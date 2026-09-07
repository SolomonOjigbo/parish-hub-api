# ParishHub API — cPanel Deployment Guide

Deploys the Laravel API to shared cPanel hosting (tested flows for
**Namecheap Shared/Stellar** and **Orangehost** — any cPanel host with
PHP 8.3 and MySQL 8 works the same way). The React frontend deploys
separately to Vercel — see
[`parish-hub-connect/DEPLOYMENT.md`](https://github.com/SolomonOjigbo/parish-hub-connect/blob/main/DEPLOYMENT.md).

**Recommended topology**

| Piece | Where | Example |
|---|---|---|
| API (this repo) | cPanel subdomain | `https://api.stferdinandboystown.com` |
| Frontend SPA | Vercel | `https://chms.stferdinandboystown.com` |

---

## 1. Prepare the hosting account

1. **PHP version** — cPanel → *Select PHP Version* (Namecheap) / *MultiPHP
   Manager* (most Orangehost plans): choose **PHP 8.3** and enable the
   extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `fileinfo`,
   `dom`, `curl`, `intl`.
2. **Database** — cPanel → *MySQL® Databases*: create a database
   (e.g. `user_parishhub`), a DB user with a strong password, and grant the
   user **all privileges** on that database. Note all three values.
3. **Subdomain** — cPanel → *Domains* → create `api.yourparish.org`.
   You'll point its document root at the app's `public/` folder in step 3.
4. **SSL** — both Namecheap and Orangehost run **AutoSSL** (Let's Encrypt);
   it issues automatically once the subdomain exists. Confirm under
   cPanel → *SSL/TLS Status*.
5. **Shell access** — use cPanel → *Terminal* (both hosts include it), or
   enable SSH (Namecheap: *Manage Shell* toggle in cPanel). Composer is
   preinstalled on Namecheap; on hosts without it:
   ```bash
   cd ~ && curl -sS https://getcomposer.org/installer | php
   alias composer="php ~/composer.phar"
   ```

## 2. Install the application

Clone **outside** the web root so the project itself is never web-served:

```bash
cd ~
git clone https://github.com/SolomonOjigbo/parish-hub-api.git parishhub-api
cd parishhub-api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME=ParishHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourparish.org
APP_TIMEZONE=Africa/Lagos

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=user_parishhub
DB_USERNAME=user_parishhub
DB_PASSWORD=********

# Comma-separated list of allowed SPA origins (Vercel + custom domain).
FRONTEND_URL=https://chms.yourparish.org,https://your-project.vercel.app
# Optional: allow Vercel preview deployments too:
# FRONTEND_URL_PATTERN="#^https://parish-hub-connect-.*\.vercel\.app$#"

SANCTUM_TOKEN_EXPIRATION_MINUTES=43200
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=mail.yourparish.org        # cPanel mail server
MAIL_PORT=465
MAIL_USERNAME=info@yourparish.org
MAIL_PASSWORD=********
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@yourparish.org
MAIL_FROM_NAME="St. Ferdinand Catholic Church"

TERMII_API_KEY=********
TERMII_SENDER_ID=StFerdinand
```

Then initialise:

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=ZoneSeeder --force
php artisan db:seed --class=SocietySeeder --force
php artisan db:seed --class=AdminUserSeeder --force   # change this password on first login
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 3. Point the subdomain at `public/`

**Option A (preferred — both Namecheap and Orangehost allow this):**
cPanel → *Domains* → edit the `api.` subdomain's **Document Root** to:

```
/home/USER/parishhub-api/public
```

**Option B (document root locked to `public_html/api`):** keep the app in
`~/parishhub-api` and bridge from the fixed docroot:

```bash
cp -r ~/parishhub-api/public/. ~/public_html/api/
```

then edit `~/public_html/api/index.php` so the two `require`/`bring` paths
point at `/home/USER/parishhub-api/vendor/autoload.php` and
`/home/USER/parishhub-api/bootstrap/app.php`. Re-copy `public/` whenever it
changes (rare). Never place the whole project inside `public_html`.

Sanity check: `https://api.yourparish.org/up` should return HTTP 200, and
`/api/v1/public/events` should return JSON.

## 4. Cron jobs

cPanel → *Cron Jobs* → add two entries (adjust the PHP path if your host
uses a versioned binary such as `/usr/local/bin/php83` — `which php` in the
Terminal tells you):

```
* * * * *  cd /home/USER/parishhub-api && php artisan schedule:run >> /dev/null 2>&1
* * * * *  cd /home/USER/parishhub-api && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

The scheduler drives pledge-status updates and birthday SMS; the queue
worker sends bulk email/SMS, event reminders and runs database backups.

## 5. Post-deploy checklist

- [ ] Log in as `admin@stferdinand.com` and complete the forced password change.
- [ ] Settings → Parish: parish name, diocese, address, phone, email, motto.
- [ ] Create real staff accounts (each gets a forced password change).
- [ ] Settings → send a test email and a test SMS (Termii sender ID must be registered/approved).
- [ ] Confirm the frontend origin works with no CORS errors (login from the Vercel URL).
- [ ] Confirm a password-reset email links to the Vercel frontend.
- [ ] Backups: trigger one from Settings, confirm it lists, and that its
      download URL requires login (files live in `storage/app/backups`, not the web root).

## 6. Updating

```bash
cd ~/parishhub-api
bash scripts/deploy.sh
```

(The script runs: maintenance mode → `git pull` → `composer install
--no-dev` → `migrate --force` → rebuild caches → back up. Set
`PHP_BIN=/usr/local/bin/php83` before the command if needed.)

## 7. Troubleshooting

| Symptom | Fix |
|---|---|
| 500 with blank page | `tail storage/logs/laravel.log`; usually a stale cache — `php artisan config:clear && php artisan config:cache` |
| CORS error in the browser console | The exact SPA origin must appear in `FRONTEND_URL` (comma-separated, no trailing slash), then `php artisan config:cache` |
| Emails not sending | Use the cPanel mail credentials exactly (`mail.` host, port 465 SSL); some hosts block external SMTP on shared plans |
| `php artisan` uses wrong PHP | Call the versioned binary, e.g. `php83 artisan …`, and use it in the cron entries |
| Uploads 404 | Re-run `php artisan storage:link`; with Option B, copy `public/storage` symlink manually |
