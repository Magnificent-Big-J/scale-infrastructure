# Scale Infrastructure - CLAUDE.md

## What this is

Scale Infrastructure is Code Scale Tech's internal operating platform for client environments, support, finance, monitoring, releases, and profitability.

It is built from the Rainwaves Starter baseline, but the product identity and role model are Scale Infrastructure.

Stack: Laravel 13, Sanctum, Vue 3, Vuetify, Pinia, Vite, Sail, PostgreSQL, Redis, Horizon.

## Current Phase

Phase 0: Foundation and starter alignment.

Completed in this phase:

- PostgreSQL Sail defaults
- Scale Infrastructure app identity
- Scale Infrastructure role and permission seeders
- seeded internal users
- permission-aware authenticated navigation
- dashboard shell
- module placeholder pages for the planned build sequence

## Core Roles

- `administrator`
- `executive`
- `operations`
- `finance`
- `sales`
- `support`
- `technical`

Permission naming convention: `module.action`.

## Build Pattern

Every module should follow the local Rainwaves pattern:

```text
Model
Service
Interface
Request
Resource
Controller
Policy
Enum
Feature tests
Frontend page/store/components
```

Controllers should stay thin. Business rules belong in services.

## Navigation Groups

```text
Dashboard
Clients
Commercial
  Opportunities
  Contracts
  Billing
  Profitability
Operations
  Deployments
  Infrastructure
  Monitoring
  Incidents
  Releases
Support
  Agreements
  Tickets
  SLA Overview
Administration
  Products
  Packages
  Users
  Profile
```

## Planning Source

Before building a module, read:

- `/home/eclaims/Documents/projects/Scale-Infrastructure/Planning/00. Scale Infrastructure - Planning Index v1.md`
- `/home/eclaims/Documents/projects/Scale-Infrastructure/Planning/01. Scale Infrastructure - Tech Stack & Architecture Decisions v1.md`
- the relevant file under `/home/eclaims/Documents/projects/Scale-Infrastructure/Planning/Modules`

## Verification

Run:

```bash
php artisan test
npm run build
```
