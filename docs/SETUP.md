# Local Setup

This project uses native local PHP/PostgreSQL/Node — no Docker. See `docs/architecture/version-matrix.md` for exact verified versions.

## Prerequisites (already verified on this machine)

- PHP 8.5 with `pdo_pgsql`/`pgsql` extensions
- Composer 2.10+
- Node.js 20+ / npm 10+
- PostgreSQL 16, running locally on port 5432 (native Ubuntu service, cluster `main`)

## Database

A dedicated development role and database were created for this project (not the default `postgres` superuser, and not shared with any other project):

- Role: `laravel_learning`
- Database: `laravel_learning_dev`

These were created interactively by the project owner running:

```bash
sudo -u postgres psql \
  -c "CREATE ROLE laravel_learning WITH LOGIN PASSWORD '<your password>';" \
  -c "CREATE DATABASE laravel_learning_dev OWNER laravel_learning;"
```

This was a deliberate choice: the agent building this project does not have `sudo` access on this machine and must never guess or be handed database superuser credentials. Creating a scoped role with only ownership of one dev database (rather than reusing a shared/superuser role) limits blast radius if `.env` is ever mishandled.

## Environment Configuration

Copy `.env.example` to `.env` (already done for this repo) and confirm the `DB_*` values point at the role/database above:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_learning_dev
DB_USERNAME=laravel_learning
DB_PASSWORD=<your password>
```

`.env` is gitignored by Laravel's default `.gitignore` and must never be committed.

## Bootstrap Steps

```bash
composer install
npm install
php artisan key:generate   # already run once during scaffolding; re-run only if APP_KEY is lost
php artisan migrate
npm run build
php artisan test
```

## Seed Data / Demo Accounts

```bash
php artisan db:seed
```

Populates `laravel_learning_dev` with the volume `database/seeders/DatabaseSeeder.php` is designed to produce (14 users, 6 projects, 60 tasks, 120 comments, 66 activity log entries) — see that file's own comments for exactly how membership roles and task/comment distribution are constructed. Running `db:seed` again inserts a SECOND full copy of this data (it is not idempotent) — if re-seeding, clear existing rows first:

```bash
psql -h 127.0.0.1 -U laravel_learning -d laravel_learning_dev \
  -c "DELETE FROM comments; DELETE FROM activity_logs; DELETE FROM tasks; DELETE FROM project_user; DELETE FROM projects; DELETE FROM users;"
php artisan db:seed
```

Four named demo accounts, one per project role, all using the password **`password`** (documented in `DatabaseSeeder::DEMO_PASSWORD`, deliberately not a real-world-safe value — this is local dev seed data only):

| Email | Role on every seeded project |
|---|---|
| `admin@example.test` | Owner (creator of 5 of the 6 projects) |
| `manager@example.test` | Manager |
| `member@example.test` | Member |
| `viewer@example.test` | Viewer |

Log in as any of these to immediately see a populated dashboard, project list, and task list without needing to create data manually first.

## Running the App

```bash
php artisan serve
```

Then open:

- `http://127.0.0.1:8000/` — Laravel's default welcome page
- `http://127.0.0.1:8000/login` — log in with one of the demo accounts above (after seeding) to explore the app immediately
- `http://127.0.0.1:8000/system-check` — Phase 1 bootstrap verification page; confirms Blade is rendering data that came from a live PostgreSQL query. Left in place as a standing low-level connectivity check, distinct from the real dashboard.
- `http://127.0.0.1:8000/up` — Laravel's built-in health check route (returns 200 if the app boots).

## Known Non-Blocking Issues

See `docs/architecture/version-matrix.md` → "Known Version Mismatches" for the `concurrently`/Node 22 engine warning.

The test suite runs against a dedicated PostgreSQL database (`laravel_learning_test`), not SQLite — see `docs/architecture/adr/010-testing-strategy.md` for the full reasoning (this was resolved early in the project; the SQLite default only ever applied briefly during initial bootstrap).
