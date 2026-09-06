# ParishHub Deployment Guide (cPanel + MySQL 8)

Target: shared cPanel hosting for the parish domain (e.g.
`https://chms.stferdinandboystown.com`) serving the API, with the React
frontend deployed as static files on the same domain or a subdomain.

## 1. Prepare the server
- PHP 8.3 with extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `fileinfo`, `dom`.
- MySQL 8 database + user (note the credentials).
- SSH access (or cPanel Terminal) with Composer available.

## 2. Upload the API
```bash
git clone <repo> parishhub-api && cd parishhub-api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` for production:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://chms.stferdinandboystown.com
APP_TIMEZONE=Africa/Lagos
DB_CONNECTION=mysql
DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
FRONTEND_URL=https://chms.stferdinandboystown.com
SANCTUM_TOKEN_EXPIRATION_MINUTES=43200
MAIL_* (parish SMTP)
TERMII_API_KEY=... TERMII_SENDER_ID=StFerdinand
QUEUE_CONNECTION=database
```

Then:
```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=ZoneSeeder --force
php artisan db:seed --class=SocietySeeder --force
php artisan db:seed --class=AdminUserSeeder --force   # change this password immediately
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Point the (sub)domain's document root at `parishhub-api/public`.
Never expose the project root; `storage/` holds private backups.

## 3. Cron entries (cPanel → Cron Jobs)
```
* * * * *  cd /home/USER/parishhub-api && php artisan schedule:run >> /dev/null 2>&1
* * * * *  cd /home/USER/parishhub-api && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```
The queue worker sends bulk email/SMS, event reminders and runs backups.

## 4. Deploy the frontend
```bash
cd parish-hub-connect
echo "VITE_API_BASE_URL=https://chms.stferdinandboystown.com/api/v1" > .env.production
npm ci && npm run build
```
Upload `dist/` to the frontend document root. Add an SPA rewrite so deep
links resolve (cPanel → `.htaccess` in the frontend root):
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```
If the frontend lives on a different origin, set that origin in the API's
`FRONTEND_URL` (drives CORS and password-reset links).

## 5. Post-deploy checklist
- [ ] Log in as the seeded admin and complete the forced password change.
- [ ] Settings → Parish: set parish name, diocese, address, phone, email, motto.
- [ ] Create staff accounts (Staff & Roles) — each gets a forced password change.
- [ ] Send a test email and test SMS from Settings.
- [ ] Confirm `https://.../api/v1/../settings/backups` requires login (backups are on the private disk).
- [ ] Verify the reset-password email links to the frontend.
- [ ] `composer audit` is clean at deploy time; re-run after dependency updates.

## 6. Backups
- Settings → Backup runs `DatabaseBackupJob` (queued): dumps to
  `storage/app/backups`, keeps the last 8, downloadable only by
  `settings.manage` users via `GET /api/v1/settings/backups/{filename}`.
- Also enable cPanel-level scheduled backups for files + database.

## 7. Updating
```bash
cd parishhub-api
php artisan down
git pull && composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```
Rebuild and re-upload the frontend `dist/` when it changes.
