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

## Running the App

```bash
php artisan serve
```

Then open:

- `http://127.0.0.1:8000/` — Laravel's default welcome page
- `http://127.0.0.1:8000/system-check` — Phase 1 bootstrap verification page; confirms Blade is rendering data that came from a live PostgreSQL query (PHP version, Laravel version, DB driver, PostgreSQL server version, current DB time via `SELECT now()`, and `users` row count). This route is temporary scaffolding and will be removed/replaced once the real dashboard exists in Phase 2.
- `http://127.0.0.1:8000/up` — Laravel's built-in health check route (returns 200 if the app boots).

## Known Non-Blocking Issues

See `docs/architecture/version-matrix.md` → "Known Version Mismatches" for the `concurrently`/Node 22 engine warning.

The default test suite (`phpunit.xml`) currently runs against in-memory SQLite, not PostgreSQL. This is Laravel's stock scaffolding default and has **not** been changed yet — it is an open decision to be resolved with `docs/architecture/adr/010-testing-strategy.md` in a later phase, per the project's stated preference for testing against PostgreSQL where practical.
