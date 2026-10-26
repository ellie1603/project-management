# BMPC Project Management System

Internal web system for Barbaza Multi-Purpose Cooperative (BMPC) to register, monitor, and document approved infrastructure projects: budgets, expenses, progress, documents, contractors, reports, and audit history.

Projects are approved outside the system (by the Board). The system starts when the Admin/CEO registers an approved project.

## Stack

- PHP 8.2+ and Laravel 12, MySQL 8
- Blade, Tailwind CSS, Alpine.js, Turbo, Lucide icons, Chart.js
- DomPDF (PDF reports) and Laravel Excel (Excel reports)
- Optional AI progress-report assistant (Gemini or Claude)
- Optional Windows desktop app (see [desktop/README.md](desktop/README.md))

## Roles

There are exactly three roles. Each role's abilities are set on **System → Roles & Permissions**.

| Role | Does |
|---|---|
| **Admin / CEO** (`admin`) | Registers and manages projects, assigns personnel, approves or rejects budget requests, reviews expenses and reports, manages users and settings. |
| **Project Personnel** (`project_personnel`) | Sees only assigned projects. Submits progress updates, photos, and documents. Position types: OIC, Staff, Branch Manager, Foreman (in-house, "by administration") and Contractor (3rd party). Only **Contractor** personnel submit budget requests. |
| **Finance & Accounting** (`finance_accounting`) | Records project expenses (one at a time or several at once) and monitors budget utilization. Does not approve requests. |

The **System Administrator** is an Admin/CEO account flagged `is_system_admin` (the developer who maintains the system). It always has every permission, is the only account that sees Roles & Permissions and System Usage, and cannot be edited or removed by the CEO.

## Default accounts (development seed)

| Account | Email | Password |
|---|---|---|
| System Administrator | `admin@bmpc.test` | `password123` |
| CEO | `ceo@bmpc.test` | `password123` |
| Finance Officer | `finance@bmpc.test` | `password123` |
| Juan Dela Cruz (Contractor) | `juan@bmpc.test` | `password123` |
| Maria Santos (Foreman) | `maria@bmpc.test` | `password123` |
| Pedro Reyes (Branch Manager) | `pedro@bmpc.test` | `password123` |

Re-running the seeder never resets an existing password. **Change every password before real users get access.**

## Local setup (Laragon on Windows)

```bash
composer setup          # install packages, create .env, generate key, migrate, build assets
php artisan db:seed     # accounts and project categories (no sample projects)
```

Then open `http://project-management.test` (Laragon's virtual host). It is faster than `php artisan serve`, which answers one request at a time.

For live CSS/JS changes run `npm run dev`; after changing assets for others, run `npm run build`.

## Everyday commands

| Task | Command |
|---|---|
| Run all tests | `php artisan test` |
| Run one test file | `php artisan test --filter=BudgetTest` |
| Apply new migrations | `php artisan migrate` |
| Undo the last migration batch | `php artisan migrate:rollback` |
| Seed accounts and categories | `php artisan db:seed` |
| Format PHP code | `vendor/bin/pint` |
| Run deadline/delay alerts now | `php artisan projects:check-alerts` |
| Check packages for known vulnerabilities | `composer audit` and `npm audit` |

The alert check runs every day at 06:00 through Laravel's scheduler (`schedule:work` in production; locally run `php artisan schedule:work` to try it).

## Environment variables

Beyond Laravel's standard ones (`APP_*`, `DB_*`, `SESSION_*`, `CACHE_STORE`, `MAIL_*`):

| Variable | Purpose |
|---|---|
| `AI_PROVIDER` | `gemini` or `anthropic`. The "Write report with AI" button stays hidden until the chosen provider's key is set. |
| `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_FALLBACK_MODEL` | Gemini settings for the progress-report assistant. |
| `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL` | Claude settings for the progress-report assistant. |
| `RATE_LIMIT_*` | Per-minute request limits (see `config/rate_limits.php`). |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Needed for "Forgot password" emails. With `MAIL_MAILER=log` (the default) emails are written to `storage/logs` instead of being sent. |
| `BOOST_BROWSER_LOGS_WATCHER` | Laravel Boost development logger; `false` keeps local navigation fast. |
| `RUN_MIGRATIONS`, `SEED_ON_FIRST_DEPLOY` | Docker only: migrate on start, and seed when the database has no users. |

Keep `.env` out of Git. Set `APP_ENV=production` and `APP_DEBUG=false` on any server real users reach.

## How it fits together

- **Authorization**: `app/Services/Permissions.php` defines every permission, which roles may hold it, and the defaults. Policies in `app/Policies` and the sidebar read those permissions; users who can't "See every project" are limited to assigned projects by `ProjectAccessScope`.
- **Budget**: `BudgetService` derives available, spent, remaining, utilization, and status (`Within Budget`, `Approaching Budget Limit` at the Settings threshold, `Budget Exceeded` at 100%). The approved budget is never changed by expenses or requests.
- **Budget requests**: contractors submit them, only the Admin/CEO decides (with a row lock so two decisions can't both apply), and approval does not create an expense. Finance records the actual payment.
- **Timeline**: `TimelineService` computes duration, days elapsed/remaining, delay, and status. `CheckProjectAlerts` sends deadline and delay notifications daily.
- **Notifications**: in-system only (the bell). Users choose which types they receive in **Profile → Notifications**; budget-exceeded and assignment notifications are always delivered.
- **Project codes**: `BMPC-PRJ-YEAR-NNNN`, generated under a lock. The highest number issued per year is remembered, so a deleted project's code is never reused.
- **Deleting a project** is permanent: its records, files, saved reports, and notifications are removed (`ProjectDeletion`); only the audit log entry stays.
- **Audit log**: important actions are recorded with old/new values and IP. Nobody can delete audit records from the app.
- **System Usage**: sign-ins, failed sign-ins, last activity, and actions per user, for the System Administrator.

## Deployment (Docker)

The `Dockerfile` builds one image with nginx, PHP-FPM, and the scheduler under supervisord, serving port 80 with a `/up` health check. It is set up for Dokploy behind Traefik (TLS at Traefik, HTTPS enforced by HSTS in `docker/nginx.conf`).

1. Provide the environment variables above (at least `APP_KEY`, `APP_URL`, `DB_*`), with `APP_ENV=production`, `APP_DEBUG=false`.
2. Set `RUN_MIGRATIONS=true` so each start applies new migrations; set `SEED_ON_FIRST_DEPLOY=true` for the very first deploy only.
3. Mount `storage/app` on a persistent volume: uploaded documents and photos live there.
4. To roll back a bad release, redeploy the previous image. Run `php artisan migrate:rollback` only if that release added migrations, and back up the database first.

## Backups (to set up on the server)

Nothing in the app backs itself up. On the server, schedule:

- a nightly `mysqldump` of the database, and a copy of the `storage/app` volume (uploaded files);
- copies kept off the server (for example, cloud storage), encrypted, for at least 30 days;
- a restore test every few months on a spare database, so the backups are known to work.

**Recovery targets** (what the backup schedule above is designed to meet):

| Target | Value | Meaning |
|---|---|---|
| Recovery Point Objective (RPO) | 24 hours | At most one day of entries may need re-encoding after a total loss. Shorten by backing up more often. |
| Recovery Time Objective (RTO) | 4 hours | Time to restore the database and files on a new server and redeploy the image. |

## Capacity and performance targets

Sized for one cooperative: up to about 50 user accounts with 10–20 people using it at the same time, a few hundred projects, and tens of thousands of expenses and progress updates over the years. Any single server with 2 CPUs and 4 GB RAM handles this.

Targets: pages respond in under 0.5 s on the server and under 2 s for the person using it. The heaviest pages (dashboards, Projects, Budget & Finance) were measured at 40–120 ms with 40 projects, and their query count stays flat as projects grow.

## Data ownership and third-party services

| Data | Owner (who keeps it correct) |
|---|---|
| Projects, assignments, contractors, users, settings, permissions | Admin/CEO (permissions: System Administrator) |
| Expenses, saved finance reports | Finance & Accounting |
| Budget requests | Contractor personnel submit; Admin/CEO decides |
| Progress updates, site photos, project documents | Assigned personnel (Admin/CEO for approved designs) |
| Audit log, sign-in history | System (read-only) |

Third-party services that receive data:

| Service | Receives | When |
|---|---|---|
| Google Gemini or Anthropic Claude | A progress update's site notes and up to two photos | Only when someone presses "Write report with AI", and only if an AI key is configured |
| jsDelivr CDN | Page requests for Lucide icons, Chart.js, and Turbo (no project data) | Every page load |
| Bunny Fonts | Requests for the Figtree and Plus Jakarta Sans fonts (no project data) | Every page load |
| Email provider (SMTP) | Password-reset emails | Once SMTP is configured |

## Documentation

- [CHANGELOG.md](CHANGELOG.md): what changed and when
- [desktop/README.md](desktop/README.md): building and using the Windows desktop app
- [CLAUDE.md](CLAUDE.md): the full system specification
