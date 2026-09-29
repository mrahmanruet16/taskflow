# Project State

## Current Phase

Phase: 2 — Implement the learning project (sub-phase 2.10: Database seeding + demo accounts)
Status: Sub-phases 2.1–2.10 COMPLETE and VERIFIED. Dev database now populated at spec-required volume with 4 named demo accounts. Failure experiments, manual-verification checklist, final report/assessment NOT started.
Last Updated: 2026-09-29

## Remote

Pushed to `git@github.com:mrahmanruet16/taskflow.git`, branch `main`. Sixteen commits pushed and confirmed (`git push` output showed `dfeaf28..3e42c69  main -> main`):
- `84a7bd8` — root commit, covers Phases 1 + 2.1 (Authentication) + 2.2 (Projects CRUD)
- `3c7cb50` — Phase 2.3 (Project Membership, transactions, activity logging)
- `2d6eb19` — PROJECT-STATE.md correction after confirming the 2.3 push
- `940446b` — Phase 2.4 (Tasks, filtering/sorting, eloquent.md)
- `0f08e98` — PROJECT-STATE.md correction after confirming the 2.4 push
- `43f984e` — Phase 2.5 (Comments)
- `2868c3d` — PROJECT-STATE.md correction after confirming the 2.5 push
- `7de4bd0` — Phase 2.6 (Dashboard real aggregation)
- `0c20e87` — PROJECT-STATE.md correction after confirming the 2.6 push
- `53268e4` — Phase 2.7 (error pages, APP_DEBUG verification)
- `fa43d79` — PROJECT-STATE.md correction after confirming the 2.7 push (also fixed a stray duplicate line)
- `c68bcd4` — Phase 2.8 (foundational ADRs 001–004, 007)
- `0e5a0d6` — PROJECT-STATE.md correction after confirming the 2.8 push
- `3b1b775` — Phase 2.9 (real logging + remaining 8 backend-concepts docs, including the request-lifecycle.md fix)
- `dfeaf28` — PROJECT-STATE.md correction after confirming the 2.9 push
- `3e42c69` — Phase 2.10 (database seeding + demo accounts)

Working tree clean as of this checkpoint. A future session should still re-verify with `git log`/`git status` rather than trusting this note if significant time has passed.

## Important: Dev Database Now Contains Real Seed Data

Starting this sub-phase, `laravel_learning_dev` is intentionally populated (14 users, 6 projects, 60 tasks, 120 comments, 66 activity logs — see `database/seeders/DatabaseSeeder.php`) and this data is meant to PERSIST, unlike every prior phase's manual-verification data, which was always deliberately created and then cleaned up. **A future session must NOT reflexively `DELETE FROM` the dev database's tables as part of "cleanup"** the way every previous phase's manual verification did — check whether new manual-verification data is being added on top of the seed data (in which case, clean up only the newly-added rows, not the seeded baseline) before running any destructive query against `laravel_learning_dev`.

Working tree clean as of this checkpoint. A future session should still re-verify with `git log`/`git status` rather than trusting this note if significant time has passed.

Working tree clean as of this checkpoint. A future session should still re-verify with `git log`/`git status` rather than trusting this note if significant time has passed.

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
- [x] Phase 5 — Tasks (sub-phase 2.4, complete — CRUD, TaskPolicy with assignee-can-update-own-task rule, filtering/sorting, activity logging correctly scoped to the Task subject)
- [x] Phase 6 — Comments and activity logging (sub-phase 2.5, complete — CommentPolicy deliberately author-only for edit/delete, no Owner/Manager override, verified even against a project Manager over real HTTP; "Comment created"/"Comment deleted" activity logged, matching spec's exact event list, no "Comment edited" logging since spec doesn't list it)
- [~] Phase 7 — Validation, errors, transactions and backend hardening (validation: done since Phase 2.2/2.4; transactions: done since Phase 2.3, ADR 008; custom 404/403/500 error pages + empirically-verified `APP_DEBUG=false` behavior: done, sub-phase 2.7. Remaining: no other hardening gaps identified yet — revisit if failure experiments surface one)
- [x] Phase 8 — Pagination, filtering and sorting (Projects: pagination since 2.2; Tasks: pagination + status/priority/assignee filtering + due_date/priority/created_at sorting, since 2.4 — matches the spec's exact `?status=&priority=&assignee=` example)
- [x] Dashboard (spec section 2, not separately numbered in this template) — sub-phase 2.6, complete: real aggregate COUNT() queries (Projects/Tasks/Completed/Pending/Overdue), verified as 5 flat queries regardless of task volume (measured via tinker, same rigor as the eloquent.md N+1 demonstration), verified correct over real HTTP including the completed-but-overdue-due-date edge case (must NOT count as overdue)
- [x] Database Seeding + Demo Accounts (spec's explicit "Database Seeding"/"Factories" sections, not separately numbered in this template) — sub-phase 2.10, complete: 14 users/6 projects/60 tasks/120 comments/66 activity logs, exceeding all spec-required minimums (10+/5+/50+/100+); 4 named demo accounts (admin/manager/member/viewer @example.test, password "password") each a member of every seeded project with their intended role; verified over real HTTP (logged in as admin@example.test, dashboard counts cross-checked exactly against direct DB queries)
- [ ] Phase 9 — Tests
- [ ] Phase 10 — UI/browser verification
- [ ] Phase 11 — Failure experiments
- [x] Phase 12 — Documentation and ADR completion (ADRs: all 10 spec-required ADRs exist, sub-phase 2.8. Backend-concepts docs: all 14 spec-required docs now exist, sub-phase 2.9 — including `docs/architecture/request-lifecycle.md`, discovered missing and written this same sub-phase after finding 4 other docs falsely referenced it as "already documented." Remaining: `docs/architecture/system-overview.md`, `database-design.md`, `architecture-decisions.md`, `docs/database/database-design.md`, `docs/security/security-model.md`, `docs/testing/testing-strategy.md` are named in the spec's file-tree diagram but not the explicit required-list prose — treated as lower priority than the explicitly-named docs, not yet written)
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

### Phase 2.4 — Tasks (CRUD, filtering/sorting, activity logging)
Status: Complete, verified (automated tests + manual HTTP verification including a real, measured N+1 demonstration)

Completed:
1. **`TaskStatus`/`TaskPriority` backed enums** — same pattern as `ProjectStatus`/`ProjectRole`. `TaskPriority::weight()` added specifically to support future numeric priority sorting (a string column sorted alphabetically would put "high" before "low" before "medium" before "urgent," which is meaningless) — not yet wired into the sort query since `ORDER BY priority` on the string column already gives a stable (if not numerically meaningful) order for now; documented as available for when it's needed.
2. **`tasks` migration**: `project_id` (`cascadeOnDelete()` — a task has no meaning outside its project, unlike `projects.created_by`'s restrict), `assigned_to` (nullable, `nullOnDelete()` — a task can be unassigned, and losing an assignee's account should unassign rather than delete/block), `created_by` (`restrictOnDelete()`, matching `projects.created_by`'s reasoning), `title`, `description`, `status`/`priority` (string, enum-cast), `due_date`. Indexes on `project_id`, `status`, `priority`, `assigned_to` — the latter three exist specifically because the spec requires filtering on all three.
3. **`Task` model**: `project()`, `creator()`, `assignee()` (nullable — Blade/controller code must null-check, e.g. `$task->assignee?->name`), `activities()`, plus an `isOverdue(): bool` helper (due date in the past AND not completed/cancelled) intended for the Dashboard's "Overdue Tasks" count in a future sub-phase.
4. **`Project::tasks()` and `User::tasksCreated()`/`assignedTasks()`** added, completing the relationship set from the spec's explicit list.
5. **`TaskPolicy`**: `view`/`viewAny` — any project member; `create` — Owner/Manager/Member (Viewer excluded — can see everything, can't add new work); `update` — Owner/Manager OR **the task's own assignee** (a deliberate, documented divergence from Project-level rules: the spec's "Change Status" action needs to be available to whoever the task is actually assigned to, not just project leads); `delete` — Owner/Manager only, NOT extended to the assignee (finishing your own work means changing its status, not deleting the record).
6. **`StoreTaskRequest`/`UpdateTaskRequest`**: validate `assigned_to` via a closure rule checking the target user is actually a member of the task's project — prevents assigning a task to someone who (per `ProjectPolicy`) couldn't even see the project it lives in.
7. **`TaskController`**: `index()` implements the spec's exact required filtering (`?status=&priority=&assignee=`) and sorting (`?sort=due_date|priority`, default `created_at`) — **sort column is whitelisted via a `match` expression, never string-interpolated into `ORDER BY`**, explicitly documented as the SQL-injection-prevention reason for that choice. `create`/`store` nested under `/projects/{project}/tasks/...`; `show`/`edit`/`update`/`destroy` NOT nested (bare `/tasks/{task}`), matching the spec's exact required screen URL and reflecting that a task is independently addressable once it exists.
8. **A real bug caught and fixed before it shipped, not after**: initially logged all task lifecycle events (`created`, `status changed`, `assigned`) against the **Project** as the activity subject. Caught during view-writing (not during testing) that `tasks/show.blade.php` reads `$task->activities` — a `morphMany` scoped to `subject_type = Task` — which would have silently rendered empty forever, since no log entry was ever created with that subject type. Fixed by changing task lifecycle events to log against the **Task** itself; task *deletion* specifically stays logged against the Project (the task won't exist afterward to view its own activity).
9. **Activity log message for status changes matches the spec's exact example format** — verified via manual HTTP test: `"Task Tester changed task ... status from "To Do" to "In Progress"."`, structurally identical to the spec's `"John changed Task #15 status from 'todo' to 'completed'."`
10. **`ProjectController::show()` updated** to eager-load `tasks.assignee` (documented why: the project detail page's task table prints `$task->assignee?->name` per row — same N+1-avoidance pattern as `with('owner')` in `index()`).
11. **Project show/nav updated**: "Create Task" button (gated behind `@can('create', [Task::class, $project])`), task list table on the project detail page, "Tasks" link added to the main nav (matching the spec's UI mockup: Dashboard | Projects | Tasks | Logout).
12. **Tests**: `tests/Feature/TaskTest.php`, 12 tests — guest redirected from `/tasks`; member can create a task (verifies the activity log lands on the Task subject, catching a regression if the bug from item 8 were ever reintroduced); **Viewer role blocked from creating** (403); non-member blocked; **assigning to a non-member is rejected as a validation error**, not a raw DB exception; member can view a task, non-member cannot (403); **assignee can change their own task's status without any management role**; **a different member who is NOT the assignee cannot update the task** (403); **only Owner/Manager, not the assignee alone, can delete**; index correctly filters by status; index correctly scopes to only the current user's project memberships (cross-project isolation, same pattern as the Project tests).
13. **`docs/backend-concepts/eloquent.md` finally written** (deliberately deferred since Phase 2.2 specifically until Tasks existed) — contains a REAL, MEASURED N+1 demonstration, not a hypothetical: seeded 3 projects with 2 tasks each via `php artisan tinker`, ran `DB::enableQueryLog()` before and after adding `with(['tasks', 'owner'])`, captured the actual SQL and exact query counts (7 queries without eager loading vs. 3 with, flat regardless of row count), then deleted the seed data afterward. The doc includes the literal captured SQL, not paraphrased/invented queries.
14. **Extensive manual HTTP verification**, same rigor as prior phases: register → create project → **hit the same CSRF 419 red herring pattern a third time**, immediately cross-checked the DB-stored session token against the rendered page token (exact match, confirmed scripting artifact not app bug) before proceeding → create task via `/projects/{id}/tasks` → **DB-verified both the task row AND its Task-subject activity log entry** → view task show page, confirmed title and activity both render → change status via the quick-status form → **DB-confirmed the status actually changed AND the activity log message text matched the spec's exact format** → filtered `/tasks?status=in_progress` (task appears) and `?status=completed` (correctly absent, "No tasks match these filters" shown). Cleaned up: stopped dev server, deleted all manually-created data, confirmed 0 rows across users/projects/tasks/activity_logs in the dev database.

Remaining for full Phase 2: Dashboard real aggregation queries (still a stub — genuinely well-motivated now that Projects/Tasks both exist: Projects/Tasks/Completed/Pending/Overdue counts are all real, meaningful queries at this point), Comments (spec section 6 — the one remaining CRUD entity), further validation/error-handling hardening (custom error pages per spec: 404/403/500), remaining ADRs (001–004, 007), remaining backend-concepts docs (database-indexes.md, transactions.md as a standalone concept doc distinct from ADR 008, pagination.md, error-handling.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), seeders at spec's required volume (10+ users, 5+ projects, 50+ tasks, 100+ comments — all three factories now exist: User, Project, Task; no CommentFactory yet since Comments isn't built; no seeder invokes any of them at volume yet), failure experiments, final learning report, final assessment.

Verification:
- `php artisan migrate --pretend` then `--force` (tasks table) → correct SQL, applied cleanly (VERIFIED)
- `php artisan test --filter=TaskTest` → 12 passed, 29 assertions (VERIFIED)
- `php artisan test` (full suite) → 46 passed, 111 assertions (VERIFIED)
- `./vendor/bin/pint --test` → passed, no violations (VERIFIED)
- Empirical N+1 measurement via `tinker` + `DB::enableQueryLog()` → 7 queries without eager loading, 3 with, for 3 seeded projects × 2 tasks each (VERIFIED, real captured SQL in `docs/backend-concepts/eloquent.md`)
- Manual curl-based verification: project → task creation → DB-verified task row + Task-subject activity log entry → task show page renders correctly → status change → DB-verified status AND activity message text matches spec's exact format → filtering by status confirmed both directions (present when matching, absent + correct empty-state message when not) (ALL VERIFIED)
- Dev database cleanup confirmed twice: once after the N+1 measurement seed data, once after the full manual HTTP flow — 0 rows across all tables both times (VERIFIED)

Files changed:
- Added: `app/Enums/TaskStatus.php`, `app/Enums/TaskPriority.php`
- Added: `database/migrations/2026_09_28_130206_create_tasks_table.php`
- Added: `app/Models/Task.php`
- Modified: `app/Models/Project.php` (added `tasks()`)
- Modified: `app/Models/User.php` (added `tasksCreated()`, `assignedTasks()`; cleaned up a stale comment on `projects()`)
- Added: `app/Policies/TaskPolicy.php`
- Added: `app/Http/Requests/StoreTaskRequest.php`, `UpdateTaskRequest.php`
- Added: `app/Http/Controllers/TaskController.php`
- Modified: `app/Http/Controllers/ProjectController.php` (`show()` eager-loads `tasks.assignee`)
- Modified: `routes/web.php` (added task routes)
- Added: `resources/views/tasks/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `_form.blade.php`
- Modified: `resources/views/projects/show.blade.php` (task list, Create Task button)
- Modified: `resources/views/components/layout.blade.php` (added Projects/Tasks nav links)
- Added: `database/factories/TaskFactory.php`
- Added: `tests/Feature/TaskTest.php`
- Added: `docs/backend-concepts/eloquent.md`

### Phase 2.5 — Comments
Status: Complete, verified (automated tests + manual HTTP verification proving CommentPolicy's author-only rule holds even against a project Manager)

Completed:
1. **`comments` migration**: `task_id` (`cascadeOnDelete()`, same reasoning as `tasks.project_id`), `user_id` (nullable, `nullOnDelete()` — matches `activity_logs.user_id`'s reasoning: discussion content should survive the author's account being deleted), `body` (text). Only one index (`task_id`) — deliberately NOT adding speculative indexes, per the project's "every non-obvious index must have a reason" rule; no other query pattern exists for comments in this app.
2. **`Comment` model**: `task()`, `user()` (nullable — documented that Blade must null-check it). `Task::comments()` added as `oldest()`-ordered (chronological conversation), explicitly contrasted in a code comment against `activities()`'s `latest()`-ordering (reverse-chronological log) — these are different reading patterns for different kinds of content, not an oversight.
3. **`CommentPolicy` — the one deliberately DIFFERENT authorization pattern in this app**: `create` follows the same Owner/Manager/Member-not-Viewer rule as `TaskPolicy::create`, but `update`/`delete` are **author-only, with NO Owner/Manager override** — matching the spec's literal wording ("edit their own comment," "delete their own comment"). This is a genuine, deliberate divergence from `TaskPolicy` (where Owner/Manager can act on any task regardless of who created it) and from `ProjectPolicy` (same pattern) — documented explicitly in the policy file's comments as "comment ownership follows the individual, not the project hierarchy," not left as an unexplained inconsistency.
4. **`StoreCommentRequest`/`UpdateCommentRequest`**: simple `body` validation (required, max 2000 chars) — no cross-table closure rules needed here, unlike `StoreTaskRequest`'s assignee check, since a comment's only "who" question (the author) is always the authenticated user, not a value submitted in the request.
5. **`CommentController`**: `store`/`edit`/`update`/`destroy` only — no `index`/`show`/`create` routes, since comments have no standalone page in this app; they render inline on the task show page. This was decided and recorded back in the Phase 2.4 NEXT ACTION notes before this phase started, and implemented exactly as planned.
6. **Activity logging matches the spec's exact event list**: "Comment created" and "Comment deleted" are logged (against the Task subject, joining the task's unified activity feed alongside its own lifecycle events); **"Comment edited" is deliberately NOT logged**, since the spec's explicit activity event list only names created/deleted for comments — not adding unrequested logging.
7. **`tasks/show.blade.php` updated**: placeholder text replaced with the real comment list (author name, timestamp via `diffForHumans()`, body), each comment's Edit/Delete actions gated behind `@can('update'/'delete', $comment)` (UI convenience only — the real enforcement is the Policy, per ADR 006's dual-check pattern, and this phase's manual verification specifically proved the backend check holds even when the UI check would have been bypassed). Add-comment form gated behind `@can('create', [Comment::class, $task])`.
8. **`TaskController::show()` updated** to eager-load `comments.user` (same N+1-avoidance pattern as every other list-of-related-models render in this app).
9. **Tests**: `tests/Feature/CommentTest.php`, 9 tests — member can add a comment (verifies both the comment row and its Task-subject activity log entry); Viewer blocked (403); non-member blocked (403); empty body rejected; **author can edit their own comment**; **a project Manager — who has full authority over the task itself — CANNOT edit another user's comment** (403, the concrete test proving the CommentPolicy/TaskPolicy divergence actually holds, not just documented); author can delete their own comment (verifies the "Comment deleted" activity log entry); **project owner cannot delete another user's comment** (403); comments render on the task show page.
10. **Extensive manual HTTP verification, specifically targeting the CommentPolicy divergence**: registered two users, created a project, made the second user a project **Manager** (full task authority) via the real HTTP members endpoint, had the first user post a comment, then confirmed via real HTTP requests that the Manager gets **403 on both `GET /comments/{id}/edit` and `DELETE /comments/{id}`** despite their elevated project role — then confirmed the actual comment author successfully edits it (200 on edit page, 302 + DB-verified body change on submit). This was the one authorization rule in the entire app most likely to have a subtle bug (an "Owner/Manager can override" check accidentally copied from `TaskPolicy` would have silently broken this), so it received the most targeted manual verification of any phase so far. Cleaned up afterward: stopped dev server, deleted all manually-created data, confirmed 0 rows across users/projects/tasks/comments/activity_logs.

Remaining for full Phase 2: Dashboard real aggregation queries (still a stub — all underlying data now exists: Projects/Tasks/Completed/Pending/Overdue counts are fully real, meaningful queries at this point, this is genuinely the next natural piece), backend hardening (custom 404/403/500 error pages per spec — none exist yet, Laravel's default error pages are still in use), remaining ADRs (001–004, 007), remaining backend-concepts docs (database-indexes.md, transactions.md-as-standalone-concept, pagination.md, error-handling.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), seeders at spec's required volume (all four factories now exist — User, Project, Task, Comment — but no seeder invokes any of them at the spec's required volume of 10+ users/5+ projects/50+ tasks/100+ comments yet), failure experiments, final learning report, final assessment.

Verification:
- `php artisan migrate --pretend` then `--force` (comments table) → correct SQL, applied cleanly (VERIFIED)
- `php artisan test --filter=CommentTest` → 9 passed, 22 assertions (VERIFIED)
- `php artisan test` (full suite) → 55 passed, 133 assertions (VERIFIED)
- `./vendor/bin/pint --test` → passed, no violations on first run (VERIFIED)
- Manual curl-based verification: full chain (register → project → task → comment) → DB-verified comment row AND its Task-subject activity log entry → comment renders on task page with correct author name → **Manager blocked (403) from both editing and deleting another user's comment, despite having full task-level authority** → **actual author successfully edits their own comment (200/302, DB-confirmed body change)** (ALL VERIFIED)
- Dev database cleanup confirmed: 0 rows across users/projects/tasks/comments/activity_logs after manual verification (VERIFIED)

Files changed:
- Added: `database/migrations/2026_09_28_131637_create_comments_table.php`
- Added: `app/Models/Comment.php`
- Modified: `app/Models/Task.php` (added `comments()`)
- Modified: `app/Models/User.php` (added `comments()`)
- Added: `app/Policies/CommentPolicy.php`
- Added: `app/Http/Requests/StoreCommentRequest.php`, `UpdateCommentRequest.php`
- Added: `app/Http/Controllers/CommentController.php`
- Modified: `app/Http/Controllers/TaskController.php` (`show()` eager-loads `comments.user`)
- Modified: `routes/web.php` (added comment routes)
- Added: `resources/views/comments/edit.blade.php`
- Modified: `resources/views/tasks/show.blade.php` (real comment list + add-comment form, replacing placeholder)
- Added: `database/factories/CommentFactory.php`
- Added: `tests/Feature/CommentTest.php`

### Phase 2.6 — Dashboard (real aggregation queries)
Status: Complete, verified (automated tests with an edge-case assertion + measured query-count verification + manual HTTP verification)

Completed:
1. **`DashboardController::index()` rewritten** from the Phase 2.1 stub to 5 real aggregate queries, all scoped to `Auth::user()->projects()` (membership, matching every other list view in the app): Projects (count of member project IDs), Tasks (count across those projects), Completed, Pending (`whereNotIn('status', [Completed, Cancelled])` — explicitly NOT just "not completed," since a cancelled task isn't meaningfully "pending" either), Overdue (`due_date < now() AND status NOT IN (Completed, Cancelled)` — the same logic as `Task::isOverdue()` from Phase 2.4, expressed as a query rather than a per-row PHP check since this needs to be a COUNT, not a loaded collection).
2. **Explicit `clone` before each count()**, with a documented reason: reusing the same query builder instance across multiple `->where(...)->count()` calls would accumulate `WHERE` clauses onto one query instead of running independent ones — a real, non-obvious Eloquent gotcha worth the comment.
3. **Deliberately used `count()` SQL aggregates, not `Collection::count()` on loaded models** — the dashboard loads on every login, making it exactly the page where an accidental "load everything, then count in PHP" pattern would be most costly at scale. Documented as a variant of the N+1 avoidance principle from `docs/backend-concepts/eloquent.md`.
4. **`resources/views/dashboard/index.blade.php` rewritten** with the 5 stat cards matching the spec's exact required list (Projects / Tasks / Completed Tasks / Pending Tasks / Overdue Tasks) plus the already-present current-user display.
5. **Tests**: `tests/Feature/DashboardTest.php`, 2 tests. The first asserts exact counts against `$response->viewData(null)` (the actual PHP data passed to the view) rather than fragile HTML string matching — a first draft of this test used `assertSee('1</div>')`-style assertions and was caught and rejected during writing as unreliable, since multiple legitimately-different stats could coincidentally share the same numeric value and match the same substring; rewritten to assert on precise typed data instead. This test specifically includes a deliberate edge case: a task with a past `due_date` but `status = Completed` — asserts it does NOT count as overdue, proving the "AND status NOT IN (...)" clause actually does its job rather than just checking date. The second test confirms membership-scoping (a task in a project the user is NOT a member of must not be counted), mirroring the same cross-project-isolation check used throughout the Task/Project test suites.
6. **Measured, not assumed, query efficiency**: seeded 20 tasks via `tinker`, ran `DB::enableQueryLog()` around a direct `DashboardController::index()` call, confirmed exactly 5 real dashboard queries (1 project-ID lookup + 4 `COUNT(*)` aggregates) — flat regardless of task volume, same empirical rigor as the `eloquent.md` N+1 demonstration. Captured and reviewed the actual SQL (all 4 count queries correctly filter on `project_id IN (...)`, using the same index from the Phase 2.4 `tasks` migration). Cleaned up the seed data afterward.
7. **Manual HTTP verification with two data states**: checked the dashboard immediately after registration (0 projects, 0 tasks, all 5 stats correctly showing 0) — this matters because an aggregate query bug involving an empty membership set (e.g. `WHERE project_id IN ()`, which is invalid SQL in some contexts) would only surface with zero data, not with data present. Then created 1 project + 1 overdue task + 1 completed task via real HTTP requests and confirmed the dashboard updated to the exact expected counts (1/2/1/1/1 — note the overdue task correctly counts toward BOTH "Pending" and "Overdue," since an incomplete overdue task is still pending work, not a separate mutually-exclusive category). Cleaned up afterward: stopped dev server, deleted all manually-created data, confirmed 0 rows remain.

Remaining for full Phase 2: backend hardening (custom `404`/`403`/`500` error pages per spec — Laravel's default pages are still in use; the spec also asks to verify no stack traces leak when `APP_DEBUG=false`, not yet explicitly checked), remaining ADRs (001–004, 007 — the general-architecture ones deferred since Phase 1/2 as "implicit," never actually written), remaining backend-concepts docs (database-indexes.md, transactions.md-as-standalone-concept, pagination.md, error-handling.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), seeders at the spec's required volume with named demo accounts (admin@example.test / manager@example.test / member@example.test / viewer@example.test), failure experiments (5 numbered experiments per spec), final learning report, final backend assessment.

Verification:
- `php artisan test --filter=DashboardTest` → 2 passed, 8 assertions (VERIFIED, including the completed-but-overdue-date edge case)
- `php artisan test` (full suite) → 57 passed, 141 assertions (VERIFIED)
- `./vendor/bin/pint --test` → passed, no violations (VERIFIED)
- Empirical query-count measurement (tinker, 20 seeded tasks) → exactly 5 dashboard queries, captured and reviewed actual SQL, confirmed flat regardless of task volume (VERIFIED)
- Manual curl-based verification: fresh user shows all-zero dashboard (0/0/0/0/0) → after creating 1 project + 2 tasks (1 overdue, 1 completed), dashboard correctly shows 1/2/1/1/1 (VERIFIED)
- Dev database cleanup confirmed twice (once after the tinker measurement, once after the manual HTTP flow) — 0 rows across all tables both times (VERIFIED)

Files changed:
- Modified: `app/Http/Controllers/DashboardController.php` (real aggregation queries, replacing the Phase 2.1 stub)
- Modified: `resources/views/dashboard/index.blade.php` (5 stat cards, replacing placeholder text)
- Added: `tests/Feature/DashboardTest.php`

### Phase 2.7 — Error-page hardening
Status: Complete, verified (automated tests + manual HTTP verification against a real running server in both `APP_DEBUG` states)

Completed:
1. **Custom `resources/views/errors/404.blade.php`, `403.blade.php`, `500.blade.php`** — Laravel auto-discovers these by HTTP status code, no registration needed.
2. **The 500 view deliberately does NOT use `<x-layout>`** — a documented, deliberate divergence from every other view in the app. Reasoning captured in a comment in the file itself: a 500 page must render correctly even when something else is broken (session, auth, database), and `<x-layout>` calls `@auth`/`route()`, which could themselves fail if the underlying breakage is in auth or routing. The 500 view has zero dependencies by design.
3. **Tests**: `tests/Feature/ErrorPagesTest.php`, 4 tests — undefined route → custom 404; route-model-binding miss (`GET /projects/999999`) → also a custom 404, via a different mechanism (`ModelNotFoundException` rather than no matching route, both converge on the same view); unauthorized access → custom 403; and the most important one — **`APP_DEBUG=false` does not leak the exception message or class name**, verified by actually throwing a real `RuntimeException` through the normal HTTP pipeline (a temporary route registered inside the test itself, not a persisted app route) and asserting the response body contains neither the message nor "RuntimeException." Caught and fixed one test bug while writing this: `assertSee()` HTML-escapes its needle by default (assuming Blade `{{ }}` output), which doesn't match this app's error pages' plain unescaped static text — fixed by passing `false` to disable escaping, with the reason documented inline.
4. **Manual HTTP verification of the exact spec requirement, not just the automated test**: temporarily added a route that throws (`/__debug-trigger-500`), started `php artisan serve`, hit it with `APP_DEBUG=true` — confirmed Laravel's detailed debug page shows the exception message/class **6 times** in the response. Then edited `.env` to `APP_DEBUG=false` (no server restart needed — `php artisan serve`'s built-in PHP server re-reads `.env` per request, confirmed empirically) and hit the same route again — confirmed the custom 500 page renders with the exception message/class appearing **zero** times, only the generic "Something went wrong" text. **Both the temporary route and the `.env` change were then cleanly reverted** — confirmed via `git diff routes/web.php` (zero diff) and re-reading `.env` (`APP_DEBUG=true` restored) before proceeding to anything else.
5. Wrote `docs/backend-concepts/error-handling.md` — covers the 404/403/500 mechanism, the `APP_DEBUG` behavior with the actual measured verification numbers (6 leaked mentions vs. 0), and why the 500 view's lack of `<x-layout>` is a deliberate choice.

Remaining for full Phase 2: remaining foundational ADRs (001–004 — never actually written, treated as "implicit" since Phase 1), remaining backend-concepts docs (database-indexes.md, transactions.md as a standalone concept doc distinct from ADR 008, pagination.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), a real `DatabaseSeeder` at the spec's required volume with named demo accounts, the 5 numbered failure experiments, `docs/testing/manual-verification.md`, final learning report, final backend assessment.

Verification:
- `php artisan test --filter=ErrorPagesTest` → 4 passed, 13 assertions (VERIFIED)
- `php artisan test` (full suite) → 61 passed, 154 assertions (VERIFIED)
- `./vendor/bin/pint --test` → passed after 1 auto-fix (import ordering in the new test file) (VERIFIED)
- Manual HTTP verification with `APP_DEBUG=true`: exception details appeared 6 times in the response (VERIFIED)
- Manual HTTP verification with `APP_DEBUG=false`: exception details appeared 0 times, generic 500 page rendered instead (VERIFIED)
- Clean revert of both temporary changes confirmed via `git diff routes/web.php` (empty) and `.env` re-read (`APP_DEBUG=true`) (VERIFIED)

Files changed:
- Added: `resources/views/errors/404.blade.php`, `403.blade.php`, `500.blade.php`
- Added: `tests/Feature/ErrorPagesTest.php`
- Added: `docs/backend-concepts/error-handling.md`
- (Temporarily modified and cleanly reverted, not part of the final diff: `routes/web.php`, `.env`)

### Phase 2.8 — Foundational ADRs (001–004, 007)
Status: Complete, verified (no code changes, pure documentation — sanity-checked that the test suite and Pint remain unaffected)

Completed:
1. **ADR 001 (Laravel monolith)**: documents why TaskFlow is one Laravel app rather than microservices — ties directly to why cross-entity transactions (ADR 008) and Eloquent relationships (`docs/backend-concepts/database-relationships.md`) are easy to demonstrate cleanly in this app, which would become a distributed-transaction problem across service boundaries.
2. **ADR 002 (PostgreSQL)**: documents the primary-database choice, distinct from ADR 010 (which is specifically about the *test* database strategy). Captures a sub-decision not previously written down anywhere: `ProjectStatus`/`TaskStatus`/`TaskPriority`/`ProjectRole` are stored as plain `VARCHAR` with PHP-level enum casting, NOT PostgreSQL's native `ENUM` type — deliberately, since adding a new PHP enum case is less disruptive than a Postgres `ALTER TYPE`.
3. **ADR 003 (Blade)**: documents why no SPA/API layer exists, tying directly to the "one hop, fully traceable" request lifecycle goal already documented in `docs/architecture/request-lifecycle.md`, and explicitly cross-references why `@can` Blade checks are UI convenience only (ADR 006).
4. **ADR 004 (Eloquent, no repository pattern)**: documents the middle-ground actually built — direct Eloquent in controllers, but small reusable logic (`Project::hasMember()`, `Task::isOverdue()`) factored onto models rather than left duplicated OR extracted into a full repository layer. Explicitly ties to the org's "never use raw SQL strings" security rule as a contributing factor, not just a style preference.
5. **ADR 007 (no service layer)** — a gap discovered while verifying the ADR list against the spec's exact required set (001–010); 005/006/008/009/010 already existed, but 007 had never been written. Documents the actual, real decision already implicitly made throughout every controller in this app (business logic lives in controllers, not in a separate `app/Services/` layer) — using `ProjectController::store()`'s transaction as the concrete example of logic that's short enough to stay inline, with an explicit trigger for when this would be reconsidered (the moment any business operation needs a second real caller beyond its current single controller action).

All 10 spec-required ADRs (001–010) now exist. This was a documentation-only phase — no application code, routes, migrations, or tests were touched. Verified this claim by re-running the full test suite and Pint after writing all 5 ADRs, confirming zero regressions (expected, but checked rather than assumed).

Remaining for full Phase 2: 8 of 14 backend-concepts docs still unwritten (database-indexes.md, transactions.md as a standalone concept doc — distinct from ADR 008's specific decision — pagination.md, logging.md, http-and-rest.md, routing.md, middleware.md, controllers.md), a real `DatabaseSeeder` at volume with named demo accounts, the 5 numbered failure experiments, `docs/testing/manual-verification.md`, final learning report, final backend assessment.

Verification:
- `php artisan test` (full suite) → 61 passed, 154 assertions, unchanged from before this phase (VERIFIED — confirms a documentation-only phase genuinely touched no code)
- `./vendor/bin/pint --test` → passed (VERIFIED)
- `ls docs/architecture/adr/` → confirmed exactly 10 files, 001 through 010, matching the spec's required list verbatim (VERIFIED)

Files changed:
- Added: `docs/architecture/adr/001-laravel-monolith.md`
- Added: `docs/architecture/adr/002-postgresql.md`
- Added: `docs/architecture/adr/003-blade-server-rendered-ui.md`
- Added: `docs/architecture/adr/004-eloquent-orm.md`
- Added: `docs/architecture/adr/007-service-layer.md`

### Phase 2.9 — Remaining backend-concepts docs + real Laravel logging
Status: Complete, verified (real log entries captured from the actual test suite run, not fabricated; full regression test after code changes)

Completed:
1. **Discovered a real gap while auditing before writing `logging.md`**: the spec requires "Implement useful Laravel logging" as a functional requirement (not just documentation), and grep confirmed **zero** `Log::` facade calls existed anywhere in `app/` — only the domain-level `ActivityLog` model (user-facing audit trail) existed. Rather than write a doc describing logging that wasn't actually implemented, added 4 real `Log::` call sites first: `ProjectController::destroy()` (`Log::warning`, since project deletion cascades destructively through tasks/comments/memberships), `TaskController::destroy()` (`Log::info`), `ProjectMemberController::destroy()` (`Log::info`, access-revocation), `AuthenticatedSessionController::store()`/`destroy()` (`Log::info`, login/logout) and `LoginRequest::authenticate()`'s failure path (`Log::warning`, failed login — deliberately includes the attempted email + IP for brute-force pattern detection, the one log line in the app that includes anything PII-adjacent, justified because detecting that pattern IS the log's purpose — never includes the password).
2. **Verified the new logging by reading real captured log lines**, not by assuming the code was correct: ran the full test suite (which naturally exercises all 4 call sites via `AuthenticationTest`/`ProjectTest`/`ProjectMemberTest`/`TaskTest`) and grepped `storage/logs/laravel.log` afterward — confirmed real entries for all 4 events with correct structured context (`user_id`, `project_id`, etc.) and zero leaked passwords/tokens.
3. **Wrote `docs/backend-concepts/logging.md`** describing exactly this real implementation — including a concrete side-by-side comparison of the SAME event (project deletion) showing why it gets a `Log::warning()` but deliberately NOT an `ActivityLog::create()` (logging an activity entry pointing at a subject about to be deleted would leave a permanently-broken reference), which doubles as the clearest possible illustration of the spec's required "Application logs vs. Audit logs" distinction.
4. **Wrote the 7 remaining backend-concepts docs**: `database-indexes.md` (collects the "why this index" reasoning already scattered across every migration's inline comments into one place, including the story of catching and removing a redundant duplicate index in Phase 2.3), `transactions.md` (the standalone concept doc, distinct from ADR 008's specific decision record — explains WHY only one operation in this app uses a transaction while several similar-looking two-write operations deliberately don't), `pagination.md` (explains the actual `LIMIT`/`OFFSET` SQL `paginate()` generates, plus the offset-vs-cursor trade-off from ADR 009), `http-and-rest.md`, `routing.md`, `middleware.md`, `controllers.md`.
5. **Caught and fixed a real factual error while writing `http-and-rest.md`**: initially wrote that a "MethodField middleware" handles `_method` spoofing for `PUT`/`DELETE` forms — a plausible-sounding but incorrect guess. Verified against actual Laravel/Symfony source (`grep`-ing `vendor/`) before publishing, found the real mechanism is Symfony's `Request::enableHttpMethodParameterOverride()`, called by Laravel's HTTP Kernel — corrected before this became a false claim in a doc meant to teach accurate mechanics, not "sounds right."
6. **Discovered and fixed a bigger, pre-existing accuracy problem**: while writing `controllers.md`, referenced `docs/architecture/request-lifecycle.md` as an existing doc (echoing 3 other files — `003-blade-server-rendered-ui.md`, `004-eloquent-orm.md`, `007-service-layer.md` — that had ALL previously referenced it the same way, as if it had been written back in Phase 1 per the spec's own requirement). Ran a repo-wide scan (`grep -rhoE 'docs/[a-zA-Z0-9_/.-]+\.md' docs/` cross-checked against actual files on disk) and confirmed **the file never actually existed** — a real instance of the exact failure mode the project's anti-hallucination rules exist to prevent (claiming something is complete/documented without verification). Fixed by actually writing `docs/architecture/request-lifecycle.md` — a full trace of a real "Create Task" request through all 15 stages of the spec's required flow diagram, using this app's actual code (real route, real Policy check, real Eloquent call, real redirect) rather than a generic/hypothetical example — which retroactively makes all 4 prior references true rather than requiring each one to be individually rewritten.
7. **Re-ran the same missing-reference scan after the fix**: confirmed only 3 references remain unresolved, and verified each is legitimately forward-looking prose ("added later," "planned"), not a false present-tense claim — `docs/backend-concepts/testing-strategy.md`, `docs/testing/manual-verification.md`, `docs/FINAL-LEARNING-REPORT.md`, all explicitly future work already tracked in this file's own NEXT ACTION notes.

Remaining for full Phase 2: a few docs named only in the spec's file-tree diagram, not its explicit required-doc prose list (`docs/architecture/system-overview.md`/`database-design.md`/`architecture-decisions.md`, `docs/database/database-design.md`, `docs/security/security-model.md`, `docs/testing/testing-strategy.md`) — judged lower priority than the explicitly-named 14 backend-concepts docs + 10 ADRs, which are now complete; a real `DatabaseSeeder` at volume with named demo accounts; the 5 numbered failure experiments; `docs/testing/manual-verification.md`; final learning report; final backend assessment.

Verification:
- `php artisan test` (full suite, after adding 4 real Log:: call sites) → 61 passed, 154 assertions, unchanged (VERIFIED — confirms the logging additions introduced no regressions)
- `./vendor/bin/pint --test` → passed (VERIFIED)
- `grep -E "User logged (in|out)|Failed login|Project deleted|Task deleted|Project member removed" storage/logs/laravel.log` → real captured entries for all 4 new log call sites, correct structured context, zero passwords/tokens present (VERIFIED)
- `grep -rhoE 'docs/[a-zA-Z0-9_/.-]+\.md' docs/` cross-checked against files on disk, before and after writing `request-lifecycle.md` → confirmed the gap, then confirmed the fix, then confirmed only legitimately-forward-looking references remain (VERIFIED)
- `ls docs/backend-concepts/` → confirmed all 14 spec-required files present (VERIFIED)

Files changed:
- Modified: `app/Http/Controllers/ProjectController.php` (added `Log::warning` on project deletion)
- Modified: `app/Http/Controllers/TaskController.php` (added `Log::info` on task deletion)
- Modified: `app/Http/Controllers/ProjectMemberController.php` (added `Log::info` on member removal)
- Modified: `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (added `Log::info` on login/logout)
- Modified: `app/Http/Requests/Auth/LoginRequest.php` (added `Log::warning` on failed login)
- Added: `docs/architecture/request-lifecycle.md`
- Added: `docs/backend-concepts/logging.md`, `database-indexes.md`, `transactions.md`, `pagination.md`, `http-and-rest.md`, `routing.md`, `middleware.md`, `controllers.md`

### Phase 2.10 — Database Seeding + Demo Accounts
Status: Complete, verified (real HTTP login as a demo account + dashboard counts cross-checked exactly against direct DB queries)

Completed:
1. **Found and fixed a real, previously-untracked cleanup gap before seeding**: checked the dev database's state before running the new seeder (a habit established every prior phase) and found 3 orphaned `activity_logs` rows left over from Phase 2.6's manual verification — that phase's cleanup command omitted `activity_logs` from its `DELETE FROM` list (harmless at the time since `user_id`/subject references are nullable/unenforced, but still stale data). Cleaned it up before seeding rather than seeding on top of leftover noise.
2. **`database/seeders/DatabaseSeeder.php` rewritten** from the Laravel-default single-test-user stub to real seed data at the spec's required volume: 4 named demo accounts (`admin@example.test`/`manager@example.test`/`member@example.test`/`viewer@example.test`, password `password`, documented as a constant with an explicit comment that this is dev-only seed data, never a real-world-safe value) + 10 additional random users (14 total, exceeds the 10+ requirement); 6 projects (exceeds 5+); every demo account attached as a member of every project with their INTENDED role (Owner/Manager/Member/Viewer) — not left to whatever a factory default would produce — so logging in as any one of them immediately demonstrates role-based authorization without further setup; ~10 tasks per project (60 total, exceeds 50+), with the first 2 tasks per project deliberately overdue so the Dashboard's Overdue count is never zero in the seeded data; ~2 comments per task (120 total, exceeds 100+); activity log entries for every project/task creation (66 total), giving every seeded project/task a non-empty activity feed out of the box.
3. **Caught and fixed 2 code-quality issues via Pint + self-review, not left in**: an unused `TaskPriority` import (Pint's `no_unused_imports` fixer), and a genuinely dead `$projectRoster` variable (declared, assigned, never referenced) — removed rather than left as clutter, matching the project's "no dead code" principle.
4. **Verified the seeder is correct, not just that it runs without error**: after seeding, ran direct `psql` queries confirming exact row counts (14/6/60/120/66) and confirming every demo account's role on every project matches the intended design (only one deviation, expected: on the one project NOT created by admin, admin holds the Member role instead of Owner, exactly as the seeder's logic specifies).
5. **Manual HTTP verification, the most consequential of this phase**: started a real `php artisan serve` instance, logged in as `admin@example.test` with the documented password, confirmed the Dashboard showed 6/60/13/35/12 (projects/tasks/completed/pending/overdue) — then ran the equivalent raw SQL aggregate query directly against PostgreSQL and confirmed an EXACT match, proving the seeded data and the dashboard aggregation code (built in Phase 2.6, before any seed data existed to test it against at this volume) are both correct together, not just individually plausible. Also confirmed a project's detail page shows the exact designed "Members (6) / Tasks (10)" counts, and that `/tasks?status=completed` correctly returns exactly the 13 completed tasks with zero non-completed tasks leaking through.
6. **Deliberately did NOT clean up this data afterward** — unlike every previous phase's manual-verification data (always created and then deleted), this seed data is the actual intended deliverable and is meant to persist in `laravel_learning_dev` going forward. Added an explicit warning note near the top of this file (see "Important: Dev Database Now Contains Real Seed Data" above) so a future session doesn't reflexively wipe it the way every prior phase's cleanup routine did.
7. **Updated `docs/SETUP.md`** with seeding instructions (including that `db:seed` is NOT idempotent — re-running it inserts a second full copy, so clearing first is documented explicitly) and the demo account table.

Remaining for full Phase 2: the 5 numbered failure experiments; `docs/testing/manual-verification.md`; final learning report; final backend assessment. A handful of docs named only in the spec's file-tree diagram (not its required-doc prose list) remain optional/lower-priority (unchanged from the prior checkpoint).

Verification:
- `psql` row counts after seeding → 14 users, 6 projects, 60 tasks, 120 comments, 66 activity_logs, 36 project_user rows (VERIFIED, exceeds every spec minimum)
- `psql` role-assignment cross-check for all 4 demo accounts across all 6 projects → matches intended design exactly (VERIFIED)
- `php artisan test` (full suite, dev DB seeded, test DB untouched since it's a separate database) → 61 passed, 154 assertions, unchanged (VERIFIED — confirms seeding the dev DB has zero effect on the test suite)
- `./vendor/bin/pint --test` → passed after 2 fixes (unused import, dead variable) (VERIFIED)
- Manual HTTP login as `admin@example.test` / `password` → 302 to `/dashboard` (VERIFIED)
- Dashboard shows 6/60/13/35/12 → cross-checked against a direct SQL aggregate query → EXACT match (VERIFIED)
- Project detail page shows "Members (6) / Tasks (10)" exactly as designed (VERIFIED)
- `/tasks?status=completed` shows exactly 13 completed-status badges, zero others (VERIFIED)

Files changed:
- Modified: `database/seeders/DatabaseSeeder.php` (real seed data, replacing the Laravel-default stub)
- Modified: `docs/SETUP.md` (seeding instructions + demo account table)
- Modified: `docs/PROJECT-STATE.md` (this file — added the "dev DB now contains real data" warning)

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
10. ~~N+1 avoided proactively in `ProjectController::index()`~~ **`docs/backend-concepts/eloquent.md` now written (Phase 2.4)** with a real, measured demonstration (7 queries → 3 queries, actual captured SQL, not hypothetical).
11. **`project_user` uses `cascadeOnDelete()` on both FKs, while `projects.created_by` uses `restrictOnDelete()`** — a deliberate, documented divergence: membership rows have no independent meaning once either side is gone, but project ownership records should never silently vanish. See the migration's inline comments and `docs/backend-concepts/database-relationships.md`.
12. **`activity_logs.user_id` uses `nullOnDelete()`, not cascade or restrict** — an audit trail should survive the actor's account being deleted (the log entry stays, just with a null user reference) rather than being deleted itself or blocking account deletion.
13. **Transaction for project creation deferred from Phase 2.2 to 2.3, implemented once `project_user`/`activity_logs` existed** — rather than either skipping it or awkwardly pre-creating those tables early. See ADR 008.
14. **Last-owner-removal guard implemented as a controller-level business rule, not a Form Request validation rule** — because it depends on querying the state of OTHER membership rows (how many Owners currently exist), not just the shape of the current request. Matches the validation-vs-business-rule distinction in `docs/backend-concepts/validation.md`.
15. **`ProjectFactory` updated to auto-attach creator as Owner via `afterCreating`** — after discovering this was a real, necessary fix (not a nice-to-have) once `ProjectPolicy` became membership-based; framed as "factories should produce the same valid invariant the real transaction guarantees," not a test-only hack.
16. **Task lifecycle activity logged against the Task subject; task deletion logged against the Project subject** — a deliberate split, not an inconsistency: a deleted task can no longer be viewed, so its "deletion" event is the one task-related activity that belongs on the still-viewable project instead. Caught and fixed a bug where this was initially reversed (see Phase 2.4 item 8).
17. **`TaskPolicy::update()` extends update rights to the task's own assignee, not just Owner/Manager** — a deliberate divergence from `ProjectPolicy`'s "only management roles" pattern, because the spec's "Change Status" action needs to work for whoever the task is actually assigned to.
18. **Sort column whitelisted via `match` in `TaskController::index()`, never string-interpolated into `ORDER BY`** — a concrete SQL-injection-prevention decision, not just a style choice; documented inline in the controller.
19. **`CommentPolicy` is author-only for update/delete, with NO Owner/Manager override** — the one policy in this app that deliberately does NOT follow the "management roles can act on anything in the project" pattern used everywhere else. Matches the spec's literal wording, verified over real HTTP against a project Manager specifically because this was the rule most likely to have a copy-paste bug from `TaskPolicy`.
20. **`Comment::user()` is nullable**, matching `comments.user_id`'s `nullOnDelete()` — discussion content survives the author's account being deleted; Blade renders `$comment->user?->name ?? 'Deleted user'`.
21. **`Task::comments()` orders oldest-first; `Task::activities()` orders newest-first** — different content types, different natural reading order (conversation vs. reverse-chronological log), documented explicitly as intentional in the model's code comments.
22. **Dashboard "Pending Tasks" defined as `NOT IN (Completed, Cancelled)`, not just `!= Completed`** — a cancelled task is neither pending work nor completed work; treating it as "pending" would inflate that count with tasks nobody is going to act on.
23. **"Pending" and "Overdue" are not mutually exclusive counts** — an overdue task is still incomplete/pending work, so it correctly increments both counters. Verified explicitly via manual HTTP test (a task with a past due_date and `todo` status counted toward both).
24. **`DashboardTest` rewritten mid-writing after catching its own flaw**: an initial draft used `assertSee('1</div>')`-style HTML substring matching, then was recognized as unreliable (multiple distinct stats could share the same numeric value and false-positive match) before being kept — rewritten to assert on `$response->viewData(null)` instead, asserting the actual typed PHP values passed to the view.
25. **`errors/500.blade.php` does NOT extend `<x-layout>`, unlike every other view in the app** — a 500 page must render correctly even when auth/session/routing itself is what's broken; `<x-layout>` depends on all three resolving successfully.
26. **`APP_DEBUG=false` behavior verified empirically over real HTTP in both directions (true and false), not just asserted in one automated test** — manually triggered a real exception through `php artisan serve` with both settings, confirmed the debug page leaks details 6 times when `true` and the custom page leaks 0 times when `false`. Temporary route and `.env` change both cleanly reverted afterward, confirmed via `git diff`.
27. **`ProjectStatus`/`TaskStatus`/`TaskPriority`/`ProjectRole` stored as plain `VARCHAR` with PHP-enum casting, not PostgreSQL native `ENUM` types** — a sub-decision made implicitly back in Phase 2.2 but only formally documented now (ADR 002): adding a new PHP enum case is less disruptive than a Postgres `ALTER TYPE` migration.
28. **No service layer (`app/Services/`) anywhere in this app** — business logic lives directly in controllers; the multi-step `DB::transaction()` in `ProjectController::store()` is the concrete example. Documented in ADR 007 with an explicit trigger for reconsidering: the moment any business operation needs a second real caller beyond its current single controller action.
29. **Added 4 real `Log::` call sites (project deletion, task deletion, member removal, login/logout/failed-login)** — discovered zero existed anywhere in the app despite the spec requiring "useful Laravel logging" as a functional requirement; added rather than just documenting the absence. See `docs/backend-concepts/logging.md`.
30. **Project deletion gets a system log (`Log::warning`) but deliberately NOT an `ActivityLog` row, unlike every other destructive action** — logging an activity entry pointing at a subject about to be deleted would leave a permanently orphaned reference with nothing left to view.
31. **Seeded data is deliberately NOT cleaned up after verification, unlike every prior phase's manual test data** — this is the actual intended deliverable, meant to persist in `laravel_learning_dev`. Explicit warning added near the top of this file so a future session doesn't reflexively wipe it.
32. **`db:seed` is not idempotent by design** — re-running it inserts a second full copy rather than upserting; documented explicitly in `docs/SETUP.md` with the exact `DELETE FROM` sequence to clear first, rather than adding upsert logic to the seeder itself (unnecessary complexity for a one-time-per-fresh-database operation).

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

--- Phase 2.4 (Tasks) ---
php artisan migrate --pretend / --force (tasks table)   → correct SQL, applied cleanly
php artisan test --filter=TaskTest   → 12 passed, 29 assertions
php artisan test (full suite)   → 46 passed, 111 assertions
./vendor/bin/pint --test   → passed, no violations
Empirical N+1 measurement (tinker, DB::enableQueryLog(), 3 seeded projects × 2 tasks each):
  WITHOUT with(['tasks','owner'])   → 7 queries (1 + 3×2), captured actual SQL for each
  WITH with(['tasks','owner'])      → 3 queries (1 + 1 + 1), captured actual SQL for each
  cleanup: DELETE FROM tasks/project_user/projects/users   → confirmed 0 rows remain
Manual curl verification (single user session):
  register + create project   → 302 / 302 (hit the same CSRF-419 scripting artifact a third time; cross-checked DB session token vs rendered token — exact match — before re-submitting with a freshly split command, confirmed app-level correctness each time)
  create task via /projects/{id}/tasks   → 302; psql confirms task row AND activity_logs row with subject_type='App\Models\Task' (confirms the Phase 2.4 item-8 bug fix actually took effect)
  GET /tasks/1   → 200; page shows task title AND the "created task" activity entry
  PUT /tasks/1 status change (todo → in_progress)   → 302; psql confirms status changed AND activity log message text: 'Task Tester changed task "..." status from "To Do" to "In Progress".' — matches spec's example format
  GET /tasks?status=in_progress   → task appears; GET /tasks?status=completed   → task correctly absent, "No tasks match these filters" shown
  cleanup: killed dev server, DELETE FROM activity_logs/tasks/project_user/projects/users   → confirmed 0/0/0/0 rows remain in dev database

--- Phase 2.5 (Comments) ---
php artisan migrate --pretend / --force (comments table)   → correct SQL, applied cleanly
php artisan test --filter=CommentTest   → 9 passed, 22 assertions
php artisan test (full suite)   → 55 passed, 133 assertions
./vendor/bin/pint --test   → passed, no violations on first run
Manual curl verification (two real user sessions — author + a project Manager):
  register both users + create project + make second user a Manager (real HTTP /projects/{id}/members flow)   → all succeeded
  create task + post comment as author   → 302; psql confirms comment row AND activity_logs row (subject_type='App\Models\Task', "commented on task")
  GET /tasks/{id}   → 200; comment body AND author name both render
  Manager GET /comments/{id}/edit   → 403 (blocked despite full task-level authority)
  Manager DELETE /comments/{id}   → 403 (same)
  Author GET /comments/{id}/edit   → 200; Author PUT /comments/{id}   → 302; psql confirms body actually changed
  cleanup: killed dev server, DELETE FROM comments/activity_logs/tasks/project_user/projects/users   → confirmed 0/0/0/0/0 rows remain in dev database

--- Phase 2.6 (Dashboard) ---
php artisan test --filter=DashboardTest   → 2 passed, 8 assertions
php artisan test (full suite)   → 57 passed, 141 assertions
./vendor/bin/pint --test   → passed, no violations
Empirical query-count measurement (tinker, 20 seeded tasks, direct DashboardController::index() call):
  → exactly 5 queries: 1 project-ID lookup + 4 COUNT(*) aggregates, captured and reviewed actual SQL
  cleanup: DELETE FROM tasks/project_user/projects/users   → confirmed 0 rows remain
Manual curl verification (single user session):
  register (no data yet)   → GET /dashboard shows 0/0/0/0/0 (verifies the empty-membership-set edge case doesn't break the aggregate queries)
  create 1 project + 1 overdue task (todo, due_date in the past) + 1 completed task   → all 302
  GET /dashboard   → 1/2/1/1/1 (projects/tasks/completed/pending/overdue) — exact match to hand-computed expectation, including the overdue task correctly counting toward BOTH pending and overdue
  cleanup: killed dev server, DELETE FROM tasks/project_user/projects/users WHERE ...   → confirmed 0/0/0 rows remain in dev database

--- Phase 2.7 (Error-page hardening) ---
php artisan test --filter=ErrorPagesTest   → 4 passed, 13 assertions
php artisan test (full suite)   → 61 passed, 154 assertions
./vendor/bin/pint --test (before fix)   → failed, 1 file (ErrorPagesTest.php, import ordering)
./vendor/bin/pint (auto-fix) + re-run tests   → fixed, 61 passed unchanged
Manual HTTP verification via php artisan serve, real APP_DEBUG toggle:
  Temporarily added Route::get('/__debug-trigger-500', fn () => throw new RuntimeException(...))
  GET /__debug-trigger-500 with APP_DEBUG=true    → 500, exception message/class appear 6 times in response body
  Edited .env to APP_DEBUG=false (no server restart — confirmed built-in PHP server re-reads .env per request)
  GET /__debug-trigger-500 with APP_DEBUG=false   → 500, exception message/class appear 0 times, generic custom page renders instead
  Reverted .env to APP_DEBUG=true and removed the temporary route
  git diff routes/web.php   → empty (confirms clean revert); grep APP_DEBUG .env → APP_DEBUG=true (confirms revert)

--- Phase 2.8 (Foundational ADRs) ---
php artisan test (full suite, after writing 5 ADR markdown files, zero code touched)   → 61 passed, 154 assertions, unchanged
./vendor/bin/pint --test   → passed
ls docs/architecture/adr/ | sort   → confirmed exactly 10 files (001–010), matching spec's required list

--- Phase 2.9 (remaining backend-concepts docs + real logging) ---
grep -rn "Log::" app/ --include="*.php" (BEFORE adding logging)   → zero results, confirming the gap
php artisan test (full suite, after adding 4 Log:: call sites)   → 61 passed, 154 assertions, unchanged
./vendor/bin/pint --test   → passed
grep -E "User logged (in|out)|Failed login|Project deleted|Task deleted|Project member removed" storage/logs/laravel.log   → real entries for all 4 new call sites, correct context, no leaked credentials
grep -rhoE 'docs/[a-zA-Z0-9_/.-]+\.md' docs/ cross-checked against disk (BEFORE writing request-lifecycle.md)   → found docs/architecture/request-lifecycle.md referenced by 4 files but not existing on disk
(wrote docs/architecture/request-lifecycle.md)
same scan re-run (AFTER)   → only 3 references remain, all confirmed legitimately forward-looking ("added later"/"planned"), not false present-tense claims
ls docs/backend-concepts/ | sort   → confirmed all 14 spec-required files present

--- Phase 2.10 (Database seeding + demo accounts) ---
psql pre-seed check   → found 3 orphaned activity_logs rows from Phase 2.6's incomplete cleanup, deleted before seeding
php artisan db:seed   → completed without error
psql row counts   → 14 users, 6 projects, 60 tasks, 120 comments, 66 activity_logs, 36 project_user (all exceed spec minimums)
./vendor/bin/pint --test (before fixes)   → failed, DatabaseSeeder.php (unused TaskPriority import)
(fixed unused import + removed dead $projectRoster variable)
./vendor/bin/pint --test (after fixes)   → passed
php artisan test (full suite, dev DB seeded)   → 61 passed, 154 assertions, unchanged
Manual HTTP: login admin@example.test / password   → 302 to /dashboard
GET /dashboard   → 6/60/13/35/12 (projects/tasks/completed/pending/overdue)
Direct SQL aggregate query (independent of app code)   → 60/13/35/12 — EXACT match to dashboard's rendered values
GET /projects/{id}   → "Members (6) / Tasks (10)" — exact match to seeder's designed per-project counts
GET /tasks?status=completed   → exactly 13 "Completed" badges, zero other statuses present
```

## Current Blocker

None. Phase 2.10 is complete and verified, pending only the commit/push described in NEXT ACTION step 1. The dev database is now populated with real, intentionally-persistent seed data and 4 working demo accounts.

## NEXT ACTION

1. **Immediate**: commit this Phase 2.10 work (`database/seeders/DatabaseSeeder.php`, `docs/SETUP.md`, this file — everything listed under "Files changed" in the Phase 2.10 section above) and `git push origin main`. Same review discipline as every prior commit: `git add -n .` dry run first, `git diff --cached | grep -i password` before committing (the demo password `password` appears as a literal string in `DatabaseSeeder.php` and `docs/SETUP.md` — this is fine and intentional, a documented local-dev-only seed value, not a leaked real credential; don't mistake it for one and don't strip it out). Confirm `git push` output actually shows success. **A future session must re-verify via `git log`/`git status`** rather than trusting this file's claim if time has passed. **Also remember**: `laravel_learning_dev` now has real seed data that should NOT be wiped — see the warning near the top of this file.
2. Recommended next sub-phase: the 5 numbered failure experiments from the spec (deliberately break auth/validation/transactions/N+1/DB-constraints and document what happens) — most of the underlying mechanics to break are already built and individually testable from prior phases; this phase is about deliberately breaking them ON PURPOSE and writing up the observed failure, not building new functionality. Several natural hooks already exist: removing the `auth` middleware (`routes/web.php` comments already reference this as "Failure Experiment 1"), removing the `DB::transaction()` in `ProjectController::store()` (ADR 008 already references this as "Failure Experiment 3"), removing eager loading from `ProjectController::index()`/`show()` (ties to the N+1 measurement already done in `docs/backend-concepts/eloquent.md`). **With seed data now in place, these experiments can be demonstrated against realistic data volume rather than data created just for the experiment.**
3. Then: `docs/testing/manual-verification.md` (a consolidated checklist — much of its content already exists scattered across this file's manual-verification notes per phase and could be extracted/reorganized rather than written from scratch), final learning report (`docs/FINAL-LEARNING-REPORT.md`), final backend assessment (~20 questions, spec explicitly says not to provide answers until asked).
4. Nothing scope-wise has changed beyond what Phase 2.10 added — remaining work is entirely: failure experiments, and the two final teaching deliverables. A handful of docs named only in the spec's file-tree diagram (not its required-doc prose list) remain optional/lower-priority, noted in the Overall Progress checklist above.
