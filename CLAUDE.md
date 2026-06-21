# Scale Infrastructure - CLAUDE.md

## What this is

Scale Infrastructure is Code Scale Tech's internal operating platform for client
environments, support, finance, monitoring, releases, profitability, and the
sales pipeline.

It was built from the Rainwaves Starter baseline, but the product identity,
role model, and modules are Scale Infrastructure.

Stack: Laravel 13, PHP 8.4, Sanctum, Vue 3, Vuetify, Pinia, Vite, Sail,
PostgreSQL, Redis, Horizon. Also: spatie/laravel-permission, spatie/activitylog,
spatie/medialibrary, maatwebsite/excel, apexcharts.

## Status

The operational registry (Modules 0–6: clients, deployments/infrastructure,
support, commercial, profitability, releases) is complete, plus a vision phase:

- **Reference data** — managed `lookup_options` (code-owned `LookupType` enum
  names each list; values are admin-editable). Open vocab lives here; true state
  machines stay enums.
- **Branding** — "Graphite · Electric" theme. All colour lives in
  `resources/css/app.css` `:root --rw-*` tokens + the Vuetify theme in
  `plugins/vuetify.js`. Logo is `public/logo-mark.png`.
- **Interactivity** — apexcharts dashboards (`AppDonutChart`/`AppLineChart`/
  `AppBarChart`), an `AppActivityFeed` timeline (from spatie/activitylog),
  tabbed detail views for every list entity, inline row actions, optimistic UI
  with toasts (`useToast`), and a ⌘K command palette (`AppCommandPalette`,
  nav from `config/navigation.js`).
- **Support desk** — ticket comments, a drag-and-drop ticket board, SLA tracking
  (`SlaStatus` + severity-weighted targets), and token-authed external ticket
  intake (`POST /api/intake/tickets`, `X-Intake-Token` header).
- **Opportunities** — a sales pipeline (stages, value, Kanban board, win→draft
  contract).

Not done: AI ticket triage (deferred — gated/off-by-default plan only) and
Workstream C (remote deploy execution).

## Core Roles

`administrator`, `executive`, `operations`, `finance`, `sales`, `support`,
`technical`. Permission naming: `module.action`. Authorization is enforced with
route-level `can:` middleware — there are **no Policy classes**.

## Build Pattern

Each module is built as a vertical slice, in this order:

```text
Enum(s)            source of truth for status/role/type; add label()/color()
Migration          uuid PKs (HasUuids); users are bigint
Model              casts enums; relations; scopes (search/ofStatus)
Service + Interface DB::transaction + spatie activity() logging; bind in AppServiceProvider
Request            FormRequest validation (reuse LookupType::*->existsRule() for lookup-backed fields)
Resource           expose *_label / *_color from enums
Controller         thin — delegates to the service
Routes             route-level can: middleware (no Policies)
Seeder             register in DatabaseSeeder
Feature tests      under tests/Feature
Frontend           page + Pinia store + components
```

Controllers stay thin; business rules live in services. Never hardcode a
status/role/type string — use the enum (or the lookup vocab). Spreadsheet
exports go through maatwebsite/excel (one generic `ReportExport`), never
hand-rolled CSV.

## Conventions & gotchas

- **Dev DB:** tests use sqlite in-memory; the live dev pgsql DB does NOT migrate
  with the tests. After any migration run `./vendor/bin/sail artisan migrate
  --force` and re-seed the relevant seeder, then verify via tinker.
- **Public assets** are referenced with a dynamic bind — `:src="'/logo-mark.png'"`,
  not `src="/..."` — or the Vite build fails with `UNRESOLVED_IMPORT`.
- **File-based routing:** to add an `[id].vue` detail page beside a list, the
  list must live at `<entity>/index.vue` (folder), not `<entity>.vue`.
- **Activity log:** `activity_log.subject_id` is a string (subjects use uuid PKs)
  — keep it that way.
- Charts reuse the apexcharts components above; don't add another chart lib.
- Branch off the latest `main`; do not commit to `main`. Run `./vendor/bin/pint
  --dirty` + `npm run build` before committing. No `Co-Authored-By` trailer.

## Navigation Groups

```text
Dashboard · Clients · Reports
Commercial:     Opportunities · Contracts · Billing · Invoices · Profitability
Operations:     Ops Dashboard · Deployments · Infrastructure · Monitoring ·
                Incidents · Releases · Change Requests · Provisioning · Automation
Support:        Agreements · Tickets · Ticket Board · SLA Overview
Administration: Products · Packages · Package Features · Support Tiers ·
                Reference Data · Users · Profile
```

## Planning Source

Planning docs live under
`/home/eclaims/Documents/projects/Scale-Infrastructure/Planning` — the module
backlog and the vision roadmap (`04. … Interactivity, Support Desk & Remote
Execution Roadmap`).

## Verification

```bash
php artisan test
npm run build
```
