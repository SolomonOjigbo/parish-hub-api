# ParishHub API — Laravel 11 Backend

Church Management System API for St. Ferdinand Catholic Church, Boys Town.

## Requirements
- PHP 8.3+
- Composer
- MySQL 8 (production) or SQLite (local dev)

## Local Setup

```bash
git clone <repo-url>
cd parishhub-api
composer install
cp .env.example .env
php artisan key:generate
# Configure your DB credentials in .env
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

## API Base URL
`http://localhost:8000/api/v1`

## Authentication
Bearer token via Laravel Sanctum.
All protected routes require: `Authorization: Bearer {token}`

## Full Spec
See `BACKEND_SPEC.md` for the complete API specification,
database schema, roles, business logic, and cPanel deployment guide.

## Tech Stack
- Laravel 11 + PHP 8.3
- Laravel Sanctum (token auth)
- Spatie Laravel Permission (roles)
- Maatwebsite Excel (exports)
- barryvdh/laravel-dompdf (PDF reports)
- Intervention Image (photo uploads)
- Owen-IT Laravel Auditing (audit logs)
