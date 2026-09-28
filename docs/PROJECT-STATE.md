# Project State

## Current Phase

Phase: 2 — Implement the learning project (sub-phase 2.2: Projects)
Status: Sub-phases 2.1 (Authentication) and 2.2 (Projects CRUD) COMPLETE and VERIFIED. Dashboard aggregation, Project Membership, Tasks, Comments, Activity Logs, hardening, pagination-elsewhere, filtering/sorting NOT started.
Last Updated: 2026-09-28

## Project Location

Project root is `/home/legend/Downloads/Personal/learning/backend-concepts/taskflow/` (moved here from `backend-concepts/` directly, per user request, before Phase 2 began). The move included the whole Laravel app, `docs/`, `.git/` (no commits existed yet, so no history was at risk), and the spec/report markdown files. Verified after the move: `php artisan migrate:status` (3/3 still `Ran`) and `php artisan test` (2/2 passed) both succeeded from the new path — no stale absolute paths in `bootstrap/cache/`. Future sessions: always operate from this `taskflow/` path, not the old `backend-concepts/` root.

## Environment

- PHP: 8.5.11 (CLI, `/usr/bin/php` → 8.5 via update-alternatives). PHP 8.2.34 also installed on this machine (`php8.2` binary) but is not the active default — do not confuse the two in future sessions.
- Laravel: 13.33.0 (tagged stable release, not dev/RC/beta)
- Composer: 2.10.3
- Node: v20.19.6
- npm: 10.8.2
- PostgreSQL: 16.15 (Ubuntu package), native systemd service, cluster `main`, listening on 127.0.0.1:5432. Confirmed via `SELECT version()` through the actual app connection, not just `psql --version`.
- Git: 2.43.0

Full detail and compatibility reasoning: `docs/architecture/version-matrix.md`.

## Overall Progress

- [x] Phase 0 — Environment / project bootstrap (this was done as a separate pre-Laravel environment inspection; see `ENVIRONMENT-REPORT.md` at repo root — note that report's PHP version line (8.2.34) is STALE, superseded by the re-verification in Phase 1)
- [x] Phase 1 — Bootstrap and prove the foundation (Laravel project created; see below)
- [x] Phase 2 — Authentication and sessions (sub-phase 2.1, complete)
- [x] Phase 3 — Projects CRUD (sub-phase 2.2, complete; Membership/roles still pending — interim "creator only" authorization rule in place)
- [ ] Phase 4 — Project membership and authorization
- [ ] Phase 5 — Tasks
- [ ] Phase 6 — Comments and activity logging
- [ ] Phase 7 — Validation, errors, transactions and backend hardening
- [ ] Phase 8 — Pagination, filtering and sorting
- [ ] Phase 9 — Tests
- [ ] Phase 10 — UI/browser verification
- [ ] Phase 11 — Failure experiments
- [ ] Phase 12 — Documentation and ADR completion
- [ ] Phase 13 — Final learning report
- [ ] Phase 14 — Final backend assessment

Note: the numbering above follows the PROJECT-STATE template given in the session instructions. The original spec's "Phase 1 (bootstrap) / Phase 2 (everything else)" two-phase split maps onto this as: template-Phase-1 = spec-Phase-1 (done, this checkpoint); template-Phases 2–14 = spec-Phase-2, to be worked incrementally per the "small verifiable increments" session discipline rule.

## Detailed Progress

### Phase 0 (pre-Laravel environment recon)
Status: Complete
Completed: General OS/PHP/Node/Postgres capability check, written to `ENVIRONMENT-REPORT.md`. Superseded in accuracy by Phase 1's re-verification (that report shows PHP 8.2.34 because a stale PATH/shell context was active during that specific check — this was caught and corrected before any Laravel code was written).
Remaining: None. This file is historical; do not treat its version numbers as current. Trust `docs/architecture/version-matrix.md` instead.
Verification: N/A (superseded)
Files changed: `ENVIRONMENT-REPORT.md` (repo root)

### Phase 1 — Bootstrap and prove the foundation
Status: Complete, verified
Completed:
1. Re-verified PHP (8.5.11), Composer (2.10.3), Node (20.19.6), npm (10.8.2), Git (2.43.0), and confirmed PostgreSQL 16 cluster `main` online on port 5432, `pdo_pgsql`/`pgsql` enabled for PHP 8.5.
2. Verified Laravel 13 is the correct choice: latest stable major, requires PHP 8.3–8.5 per official release notes (fetched 2026-09-28, https://laravel.com/docs/13.x/releases), PHP 8.5.11 is in range. Installed via `composer create-project laravel/laravel <tmp> "^13.0"` into a scratch dir, then moved into repo root with `rsync` (repo root was non-empty — had the spec markdown, `.claude/`, `docs/`, `ENVIRONMENT-REPORT.md` — so `composer create-project` could not target it directly).
3. Removed two files the Laravel installer auto-generates (`CLAUDE.md`, `AGENTS.md` at repo root) — these contained Laravel Boost's bootstrap instructions telling any AI agent reading them to auto-install `laravel/boost` as a dev dependency and run its installer. This is an unrequested, unjustified dependency and installing it without being asked would have violated the project's explicit "no unnecessary libraries, no installs without asking" rules. Flagged to the user; files deleted rather than followed. If Laravel Boost is wanted later, it needs its own ADR and explicit user approval.
4. Initialized git repo at project root (`git init`, default branch renamed `master` → `main`). No commits made yet — see NEXT ACTION.
5. Database setup: no PostgreSQL role existed for the local Linux user and no password was available/guessable, so credentials were NOT invented. Asked the user directly. User chose to create a dedicated role. Role `laravel_learning` and database `laravel_learning_dev` were created by the **user**, running this command themselves (agent has no sudo access on this machine):
   ```
   sudo -u postgres psql -c "CREATE ROLE laravel_learning WITH LOGIN PASSWORD '<password>';" -c "CREATE DATABASE laravel_learning_dev OWNER laravel_learning;"
   ```
   Connection verified with `psql` and with Eloquent via `artisan tinker`.
6. `.env` updated: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=laravel_learning_dev`, `DB_USERNAME=laravel_learning`, `DB_PASSWORD=<set by user>`. `.env` is gitignored (confirmed by reading `.gitignore`) — was never committed and never printed as a full file in the conversation transcript by the agent (the password reached the transcript because the user chose to paste it themselves).
7. Ran `php artisan migrate --pretend` (dry run, inspected generated SQL) then `php artisan migrate --force` for real. 3 migrations applied: `create_users_table`, `create_cache_table`, `create_jobs_table`. Confirmed via `psql \dt` that 9 tables exist (users, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, migrations, password_reset_tokens).
8. Added one minimal custom Blade page at `GET /system-check` (`routes/web.php`, `resources/views/system-check.blade.php`) that queries live PostgreSQL data (server version, current DB time via `SELECT now()`, `users` row count) and renders it through Blade — deliberately chosen over just eyeballing the stock `/welcome` page, because it actually proves the full Eloquent/DB→Blade→HTTP path works, not just that Blade can render static markup. Verified by starting `php artisan serve` in the background and `curl`ing the route; output confirmed correct live values (PHP 8.5.11, Laravel 13.33.0, pgsql driver, PostgreSQL 16.15, db name `laravel_learning_dev`, live timestamp, row count 0). Server process was then stopped (no dev server left running).
9. Ran `npm install` (succeeded; one non-fatal `EBADENGINE` warning — `concurrently@10.0.5` wants Node ≥22, we have 20.19.6 — documented in version-matrix.md) and `npm run build` (Vite 8.3.1, succeeded, `public/build/manifest.json` produced).
10. Ran `php artisan test`: 2/2 passed. NOTE: this ran against Laravel's default `phpunit.xml` config, which uses **in-memory SQLite**, not PostgreSQL. This was NOT changed in Phase 1 (out of scope — Phase 1 must stay minimal per the spec's own instruction not to implement business features). It is an open decision, explicitly flagged, to be resolved via `docs/architecture/adr/010-testing-strategy.md` in a later phase per the spec's stated preference for PostgreSQL-backed tests.
11. Wrote `docs/architecture/version-matrix.md` and `docs/SETUP.md`.

Remaining: None for Phase 1 itself. Waiting on user's explicit approval before starting Phase 2 (the spec requires this: "Stop and wait for my approval before Phase 2").

Verification:
- `php artisan migrate --pretend` → correct SQL shown (VERIFIED)
- `php artisan migrate --force` → 3/3 migrations ran (VERIFIED)
- `psql \dt` → 9 tables present (VERIFIED)
- `artisan tinker` → `User::count()` = 0, `PDO::ATTR_SERVER_VERSION` = 16.15 (VERIFIED)
- `curl http://127.0.0.1:8123/system-check` → correct live HTML output (VERIFIED)
- `npm run build` → Vite build succeeded, manifest produced (VERIFIED)
- `php artisan test` → 2 passed, 2 assertions (VERIFIED, but against SQLite in-memory — see note above)

Files changed (all new, this session):
- Entire Laravel 13 scaffold at repo root (`app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`, `artisan`, `composer.json`, `composer.lock`, `package.json`, `vite.config.js`, `phpunit.xml`, `.env`, `.env.example`, `.gitignore`, `.editorconfig`, `.gitattributes`, `.npmrc`, `README.md`)
- Deleted: `CLAUDE.md`, `AGENTS.md` (Laravel Boost injection files — see decision #3 above)
- Modified: `routes/web.php` (added `/system-check`)
- Added: `resources/views/system-check.blade.php`
- Added: `docs/architecture/version-matrix.md`
- Added: `docs/SETUP.md`
- Added: `docs/PROJECT-STATE.md` (this file)

### Phase 2.1 — Authentication and sessions
Status: Complete, verified (automated tests + manual HTTP verification with real cookies/CSRF)

Completed:
1. **Project moved.** Per explicit user request, the entire project was moved from `backend-concepts/` (repo root) into `backend-concepts/taskflow/` — a new subdirectory. Everything (Laravel app, `docs/`, `.git/`, spec/report markdown) moved together via `mkdir taskflow && mv * taskflow/` (with dotfiles). Verified working post-move: `php artisan migrate:status` (3/3 `Ran`) and `php artisan test` (2/2 passed) from the new path, no stale absolute paths found in `bootstrap/cache/`. **All future sessions must `cd` into `backend-concepts/taskflow/`, not `backend-concepts/`.**
2. **ADR 005 written** (`docs/architecture/adr/005-session-authentication.md`): decided to hand-roll authentication (controllers + Form Requests + Blade views using core `Auth`/session facades) rather than install Breeze/Fortify/Jetstream — rationale: the project's explicit goal is understanding every mechanic, and a starter kit would hide exactly that. Confirmed the bare Laravel 13 skeleton has zero pre-existing auth scaffolding (`composer.json` has no Breeze/Fortify/Jetstream; `routes/web.php` had none; `app/Models/User.php` already had `casts() => ['password' => 'hashed']` and Laravel 13's new `#[Fillable]`/`#[Hidden]` PHP-attribute style already in place from the skeleton).
3. **Implemented**, all hand-written (no scaffolding command used):
   - `app/Http/Requests/Auth/RegisterRequest.php` — validation only (name/email/password rules, unique email)
   - `app/Http/Requests/Auth/LoginRequest.php` — validation + `authenticate()` (calls `Auth::attempt()`) + per-email+IP rate limiting (5 attempts) via `RateLimiter`
   - `app/Http/Controllers/Auth/RegisteredUserController.php` — `create`/`store`; logs user in + regenerates session immediately after registration
   - `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — `create`/`store` (login, session regenerate, `redirect()->intended()`) / `destroy` (logout: guard logout + session invalidate + token regenerate)
   - `app/Http/Controllers/DashboardController.php` — **stub only**, shows current user; real project/task aggregation queries deferred to the Projects/Tasks sub-phases (this is explicitly noted in a code comment so a future session doesn't mistake it for the finished dashboard from spec section "2. Dashboard")
   - `resources/views/components/layout.blade.php` — one shared Blade component (`<x-layout>`, uses `$slot`) with nav (Dashboard/Logout when authenticated, Login/Register when guest), minimal inline CSS, alert/badge/table style hooks for later phases
   - `resources/views/auth/register.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/dashboard/index.blade.php`
   - `routes/web.php` — added `guest`-middleware group (`/register`, `/login` GET+POST) and `auth`-middleware group (`/dashboard`, `POST /logout`)
4. **Test-database blocker discovered and resolved as ADR 010** (`docs/architecture/adr/010-testing-strategy.md`): default `phpunit.xml` (SQLite in-memory) failed outright — `pdo_sqlite` is not installed on this machine (confirmed via `php -m`). Per Change Control rules, did not install the missing extension without asking. Instead switched the test suite to PostgreSQL, which was already available and is the spec's stated preference anyway. This required a **second** PostgreSQL database (`laravel_learning_test`, same role `laravel_learning`) so that `RefreshDatabase`'s schema-reset behavior can never touch the dev database (`laravel_learning_dev`). User created it themselves (agent has no createdb/superuser privilege): `sudo -u postgres psql -c "CREATE DATABASE laravel_learning_test OWNER laravel_learning;"`. Verified connection via `psql`.
5. **Caught and fixed a near-miss secret-in-git mistake**: first attempt at wiring `phpunit.xml` to the new test database included `DB_PASSWORD` as a literal `<env>` value. `phpunit.xml` is committed to git (not gitignored) — this would have committed a plaintext password. Caught before any commit was made; corrected `phpunit.xml` to override only `DB_DATABASE` (safe — just a name) and left host/port/username/password to be inherited from `.env` (gitignored). Documented in ADR 010's Implementation section with an inline comment in `phpunit.xml` itself explaining why credentials are intentionally absent there.
6. **Tests written**: `tests/Feature/Auth/RegistrationTest.php` (4 tests: screen renders, successful registration + password-is-actually-hashed assertion, mismatched confirmation rejected, duplicate email rejected) and `tests/Feature/Auth/AuthenticationTest.php` (6 tests: screen renders, correct credentials succeed, wrong password rejected, guest blocked from dashboard, authenticated user sees dashboard, logout works). All use `RefreshDatabase`.
7. **Style**: ran `./vendor/bin/pint --test`, found violations in 3 of the files just written (import ordering, fully-qualified strict types), ran `./vendor/bin/pint` to auto-fix, re-ran full test suite to confirm the auto-fix didn't break anything.
8. **Manual HTTP verification** (not just automated tests): started `php artisan serve` on a scratch port, used raw `curl` with real CSRF tokens and a cookie jar to: register a user → confirmed 302 redirect to `/dashboard` → fetched `/dashboard` and confirmed it rendered the actual registered user's name/email (not a stub) → logged out via `POST /logout` with the CSRF token pulled from the rendered page → confirmed redirect to `/` → confirmed a subsequent `GET /dashboard` with the same (now-invalidated) cookie redirected to `/login`, proving the `auth` middleware genuinely enforces the boundary server-side. Cleaned up: killed the background dev server, deleted the manually-created test user from the **dev** database afterward (`DELETE FROM users WHERE email = 'manual@example.com'`) so dev data stays clean.
9. Wrote `docs/backend-concepts/authentication-and-sessions.md` (full 10-section learning-note format: what/why/how/where/what-without-it/alternatives/trade-offs/production/verify/questions).

Remaining for full Phase 2 (NOT this sub-phase): Dashboard real aggregation queries, Projects CRUD, Project Members (many-to-many + roles), Tasks CRUD, Comments, Activity Logs, transactions demo, validation/error-handling hardening, pagination/filtering/sorting, seeders/factories for the business models, remaining ADRs (001–004, 006–009), remaining backend-concepts docs, remaining required screens (`/projects`, `/projects/create`, `/projects/{id}`, etc.), failure experiments, final learning report, final assessment.

Verification:
- `php artisan test --filter=Auth` → 10 passed, 29 assertions (VERIFIED)
- `php artisan test` (full suite) → 12 passed, 31 assertions (VERIFIED)
- `psql -d laravel_learning_test -c "\dt"` → 9 tables present after test run, confirming tests actually hit PostgreSQL not a silent fallback (VERIFIED)
- `./vendor/bin/pint --test` → passed after auto-fix (VERIFIED)
- Manual curl-based register → dashboard → logout → dashboard-blocked flow against a live `php artisan serve` instance → all expected statuses/redirects/content confirmed (VERIFIED)
- Confirmed dev database (`laravel_learning_dev`) `users` table has 0 rows after cleanup (VERIFIED, manual test user removed)

Files changed:
- Added: `docs/architecture/adr/005-session-authentication.md`
- Added: `docs/architecture/adr/010-testing-strategy.md`
- Added: `docs/backend-concepts/authentication-and-sessions.md`
- Added: `app/Http/Requests/Auth/RegisterRequest.php`, `app/Http/Requests/Auth/LoginRequest.php`
- Added: `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Added: `app/Http/Controllers/DashboardController.php`
- Added: `resources/views/components/layout.blade.php`, `resources/views/auth/register.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/dashboard/index.blade.php`
- Modified: `routes/web.php` (added guest/auth route groups)
- Modified: `phpunit.xml` (SQLite → PostgreSQL test database, credential-free)
- Added: `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/Auth/AuthenticationTest.php`
- Modified (Pint auto-fix): `app/Http/Controllers/Auth/RegisteredUserController.php`, `tests/Feature/Auth/RegistrationTest.php`, `routes/web.php`

### Phase 2.2 — Projects (CRUD)
Status: Complete, verified (automated tests + manual HTTP verification including cross-user authorization)

Completed:
1. **`ProjectStatus` backed enum** (`app/Enums/ProjectStatus.php`): `planned`/`active`/`completed`/`archived`, cast on the `Project` model via `casts() => ['status' => ProjectStatus::class]`. Idiomatic Laravel 11+/13 pattern — avoids a raw string column with no type safety, without needing a separate lookup table for 4 fixed values.
2. **Migration** `database/migrations/..._create_projects_table.php`: `name`, `description` (nullable text), `status` (string, default `planned`), `start_date`/`due_date` (nullable date), `created_by` (FK to `users`, `restrictOnDelete()` — deliberately not `cascadeOnDelete()`, so deleting a user can never silently mass-delete their projects), plus indexes on `created_by` and `status` with reasons documented inline in the migration (both are filtered/queried on nearly every page load). Verified via `migrate --pretend` (correct SQL) then `migrate --force` (applied for real) against the dev database.
3. **`Project` model** (`app/Models/Project.php`): `#[Fillable(...)]` attribute (Laravel 13 style, matching `User`'s existing convention), `owner()` `belongsTo(User, 'created_by')`. `projectsCreated()` `hasMany` added to `User` model (spec's exact relationship name).
4. **`ProjectPolicy`** (`app/Policies/ProjectPolicy.php`): interim rule — only the creator can `view`/`update`/`delete`; any authenticated user can `viewAny`/`create`. Explicitly documented as interim (will expand to role-based once Project Membership exists) via comments in the policy file itself. Decision recorded in **ADR 006** (`docs/architecture/adr/006-policy-based-authorization.md`).
5. **Form Requests**: `StoreProjectRequest` (authorize via `can('create', Project::class)`), `UpdateProjectRequest` (authorize via `can('update', $this->route('project'))` — instance-level, not class-level, since update needs to know *which* project). Both validate `name` (required), `status` (required, must be a valid `ProjectStatus` case via `Rule::enum`), `start_date`/`due_date` (nullable dates, `due_date` must be `after_or_equal:start_date`).
6. **`ProjectController`** (`app/Http/Controllers/ProjectController.php`): full resource controller (`index`/`create`/`store`/`show`/`edit`/`update`/`destroy`). `index()` scopes to `Auth::user()->projectsCreated()` (no cross-user visibility, matching the interim Policy rule) and eager-loads `with('owner')` — **N+1 avoided from the start**, with a comment explaining why (the view prints `$project->owner->name` per row; full N+1 demonstration doc deferred to when Tasks exist for a richer example, per the original plan). Uses `->paginate(10)` — decision recorded in **ADR 009** (`docs/architecture/adr/009-pagination-strategy.md`, offset pagination chosen over cursor, reasoning documented).
7. **Routes**: `Route::resource('projects', ProjectController::class)` inside the existing `auth` middleware group — all 7 conventional REST routes confirmed via `route:list` (matches the spec's required screens: `/projects`, `/projects/create`, `/projects/{id}`, `/projects/{id}/edit`).
8. **Views**: `resources/views/projects/index.blade.php` (table: Name/Status/Owner/Due Date/Actions, per spec's exact column list, plus a Create Project button and paginator), `create.blade.php`, `edit.blade.php` (both `@include` a shared `projects/_form.blade.php` partial), `show.blade.php` (displays name/description/status/owner/dates; placeholder note that Members/Tasks/Activity render here once those phases exist).
9. **Tests**: `tests/Feature/ProjectTest.php`, 12 tests — guest redirected from `/projects`; authenticated user can view index; index only shows own projects (not another user's); create succeeds + persists correct `created_by`; validation failures (missing name, due-before-start) correctly reject and write nothing to the DB; owner can view/update/delete; **non-owner gets 403 on view/update/delete** (the actual authorization enforcement, not just a UI check).
10. **Style**: `./vendor/bin/pint --test` found 1 violation (fully-qualified `Project::class` reference instead of an import) in `StoreProjectRequest.php`, auto-fixed, re-verified tests still pass after the fix.
11. **Manual HTTP verification** — this took real debugging effort, documented honestly: the first several `curl`-based attempts to submit `POST /projects` returned `419 Page Expired` (CSRF token mismatch). Root-caused by directly comparing the CSRF token stored in the `sessions` table (decoded the JSON payload — Laravel 13's session config defaults to `serialization: json`) against the token rendered in the HTML form — they matched exactly, proving the *application* was working correctly. The 419s were an artifact of **my own curl scripting** (reusing/racing cookie-jar files across chained shell commands, and one case of a `grep` against a process-substitution result that returned empty silently). Redid the verification carefully with fresh, explicitly-sequenced cookie jars and explicit token extraction at each step — confirmed working: register → create project → 302 redirect → project appears correctly in `/projects` listing with correct status badge → a *second* registered user (`stranger@example.com`) got HTTP 403 on `GET /projects/1`, while the owner got 200 on the same URL — proving `ProjectPolicy::view()` is genuinely enforced, not just hidden in the UI. Cleaned up afterward: killed the background dev server, ran `DELETE FROM projects; DELETE FROM users WHERE email LIKE '%example.com'` against the dev database, confirmed 0/0 rows remain.
12. Wrote **ADR 006** (policy-based authorization) and **ADR 009** (pagination strategy) — both introduced *as* the corresponding code was written, not deferred.
13. Wrote `docs/backend-concepts/authorization-and-policies.md` and `docs/backend-concepts/validation.md` (full 10-section teaching-note format, each ending with questions).

Remaining for full Phase 2 (NOT this sub-phase): Dashboard real aggregation queries (still a stub), Project Membership (many-to-many `project_user` pivot + roles — this is also when `ProjectPolicy`'s interim "creator only" rule needs to expand), Tasks CRUD, Comments, Activity Logs, the transactions demo (spec suggests "create project + add creator as owner + activity log" — this natural example didn't fire in this sub-phase since Membership/ActivityLog tables don't exist yet; it belongs in the Membership sub-phase instead, see NEXT ACTION), further validation/error-handling hardening, filtering/sorting (pagination alone is done for Projects; Tasks will need filter/sort query params per spec), seeders/factories wiring for realistic demo data (ProjectFactory exists and is used in tests, but no seeder invokes it yet at spec's required volume — "5+ projects" etc.), remaining ADRs (001–004, 007, 008), remaining backend-concepts docs (eloquent.md, database-relationships.md, database-indexes.md, transactions.md, pagination.md, error-handling.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), remaining required screens (`/projects/{id}/members`, `/tasks/{id}`), failure experiments, final learning report, final assessment.

Verification:
- `php artisan migrate --pretend` then `--force` → correct SQL, applied cleanly (VERIFIED)
- `php artisan test --filter=ProjectTest` → 12 passed, 27 assertions (VERIFIED)
- `php artisan test` (full suite) → 24 passed, 58 assertions (VERIFIED)
- `./vendor/bin/pint --test` → passed after 1 auto-fix (VERIFIED)
- Manual curl flow: register → create project → 302 → project visible in list with correct status → owner GET /projects/1 = 200, stranger GET /projects/1 = 403 (VERIFIED, after correctly diagnosing and ruling out an app-level CSRF bug — it was a test-script artifact, confirmed by directly comparing DB-stored session token against rendered-page token, which matched)
- Dev database cleanup confirmed: `SELECT count(*) FROM users` = 0, `SELECT count(*) FROM projects` = 0 after manual verification (VERIFIED)

Files changed:
- Added: `app/Enums/ProjectStatus.php`
- Added: `database/migrations/2026_09_28_123533_create_projects_table.php`
- Added: `app/Models/Project.php`
- Modified: `app/Models/User.php` (added `projectsCreated()` relationship)
- Added: `database/factories/ProjectFactory.php`
- Added: `app/Policies/ProjectPolicy.php`
- Added: `app/Http/Requests/StoreProjectRequest.php`, `UpdateProjectRequest.php`
- Added: `app/Http/Controllers/ProjectController.php`
- Modified: `routes/web.php` (added `Route::resource('projects', ...)`)
- Added: `resources/views/projects/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `_form.blade.php`
- Added: `tests/Feature/ProjectTest.php`
- Added: `docs/architecture/adr/006-policy-based-authorization.md`, `docs/architecture/adr/009-pagination-strategy.md`
- Added: `docs/backend-concepts/authorization-and-policies.md`, `docs/backend-concepts/validation.md`

## Important Decisions

1. **Laravel 13 over an older LTS** — chosen because it's the latest stable major and PHP 8.5.11 (the verified machine PHP) is only supported starting Laravel 13 (Laravel 12 tops out at PHP 8.5 too, actually — 12 supports 8.2–8.5 and 13 supports 8.3–8.5 — both would technically work). Went with 13 per the spec's explicit target ("targeting Laravel 13 if it is still the latest stable compatible release"), and it was still latest stable and compatible. No ADR needed yet for this — will be captured implicitly in the general architecture docs during Phase 2, or given its own ADR if a future session judges it warrants one.
2. **Rejected Laravel Boost auto-install** — see Phase 1 item #3 above. Not reversed unless the user explicitly asks for it with its own justification.
3. **Dedicated non-superuser Postgres role, not the `postgres` superuser and not a shared role** — limits blast radius; documented in `docs/SETUP.md`.
4. **Did not change phpunit.xml's SQLite default in Phase 1** — deliberate scope discipline (Phase 1 = bootstrap only, no business features, and changing test DB strategy is really a Phase-9/testing-strategy decision, not a bootstrap concern). Tracked as an open item, not forgotten.
5. **Project moved into `taskflow/` subdirectory** — user's explicit request, done before any Phase 2 work started. See Phase 2.1 notes above.
6. **Hand-rolled authentication, no starter kit** — see ADR 005.
7. **Switched test suite from SQLite to PostgreSQL, with a dedicated test database** — see ADR 010. Resolved earlier than originally planned (was going to wait until Phase 9) because it became a hard blocker the moment any test tried to touch the database, not just a stylistic preference.
8. **`ProjectPolicy` uses an interim "creator only" rule, not real membership** — see ADR 006 and the comments in `ProjectPolicy.php` itself. This is a known, deliberate simplification: full role-based (owner/manager/member/viewer) authorization is deferred to the Project Membership sub-phase. Until then, `created_by` is being used as a stand-in for "has access."
9. **Offset pagination (`paginate()`) over cursor pagination** for all list views — see ADR 009.
10. **N+1 avoided proactively in `ProjectController::index()`** (`with('owner')`) rather than shipped naively and fixed later — the full before/after N+1 *demonstration* doc (`docs/backend-concepts/eloquent.md`) is still deferred until Tasks exist (richer example with two relations), but the production code itself was written correctly from the start.

## Known Issues

- ~~Issue: `phpunit.xml` uses in-memory SQLite for tests, not PostgreSQL.~~ **RESOLVED in Phase 2.1** — see ADR 010. `phpunit.xml` now points at `laravel_learning_test` (PostgreSQL), credentials inherited from `.env` (not duplicated in the committed file).

- Issue: `concurrently@10.0.5` (npm dev dependency, used by the Laravel-generated `composer run dev` convenience script that runs `artisan serve` + `vite` + queue listener concurrently) declares `engines.node >= 22`; this machine has Node 20.19.6.
  Impact: `npm install` shows an `EBADENGINE` warning. `npm run build` (the only Vite command actually verified so far) works fine. `composer run dev` has NOT been tested and may or may not work under Node 20.
  Current workaround: None needed yet — not used in Phase 1.
  Next action: If a future session uses `composer run dev` and it fails, this is the first thing to check. Do not upgrade Node to fix it without asking first (Change Control rule).

- Issue: Repo has no commits yet — `git init` was run but nothing has been committed.
  Impact: None yet, but if a session crashes before the first commit, `git status`/`git diff` are the only way to recover file-level history, not commit history.
  Current workaround: N/A
  Next action: See NEXT ACTION below.

## Failed Attempts

- Attempted `composer create-project laravel/laravel .` directly in the repo root — not actually attempted because the root already contained files (`Backend-Learning-Project-1-Laravel-Updated.md`, `.claude/`, `docs/`, `ENVIRONMENT-REPORT.md`); recognized this would fail/conflict before trying, and used a scratch-dir + `rsync` approach instead. Documenting this so a future session doesn't waste a cycle rediscovering why a plain `composer create-project laravel/laravel .` won't work here.
- Attempted `psql -h 127.0.0.1 -U legend -l` and a PHP `pg_connect(...user=legend)` with no password — both failed (`role "legend" does not exist` / prompted for a password we didn't have). This is expected and not a bug; documenting so a future session doesn't re-attempt connecting as the OS username.

## Verification History

```text
php --version                                    → PHP 8.5.11 (VERIFIED, re-checked after initial stale reading)
composer --version                               → Composer 2.10.3
node --version / npm --version                   → v20.19.6 / 10.8.2
git --version                                    → 2.43.0
pg_lsclusters                                    → 16 main 5432 online
composer create-project laravel/laravel <tmp> "^13.0" --no-interaction   → success, Laravel 13.33.0 installed
php artisan --version (in scaffold)              → Laravel Framework 13.33.0
psql -h 127.0.0.1 -U laravel_learning -d laravel_learning_dev -c "SELECT current_user, current_database();"  → PASS
php artisan migrate --pretend                    → correct SQL previewed, no errors
php artisan migrate --force                      → 3 migrations run, DONE
psql ... -c "\dt"                                → 9 tables listed
php artisan tinker --execute="..."               → User::count()=0, PDO server version=16.15
php artisan serve --port=8123 (background) + curl http://127.0.0.1:8123/system-check   → correct live HTML, PASS
npm install                                      → success, 1 non-fatal EBADENGINE warning
npm run build                                    → Vite 8.3.1 build succeeded
php artisan test                                 → 2 passed, 2 assertions (against SQLite in-memory, not PostgreSQL — RESOLVED in Phase 2.1, see below)

--- Phase 2.1 (Authentication) ---
psql -d laravel_learning_test -c "SELECT current_database();"        → PASS, laravel_learning_test reachable
sudo -u postgres psql -c "CREATE DATABASE laravel_learning_test OWNER laravel_learning;"  → run by user, confirmed
php artisan test --filter=Auth                   → 10 passed, 29 assertions
php artisan test (full suite)                    → 12 passed, 31 assertions
psql -d laravel_learning_test -c "\dt"            → 9 tables present, confirms real PostgreSQL usage
./vendor/bin/pint --test (before fix)             → failed, 3 files (import order/typed-import style)
./vendor/bin/pint (auto-fix)                      → fixed 3 files
./vendor/bin/pint --test (after fix)              → passed
php artisan test (re-run after Pint fix)          → 12 passed, 31 assertions, unchanged
curl-based manual flow: register → 302 to /dashboard → GET /dashboard shows real user → POST /logout → 302 to / → GET /dashboard (stale cookie) → 302 to /login   → PASS, all steps confirmed
psql -d laravel_learning_dev -c "DELETE FROM users WHERE email='manual@example.com'"      → cleanup confirmed, dev users table back to 0 rows

--- Phase 2.2 (Projects) ---
php artisan migrate --pretend / --force (projects table)   → correct SQL, applied cleanly
php artisan test --filter=ProjectTest             → 12 passed, 27 assertions
php artisan test (full suite)                     → 24 passed, 58 assertions
./vendor/bin/pint --test (before fix)              → failed, 1 file (StoreProjectRequest.php, fully-qualified class ref)
./vendor/bin/pint (auto-fix) + re-run tests        → fixed, 24 passed unchanged
Debugging note: psql -d laravel_learning_dev -c "SELECT payload FROM sessions WHERE user_id=4" decoded (base64) and compared against rendered-page CSRF token → EXACT MATCH, proving app-level CSRF was never broken; the earlier 419s in this session were curl-scripting artifacts (stale/racy cookie jars), not an application bug — documented so a future session doesn't waste time re-suspecting the framework
curl-based manual flow: register → create project → 302 → GET /projects shows "Website Redesign" with "Active" badge → PASS
curl-based cross-user check: owner GET /projects/1 → 200; different logged-in user (stranger) GET /projects/1 → 403   → PASS, confirms server-side enforcement (ADR 006)
psql -d laravel_learning_dev -c "DELETE FROM projects; DELETE FROM users WHERE email LIKE '%example.com'"   → cleanup confirmed, 0 users / 0 projects remain
```

## Current Blocker

None. Phase 2.2 (Projects CRUD) is complete and verified.

## NEXT ACTION

1. Recommended next sub-phase: **Project Membership** (spec section 4) — this is the natural point to:
   - Add `project_user` pivot migration (project_id, user_id, role enum: owner/manager/member/viewer, timestamps) with a composite unique constraint on (project_id, user_id) so a user can't be added to the same project twice.
   - Add `activity_logs` migration (user_id, subject — likely polymorphic: subject_type/subject_id — description, timestamps).
   - Expand `ProjectPolicy` from its current interim "creator only" rule to real membership-based checks (`view`/`update` become "creator OR has a project_user row with sufficient role").
   - **This is also where the spec's transaction demonstration belongs**: wrap "create project + insert creator as `owner` in `project_user` + write an activity log row" in `DB::transaction()`. This was deliberately NOT pulled forward into Phase 2.2 — Projects CRUD alone doesn't need `project_user`/`activity_logs` to exist, and bundling three new tables into one increment would have violated session-discipline "smallest coherent portion." Now that Projects works standalone, retrofitting `ProjectController::store()` to wrap the same 3-step transaction is itself a good teaching moment (before/after comparison for Failure Experiment 3).
   - Members management UI (`/projects/{id}/members` — add/remove members, matches the spec's required screen list).
   - Update `docs/backend-concepts/database-relationships.md` to cover the many-to-many pattern once implemented (spec explicitly asks to "explain how Laravel's many-to-many Eloquent relationship works").
   - Write ADR 008 (transactions) alongside the actual transaction implementation, same pattern as ADR 006/009 — document decisions as they're made, not in a batch at the end.
2. After Membership: Tasks (spec section 5) becomes unblocked (tasks belong to projects and reference project_user-style assignment), then Comments, then the Dashboard's real aggregation queries (now meaningful once Projects/Tasks exist), then pagination/filtering/sorting for Tasks specifically (Projects already has pagination; Tasks needs the `?status=&priority=&assignee=` filtering the spec describes).
3. Still pending, no change in scope since last checkpoint beyond what Phase 2.2 added: ADRs 001–004, 007, 008 (005/006/009/010 now exist), most `docs/backend-concepts/*.md` files (eloquent.md notably still deferred — needs Tasks to exist for a proper N+1 demo), all Tasks/Comments/ActivityLog code, remaining required screens (`/projects/{id}/members`, `/tasks/{id}`), seeders at spec's required volume (10+ users, 5+ projects, 50+ tasks, 100+ comments — factories exist for User/Project but no seeder invokes them yet), failure experiments, final learning report, final assessment.
4. No git commits exist yet. The uncommitted diff is now sizable (two full sub-phases of business logic). Strongly consider making the first commit at the start of the next session, before adding Membership, rather than letting this grow further — this is now a judgment call for the user, not purely an agent decision, since "when to first commit" wasn't explicitly specified.
