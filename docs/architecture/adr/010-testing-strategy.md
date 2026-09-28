# 010 — Testing Strategy: PostgreSQL, Not SQLite

# Decision

Feature and database tests run against a real PostgreSQL database (`laravel_learning_test`), not SQLite. `RefreshDatabase` is used to reset schema/state between test runs. Unit tests that don't touch the database are unaffected by this decision.

# Context

Laravel's default `laravel new` scaffold ships `phpunit.xml` configured for `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`. This is the fastest possible test database (no disk I/O, no separate process) and is Laravel's own default recommendation for new projects.

# Problem

This machine does not have the `pdo_sqlite` PHP extension installed (`php -m | grep sqlite` returns nothing). Running `php artisan test` against the stock config fails immediately with `could not find driver`. Separately, the project's own spec explicitly states a preference: "Tests may use SQLite only if there is a documented reason and PostgreSQL-specific behavior is separately tested against PostgreSQL; prefer PostgreSQL for this learning project."

# Options Considered

## Option A — Install `pdo_sqlite` and keep SQLite in-memory tests

Advantages:
- Fastest test runs (in-memory, no network round-trips).
- Matches Laravel's own default/convention.

Disadvantages:
- Requires installing a new system package (`php8.5-sqlite3` or similar), which the project's Change Control rules require asking about first — and more importantly, the project spec explicitly prefers PostgreSQL for this learning project.
- SQLite and PostgreSQL have real behavioral differences (type coercion, case-sensitivity of `LIKE`, constraint enforcement timing, JSON functions, generated columns) — a passing SQLite test suite does not guarantee the same code is correct against the database the app actually runs on in every environment.

## Option B — PostgreSQL, dedicated test database, `RefreshDatabase`

Advantages:
- Tests run against the exact database engine used everywhere else in this project — no "works in tests, breaks in Postgres" surprises.
- No new system package needed (`pdo_pgsql` already installed and verified in Phase 1).
- Matches the project spec's explicit stated preference.

Disadvantages:
- Slower than in-memory SQLite (real TCP connection, real disk-backed database) — acceptable at this project's scale (tens of tests, not thousands).
- Requires a second database (separate from the dev database) so that `RefreshDatabase`'s schema-reset behavior never touches development data. This required an extra one-time setup step (`CREATE DATABASE laravel_learning_test OWNER laravel_learning;`, run by the project owner since the agent has no superuser/createdb privileges).

## Option C — PostgreSQL, same database as development, wrapped in transactions only (no schema refresh)

Advantages:
- No second database needed.

Disadvantages:
- Rejected: `RefreshDatabase` calls `migrate:fresh` on first use per test run when there are pending/mismatched migrations, which would drop and recreate tables in the *development* database — an unacceptable risk to run automatically. A dedicated database is the safer default.

# Decision Made

Option B.

# Why

Matches the explicit project requirement, uses an extension already verified present, and avoids the SQLite/PostgreSQL behavioral-parity risk entirely rather than managing it selectively.

# Implementation

- `phpunit.xml`: overrides only `DB_DATABASE=laravel_learning_test`. Driver/host/port/username/password are **not** duplicated here — they're inherited from `.env` (gitignored), because `phpunit.xml` itself is committed to git and must never contain a credential. See the inline comment in `phpunit.xml`.
- Tests that touch the database (`tests/Feature/Auth/*`) use `Illuminate\Foundation\Testing\RefreshDatabase`.
- `laravel_learning_test` was created with: `CREATE DATABASE laravel_learning_test OWNER laravel_learning;` — same role as the dev database, separate database, so credentials aren't duplicated but data is isolated.

# Consequences

- Test runs require a live PostgreSQL connection — tests cannot run fully offline/air-gapped. Acceptable: this is a local learning project with PostgreSQL as a stated hard requirement, not a CI pipeline running on ephemeral runners without a database.
- Test suite runtime is bound by real database round-trips rather than in-memory speed. If this becomes a real friction point at a much larger test count, revisit with parallel testing (`--parallel`, ships with Laravel) using per-process database suffixes, rather than reverting to SQLite.

# Verification

```bash
php artisan test --filter=Auth
```
Should connect to `laravel_learning_test` (confirm via `psql -h 127.0.0.1 -U laravel_learning -d laravel_learning_test -c "\dt"` after a run — `RefreshDatabase` leaves the schema migrated between runs within a suite).

# When We Would Reconsider This Decision

If test suite runtime becomes a genuine bottleneck (multiple minutes) at a scale where SQLite's speed advantage matters more than PostgreSQL-parity risk, and `pdo_sqlite` is explicitly approved for installation.
