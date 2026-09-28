# Project State

## Current Phase

Phase: 2 — Implement the learning project (sub-phase 2.3: Project Membership)
Status: Sub-phases 2.1 (Authentication), 2.2 (Projects CRUD), 2.3 (Project Membership + transactions + activity logs) COMPLETE and VERIFIED. Dashboard real aggregation, Tasks, Comments, hardening, filtering/sorting NOT started.
Last Updated: 2026-09-28

## Remote

Pushed to `git@github.com:mrahmanruet16/taskflow.git`, branch `main`. Two commits pushed and confirmed:
- `84a7bd8` — root commit, covers Phases 1 + 2.1 (Authentication) + 2.2 (Projects CRUD)
- `3c7cb50` — Phase 2.3 (Project Membership, transactions, activity logging)

Confirmed via `git log --oneline -5` and `git push` output (`84a7bd8..3c7cb50  main -> main`) — working tree is clean (`git status --short` empty) as of this checkpoint. A future session should still re-verify with `git log`/`git status` before trusting this note, rather than assuming it stayed accurate if significant time has passed.

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
- [x] Phase 3 — Projects CRUD (sub-phase 2.2, complete)
- [x] Phase 4 — Project membership and authorization (sub-phase 2.3, complete — roles: owner/manager/member/viewer, real membership-based ProjectPolicy, transaction-wrapped project creation, activity logging for project/member events)
- [ ] Phase 5 — Tasks
- [ ] Phase 6 — Comments and activity logging (activity_logs table + logging infrastructure already exist from 2.3 — this phase is really "extend logging to Task/Comment events," not build it from scratch)
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

### Phase 2.3 — Project Membership (roles, transactions, activity logging)
Status: Complete, verified (automated tests + extensive manual HTTP verification of role-based authorization)

Completed:
1. **`ProjectRole` backed enum** (`app/Enums/ProjectRole.php`): `owner`/`manager`/`member`/`viewer`, with a `canManageProject(): bool` helper centralizing "which roles may edit project metadata" so that logic lives in one place rather than being duplicated across Policy methods.
2. **Migrations**: `project_user` pivot (`project_id`, `user_id`, `role`, `cascadeOnDelete()` on both FKs — deliberately the OPPOSITE trade-off from `projects.created_by`'s `restrictOnDelete()`, reasoning documented inline: a membership row has no meaning once either side is gone, whereas a project's ownership record should never silently disappear) with a `unique(['project_id', 'user_id'])` constraint (one role per user per project); `activity_logs` (polymorphic `subject_type`/`subject_id` via `nullableMorphs()`, `user_id` with `nullOnDelete()` — reasoning documented inline: audit trail should survive the actor's account being deleted). **Caught and fixed a redundant index**: initially added an explicit `index(['subject_type', 'subject_id'])` after `nullableMorphs('subject')`, then realized `nullableMorphs()` already creates that exact index automatically — removed the duplicate before migrating.
3. **`ActivityLog` model** (`app/Models/ActivityLog.php`): `user()` belongsTo, `subject()` morphTo.
4. **`Project` model expanded**: `members()` (belongsToMany with `withPivot('role')`), `activities()` (morphMany, `->latest()`), `hasMember(User): bool`, `roleOf(User): ?ProjectRole` — the latter two are the actual primitives `ProjectPolicy` now checks against.
5. **`User` model expanded**: `projects()` (belongsToMany membership, distinct from `projectsCreated()` — documented why both exist and can diverge), `activities()` hasMany.
6. **`ProjectPolicy` rewritten** from the Phase 2.2 interim "creator only" rule to real membership-based checks: `view`/`viewMembers` — any member (any role); `update` — Owner/Manager only (via `canManageProject()`); `delete` — Owner only, deliberately MORE restrictive than update; `manageMembers` — Owner/Manager only. Every method now delegates to `Project::hasMember()`/`roleOf()` rather than comparing `created_by` directly.
7. **`ProjectController` updated**: `index()` now scopes to `Auth::user()->projects()` (membership) instead of `projectsCreated()`. **`store()` rewritten to wrap all three writes — create project, attach creator as Owner in `project_user`, write an activity log entry — in `DB::transaction()`** (this is the spec's explicitly suggested transaction example, deferred from Phase 2.2 specifically so it could be implemented once the supporting tables existed rather than bolted on afterward). `show()` now eager-loads `['members', 'activities.user']`. `update()` also writes an activity log entry (not wrapped in a transaction — a single write, no atomicity concern).
8. **`StoreProjectMemberRequest`**: authorizes via `manageMembers` policy; validates email exists as a user AND (via a closure rule, not just relying on the DB unique constraint) that the target isn't already a member — documented why the closure rule exists (a friendly validation message vs. a raw `QueryException`-turned-500).
9. **`ProjectMemberController`** (`app/Http/Controllers/ProjectMemberController.php`): `index` (list members), `store` (add by email + role, logs activity), `destroy` (remove member, logs activity) — **with a business-rule guard against removing the last Owner** (would otherwise leave a project permanently undeletable and — subtly — unable to ever satisfy `ProjectPolicy::update()` for anyone, since no one would hold a manage-capable role). This guard is deliberately NOT a Form Request validation rule, since it depends on querying the state of *other* membership rows, not just the shape of the current request — matches the validation-vs-business-rule distinction documented in `docs/backend-concepts/validation.md`.
10. **Routes**: `/projects/{project}/members` (GET/POST) and `/projects/{project}/members/{user}` (DELETE) — explicitly named routes rather than a full `Route::resource`, since only index/store/destroy are meaningful (no "edit a membership" screen). Matches the spec's required `/projects/{id}/members` screen.
11. **Views**: `resources/views/projects/members.blade.php` (table + add-member form, `@can('manageMembers', ...)`-gated — UI convenience only, backed by the real server-side check in the controller, per ADR 006's dual-check pattern). `resources/views/projects/show.blade.php` updated to display the member list (with role badges) and activity feed, and to gate the "Edit Project" link behind `@can('update', $project)`.
12. **A real regression, caught and fixed properly, not papered over**: switching `ProjectPolicy` to membership-based checks broke 4 of the existing 12 `ProjectTest` tests, because `ProjectFactory`-created projects had no corresponding `project_user` row (the factory only set `created_by`). Fixed by adding a `ProjectFactory::configure()` `afterCreating` hook that attaches the creator as Owner — framed explicitly as "factory-created projects should mirror the same invariant the real controller transaction guarantees," not as a test-only workaround.
13. **Tests**: `tests/Feature/ProjectMemberTest.php`, 10 new tests — members list visible to members/blocked for non-members; owner can add a member; a plain Member role CANNOT add another member (403); cannot add a user who's already a member (validation error, not a DB exception); owner can remove a member; **cannot remove the last owner** (asserts the membership row survives); **Manager role can update the project, Member role cannot** (403); **only Owner, not Manager, can delete** (403 for Manager); creating a project via the real HTTP endpoint produces both the `project_user` row AND the `activity_logs` row (verifies the transaction's actual effect, not just that it doesn't crash).
14. Wrote **ADR 008** (database transactions) documenting the project-creation transaction decision — written *as* the code was implemented.
15. Wrote `docs/backend-concepts/database-relationships.md` — covers all four relationship types now in use (`belongsTo`, `hasMany`, `belongsToMany`, `morphTo`/`morphMany`) with the approximate SQL each generates, per the spec's explicit requirement to explain the many-to-many pattern.
16. **Extensive manual HTTP verification**, learning from Phase 2.2's CSRF debugging detour — this time proactively cross-checked DB-stored session tokens against rendered-page tokens *before* assuming a bug, which correctly predicted several 419s were script artifacts, not app bugs, saving debugging time. Verified via real `curl` sessions (two separate users, separate cookie jars): registered Owner + Member users → Owner created a project → **DB-verified the transaction wrote all 3 rows** (project, `project_user` with role=owner, `activity_logs` entry) → added Member as a Manager → **Manager successfully updated the project (200/302, then DB-confirmed the rename)** → **Manager was correctly blocked (403) from deleting the project** → **attempted to remove the last Owner and confirmed both the 302 redirect-with-error AND that the membership row was NOT actually deleted** → re-triggered and captured the exact flashed error text ("Cannot remove the last owner of a project.") rendering correctly in the page. Cleaned up afterward: stopped the dev server, deleted all manually-created users/projects/memberships/activity logs from the dev database, confirmed 0 rows across all four tables.

Remaining for full Phase 2: Dashboard real aggregation queries (still a stub — now meaningfully implementable since Projects/Membership exist, though Tasks would make it more complete), Tasks CRUD (spec section 5 — now unblocked, can reference `project_user` for assignment), Comments (spec section 6), extending activity logging to Task/Comment events (infrastructure already exists from this phase — just needs more `ActivityLog::create()` call sites), further validation/error-handling hardening, filtering/sorting (Tasks needs `?status=&priority=&assignee=` per spec), seeders at spec's required volume, remaining ADRs (001–004, 007), remaining backend-concepts docs (eloquent.md — still deferred until Tasks exist for a proper N+1 demo — database-indexes.md, transactions.md as a standalone concept doc distinct from ADR 008, pagination.md, error-handling.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), remaining required screen (`/tasks/{id}`), failure experiments, final learning report, final assessment.

Verification:
- `php artisan migrate --pretend` then `--force` (both new migrations) → correct SQL, applied cleanly (VERIFIED)
- `php artisan test --filter=ProjectTest` → 8 passed / 4 failed initially (expected regression from Policy change), then 12 passed / 27 assertions after the `ProjectFactory` fix (VERIFIED, regression correctly diagnosed and fixed rather than ignored)
- `php artisan test --filter=ProjectMemberTest` → 10 passed, 24 assertions (VERIFIED)
- `php artisan test` (full suite) → 34 passed, 82 assertions (VERIFIED)
- `./vendor/bin/pint --test` → passed, no violations this time (VERIFIED)
- Manual curl-based verification (two real user sessions, DB cross-checks at each step) → transaction confirmed atomic (all 3 rows present), Manager role-based update confirmed working, Manager delete-block confirmed (403), last-owner-removal guard confirmed both at the HTTP layer (redirect+error) and the DB layer (row survives) (ALL VERIFIED)
- Dev database cleanup confirmed: 0 users, 0 projects, 0 project_user rows, 0 activity_logs rows after manual verification (VERIFIED)

Files changed:
- Added: `app/Enums/ProjectRole.php`
- Added: `database/migrations/2026_09_28_124829_create_project_user_table.php`, `2026_09_28_124830_create_activity_logs_table.php`
- Added: `app/Models/ActivityLog.php`
- Modified: `app/Models/Project.php` (added `members()`, `activities()`, `hasMember()`, `roleOf()`)
- Modified: `app/Models/User.php` (added `projects()`, `activities()`)
- Modified: `app/Policies/ProjectPolicy.php` (rewritten for membership-based rules)
- Modified: `app/Http/Controllers/ProjectController.php` (`index()` membership-scoped, `store()` transaction-wrapped, `show()` eager-loads members/activities, `update()` logs activity)
- Added: `app/Http/Requests/StoreProjectMemberRequest.php`
- Added: `app/Http/Controllers/ProjectMemberController.php`
- Modified: `routes/web.php` (added members routes)
- Added: `resources/views/projects/members.blade.php`
- Modified: `resources/views/projects/show.blade.php` (members list, activity feed, gated Edit link)
- Modified: `database/factories/ProjectFactory.php` (added `configure()`/`afterCreating` owner-membership hook)
- Added: `tests/Feature/ProjectMemberTest.php`
- Added: `docs/architecture/adr/008-database-transactions.md`
- Added: `docs/backend-concepts/database-relationships.md`

## Important Decisions

1. **Laravel 13 over an older LTS** — chosen because it's the latest stable major and PHP 8.5.11 (the verified machine PHP) is only supported starting Laravel 13 (Laravel 12 tops out at PHP 8.5 too, actually — 12 supports 8.2–8.5 and 13 supports 8.3–8.5 — both would technically work). Went with 13 per the spec's explicit target ("targeting Laravel 13 if it is still the latest stable compatible release"), and it was still latest stable and compatible. No ADR needed yet for this — will be captured implicitly in the general architecture docs during Phase 2, or given its own ADR if a future session judges it warrants one.
2. **Rejected Laravel Boost auto-install** — see Phase 1 item #3 above. Not reversed unless the user explicitly asks for it with its own justification.
3. **Dedicated non-superuser Postgres role, not the `postgres` superuser and not a shared role** — limits blast radius; documented in `docs/SETUP.md`.
4. **Did not change phpunit.xml's SQLite default in Phase 1** — deliberate scope discipline (Phase 1 = bootstrap only, no business features, and changing test DB strategy is really a Phase-9/testing-strategy decision, not a bootstrap concern). Tracked as an open item, not forgotten.
5. **Project moved into `taskflow/` subdirectory** — user's explicit request, done before any Phase 2 work started. See Phase 2.1 notes above.
6. **Hand-rolled authentication, no starter kit** — see ADR 005.
7. **Switched test suite from SQLite to PostgreSQL, with a dedicated test database** — see ADR 010. Resolved earlier than originally planned (was going to wait until Phase 9) because it became a hard blocker the moment any test tried to touch the database, not just a stylistic preference.
8. ~~`ProjectPolicy` uses an interim "creator only" rule, not real membership~~ **RESOLVED in Phase 2.3** — `ProjectPolicy` now checks real `project_user` membership/roles via `Project::hasMember()`/`roleOf()`.
9. **Offset pagination (`paginate()`) over cursor pagination** for all list views — see ADR 009.
10. **N+1 avoided proactively in `ProjectController::index()`** (`with('owner')`) rather than shipped naively and fixed later — the full before/after N+1 *demonstration* doc (`docs/backend-concepts/eloquent.md`) is still deferred until Tasks exist (richer example with two relations), but the production code itself was written correctly from the start.
11. **`project_user` uses `cascadeOnDelete()` on both FKs, while `projects.created_by` uses `restrictOnDelete()`** — a deliberate, documented divergence: membership rows have no independent meaning once either side is gone, but project ownership records should never silently vanish. See the migration's inline comments and `docs/backend-concepts/database-relationships.md`.
12. **`activity_logs.user_id` uses `nullOnDelete()`, not cascade or restrict** — an audit trail should survive the actor's account being deleted (the log entry stays, just with a null user reference) rather than being deleted itself or blocking account deletion.
13. **Transaction for project creation deferred from Phase 2.2 to 2.3, implemented once `project_user`/`activity_logs` existed** — rather than either skipping it or awkwardly pre-creating those tables early. See ADR 008.
14. **Last-owner-removal guard implemented as a controller-level business rule, not a Form Request validation rule** — because it depends on querying the state of OTHER membership rows (how many Owners currently exist), not just the shape of the current request. Matches the validation-vs-business-rule distinction in `docs/backend-concepts/validation.md`.
15. **`ProjectFactory` updated to auto-attach creator as Owner via `afterCreating`** — after discovering this was a real, necessary fix (not a nice-to-have) once `ProjectPolicy` became membership-based; framed as "factories should produce the same valid invariant the real transaction guarantees," not a test-only hack.

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

--- Git: first commit + push ---
git add . (95 files) → reviewed via `git add -n .` dry run first, confirmed no vendor/node_modules/storage-cache/.env staged
git diff --cached | grep -i "CHANGE_ME\|DB_PASSWORD"   → only placeholder/env()/prose matches, no actual secret value staged
git commit (root commit, "Bootstrap TaskFlow...")   → 95 files, 17239 insertions
ssh -T git@github.com   → "Hi mrahmanruet16! You've successfully authenticated" — confirmed BEFORE attempting push
git remote add origin git@github.com:mrahmanruet16/taskflow.git
git push -u origin main   → success, new branch main -> main on GitHub

--- Phase 2.3 (Project Membership) ---
php artisan migrate --pretend / --force (project_user, activity_logs)   → correct SQL, applied cleanly; caught and removed one redundant manual index (nullableMorphs() already creates it) before migrating
php artisan test --filter=ProjectTest (immediately after ProjectPolicy rewrite, BEFORE factory fix)   → 8 passed / 4 FAILED (expected regression, correctly diagnosed as a consequence of switching to membership-based auth, not a bug)
php artisan test --filter=ProjectTest (after ProjectFactory afterCreating fix)   → 12 passed, 27 assertions
php artisan test --filter=ProjectMemberTest   → 10 passed, 24 assertions
php artisan test (full suite)   → 34 passed, 82 assertions
./vendor/bin/pint --test   → passed, no violations
Manual curl verification (two real user sessions, owner.jar + member.jar):
  register owner + register member   → both 302
  create project as owner   → 302; psql cross-check: project + project_user(role=owner) + activity_logs row ALL present (transaction verified atomic)
  add member@example.com as Manager via /projects/{id}/members   → 302; psql confirms project_user row with role=manager
  GET /projects/{id}/edit as Manager   → 200 (before submitting, decoded DB session payload and compared to rendered CSRF token — exact match, confirming no app bug before proceeding)
  PUT /projects/{id} as Manager (rename)   → 302; psql confirms project.name actually changed
  DELETE /projects/{id} as Manager   → 403 (Owner-only delete rule enforced)
  DELETE /projects/{id}/members/{ownerId} as Owner (removing the LAST owner)   → 302 redirect-with-error; psql confirms owner's project_user row WAS NOT removed; re-fetched page and captured exact flashed message "Cannot remove the last owner of a project."
  cleanup: killed dev server, DELETE FROM project_user/activity_logs/projects/users WHERE ...   → confirmed 0/0/0/0 rows remain in dev database
```

## Current Blocker

None. Phase 2.3 (Project Membership) is complete and verified, pending only the commit/push described in NEXT ACTION step 1.

## NEXT ACTION

1. **Immediate**: commit this Phase 2.3 work (Membership, transactions, activity logging — everything listed under "Files changed" in the Phase 2.3 section above) and `git push origin main`. Follow the same review discipline used for the first commit: `git add -n .` dry run first, `git diff --cached | grep -i password` before committing, confirm `git push` actually succeeds (check for `[new branch]`/`main -> main` or equivalent in the output — don't assume success without reading it). **A future session must verify via `git log` / `git status` whether this push actually happened** — do not trust this file's claim alone if it looks stale (e.g. if `Last Updated` above is more than a session old and no confirming git output is visible in the conversation).
2. Recommended next sub-phase after committing: **Tasks** (spec section 5) — now fully unblocked:
   - Migration: `tasks` table (id, project_id FK cascadeOnDelete, assigned_to FK to users nullable, created_by FK, title, description, status enum: todo/in_progress/completed/cancelled, priority enum: low/medium/high/urgent, due_date, timestamps). Index reasoning to document: project_id (every task list is scoped to a project), status/priority/assigned_to (spec explicitly requires filtering by all three).
   - `Task` model + `TaskStatus`/`TaskPriority` enums (same backed-enum pattern as `ProjectStatus`/`ProjectRole`).
   - `TaskPolicy` — likely "any project member can view tasks; Owner/Manager/assignee can update; Owner/Manager can delete" — needs a real decision, not just copy-pasted from ProjectPolicy.
   - `StoreTaskRequest`/`UpdateTaskRequest`, `TaskController`, views including the required `/tasks/{id}` screen.
   - **This is the natural point to finally write `docs/backend-concepts/eloquent.md` with a real N+1 demonstration** — `Project::all()` then accessing `->tasks` per-project (N+1) vs `Project::with('tasks')->get()` (fixed), now that Tasks actually exist to demonstrate this with.
   - Filtering/sorting via query params (`?status=&priority=&assignee=`, `?sort=due_date`) — the spec's explicit requirement, not yet implemented anywhere in the app (Projects only has pagination, not filtering).
   - Extend activity logging to Task events (created/updated/assigned/status changed) — infrastructure already exists, just add more `ActivityLog::create()` call sites following the same pattern as Project/Member events.
   - Dashboard's real aggregation queries become meaningful once Tasks exist (Projects/Tasks/Completed/Pending/Overdue counts) — do this either right before or right after Tasks CRUD.
3. Still pending, no change in scope since last checkpoint beyond what Phase 2.3 added: ADRs 001–004, 007 (005/006/008/009/010 now exist), most `docs/backend-concepts/*.md` files (eloquent.md, database-indexes.md, transactions.md-as-standalone-concept, pagination.md, error-handling.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), all Tasks/Comments code, one remaining required screen (`/tasks/{id}`), seeders at spec's required volume (10+ users, 5+ projects, 50+ tasks, 100+ comments — factories exist for User/Project but no seeder invokes them at volume yet, and no TaskFactory/CommentFactory exist yet), failure experiments, final learning report, final assessment.
