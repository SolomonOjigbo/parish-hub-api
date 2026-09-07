# ParishHub API — Laravel Backend

Church Management System API for Catholic parishes, built for
**St. Ferdinand Catholic Church, Boys Town, Ipaja** (Catholic Archdiocese of
Lagos) and configurable for any parish via the settings store.

## Tech Stack
- Laravel 13 + PHP 8.3
- Laravel Sanctum (bearer-token auth, 30-day expiry)
- Spatie Laravel Permission (roles: `super_admin`, `priest`, `finance_officer`, `secretary`, `society_leader`, `parishioner`)
- barryvdh/laravel-dompdf (certificates, receipts, statements, bulletins, reports)
- Maatwebsite Excel (roster/finance import & export)
- Intervention Image (member photos)
- Termii (Nigerian SMS — set `TERMII_API_KEY` / `TERMII_SENDER_ID`)

## Requirements
- PHP 8.3+, Composer
- MySQL 8 (production) or SQLite (local dev)

## Local Setup

```bash
composer install
cp .env.example .env          # then set APP_ENV=local, DB_CONNECTION=sqlite, FRONTEND_URL=http://localhost:8080
php artisan key:generate
php artisan migrate --seed    # seeds roles, zones, societies, admin + demo data (local only)
php artisan storage:link
php artisan serve             # http://localhost:8000
```

Seeded logins (local):
- `admin@stferdinand.com` / `Admin@1234` — super admin, forced password change on first login.

## API
Base URL: `http://localhost:8000/api/v1` — bearer token via `POST /auth/login`.
All protected routes require `Authorization: Bearer {token}` and enforce
spatie permissions per module (members, families, societies, events, finance,
communications, reports, staff, committees, settings, audit).

Response envelope: `{ success, message, data, meta? }` with snake_case keys.

Notable endpoints beyond standard CRUD:
- `GET /dashboard/summary`, `GET /notifications` — permission-gated aggregates
- `GET /members/{id}/sacraments/{sid}/certificate` — sacrament certificate PDF
- `GET /members/{id}/giving/statement?year=` and `GET /portal/giving/statement` — annual giving statement PDFs
- `GET /donations/{id}/receipt` — donation receipt PDF
- `POST /members/import` — roster CSV/Excel import
- `GET|POST /bulletins` + `/{id}/preview` + `/{id}/export` — Sunday bulletin composer
- `/portal/*` — member self-service (profile, giving, events, family)
- `/public/*` — unauthenticated registration, visitor card, upcoming events (throttled)

## Scheduled jobs
`php artisan schedule:work` (or a cron entry) runs:
- `pledges:update-statuses` daily
- `members:send-birthday-wishes` daily at 06:00 (Africa/Lagos)

Queued jobs (bulk email/SMS, reminders, backups) need a worker:
`php artisan queue:work` (`QUEUE_CONNECTION=database`).

## Tests

```bash
php artisan test
```

## Deployment
See [DEPLOYMENT.md](DEPLOYMENT.md) for the cPanel + MySQL production guide
(Namecheap/Orangehost examples); the frontend deploys to Vercel per its own
[DEPLOYMENT.md](https://github.com/SolomonOjigbo/parish-hub-connect/blob/main/DEPLOYMENT.md).
