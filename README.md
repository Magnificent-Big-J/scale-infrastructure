# Scale Infrastructure

Internal Code Scale Tech infrastructure operating platform.

Scale Infrastructure is the operational control centre for managing:

- clients and contacts
- products and packages
- deployments and infrastructure assets
- monitoring checks
- support agreements, tickets, and incidents — with a drag-and-drop ticket
  board, ticket comments, SLA tracking, and token-authed external intake
- the sales pipeline (opportunities) with a Kanban board and win → draft contract
- contracts, billing, invoices, and payments
- releases, provisioning, and automation
- profitability and executive/operations reporting with analytics charts
- managed reference data, an activity timeline, and a ⌘K command palette

## Stack

- Laravel 13, PHP 8.4
- PostgreSQL
- Redis and Horizon
- Sanctum
- Vue 3, Vuetify, Pinia, Vite, apexcharts
- Spatie Permission, Activity Log, and Media Library
- maatwebsite/excel for spreadsheet exports

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
