# Scale Infrastructure

Internal Code Scale Tech infrastructure operating platform.

Scale Infrastructure is the operational control centre for managing:

- clients and contacts
- products and packages
- deployments and infrastructure assets
- monitoring checks
- support agreements, tickets, and incidents
- contracts, billing, invoices, and payments
- releases, provisioning, and automation
- profitability and executive reporting

## Stack

- Laravel 13
- PHP 8.3+
- PostgreSQL
- Redis and Horizon
- Sanctum
- Vue 3, Vuetify, Pinia, Vite
- Spatie Permission, Activity Log, and Media Library

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Start local services:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

Run frontend:

```bash
npm run dev
```

## Verification

```bash
php artisan test
npm run build
```

## Seeded Users

All seeded users use the password `password`.

- `admin@codescaletech.test`
- `operations@codescaletech.test`
- `executive@codescaletech.test`
- `finance@codescaletech.test`
- `sales@codescaletech.test`
- `support@codescaletech.test`
- `technical@codescaletech.test`

## Planning

Execution planning lives in:

`/home/eclaims/Documents/projects/Scale-Infrastructure/Planning`
