# Final Learning Report — TaskFlow

This report closes out the TaskFlow learning project. Every concept below was actually implemented, tested, and in most cases deliberately broken and observed (see `docs/testing/failure-experiments.md`) in this exact codebase — not studied in the abstract. Each entry points at real files, not generic advice.

## Concepts Learned

```text
[x] HTTP
[x] REST
[x] Routing
[x] Middleware
[x] Controllers
[x] Dependency Injection
[x] Request Validation
[x] Authentication
[x] Sessions
[x] Authorization
[x] Policies
[x] Eloquent
[x] Relationships
[x] SQL
[x] PostgreSQL
[x] Indexes
[x] Transactions
[x] Pagination
[x] Error Handling
[x] Logging
[x] Testing
[~] Docker (deliberately not used — see its section below for why, and what you should still understand about it)
```

---

### HTTP

- **Where it appears**: every request/response in this app, traced end-to-end in `docs/architecture/request-lifecycle.md`.
- **Relevant files**: `docs/backend-concepts/http-and-rest.md`; every route in `routes/web.php`.
- **What you should understand**: the request/response cycle, the four HTTP methods this app actually uses (GET/POST/PUT/DELETE) and what each implies, status codes this app actually produces (200/302/403/404/422-via-redirect/500), and why `PUT`/`DELETE` in HTML forms require method spoofing (`@method('PUT')` + Symfony's `enableHttpMethodParameterOverride()`).
- **Common mistake**: assuming a `POST` form can send `PUT`/`DELETE` natively — it can't; without the `_method` field and Laravel's override support, every form-submitted action would arrive as `POST`, and routes registered for `PUT`/`DELETE` simply wouldn't match.
- **Interview-style question**: "Why does a successful form submission in this app return a 302 rather than a 200 with the resulting page?" (Answer hinges on the Post/Redirect/Get pattern — preventing a page refresh from resubmitting the form.)

### REST

- **Where it appears**: `Route::resource('projects', ProjectController::class)` generates the 7 conventional routes; `CommentController` deliberately does NOT follow the full convention (no index/show/create) since comments have no standalone page.
- **Relevant files**: `docs/backend-concepts/http-and-rest.md`, `docs/backend-concepts/routing.md`, `routes/web.php`.
- **What you should understand**: REST organizes URLs around resources (nouns) with HTTP methods conveying the action, not verbs baked into the URL. Not every resource needs the full 7-action set — deviating for a genuine reason (comments render inline, not on their own page) is a legitimate adaptation, not "doing REST wrong."
- **Common mistake**: forcing every resource onto `Route::resource()` even when some of its 7 generated routes would never be used, just for consistency's sake — this app deliberately didn't do that for `ProjectMemberController`/`CommentController`.
- **Interview-style question**: "When is it correct to deviate from the standard 7-action REST resource convention, and how do you decide?"

### Routing

- **Where it appears**: `routes/web.php` — every URL this app responds to, confirmed at every phase via `php artisan route:list`.
- **Relevant files**: `docs/backend-concepts/routing.md`.
- **What you should understand**: route registration is explicit (no convention-based magic), route-model binding (`Task $task` in a method signature runs `Task::findOrFail()` for you automatically), and why every route in this app is named (`->name(...)`) — so `route('tasks.show', $task)` never hardcodes a URL string that could drift from the actual route definition.
- **Common mistake**: hardcoding URLs (`'/tasks/'.$task->id`) instead of `route('tasks.show', $task)` — works until the URL prefix changes, then breaks silently everywhere it was hardcoded.
- **Interview-style question**: "A route parameter like `{task}` in `Route::get('/tasks/{task}', ...)` becomes a full `Task` model instance inside the controller. What mechanism makes that happen, and what HTTP response do you get if no matching row exists?"

### Middleware

- **Where it appears**: the `guest`/`auth` middleware groups in `routes/web.php`; Laravel's default `web` group (session, CSRF) applied automatically.
- **Relevant files**: `docs/backend-concepts/middleware.md`; Failure Experiment 1 conceptually targets this layer (though the experiment actually performed removed a Policy check, not middleware — see the note in that doc about the distinction).
- **What you should understand**: middleware runs BEFORE the controller, is applied to a whole route group at once (so no individual route can "forget" to check), and answers a fundamentally different question than authorization ("is anyone logged in" vs. "is THIS user allowed to touch THIS resource").
- **Common mistake**: assuming `auth` middleware alone is sufficient protection for a resource — it only proves someone is logged in, not that they're allowed to see this specific project/task (that's the Policy layer's job).
- **Interview-style question**: "If you removed the `auth` middleware from the `/projects` route group but every Policy check still worked correctly, what would an unauthenticated visitor actually be able to do?"

### Controllers

- **Where it appears**: 9 controllers, each scoped to one resource — `ProjectController`, `TaskController`, `CommentController`, etc.
- **Relevant files**: `docs/backend-concepts/controllers.md`, `app/Http/Controllers/`.
- **What you should understand**: the consistent shape every controller method in this app follows — validate (via a type-hinted Form Request) → authorize (via the Form Request's `authorize()` or an explicit `Gate::authorize()`) → business logic → redirect/render. No controller in this app exceeds this shape; there's no hidden logic elsewhere.
- **Common mistake**: putting validation or authorization logic inline in the controller body instead of in a Form Request — works, but scatters the same concern across many methods instead of centralizing it where Laravel's framework-level hooks (automatic pre-controller execution) can enforce it structurally.
- **Interview-style question**: "`TaskController::destroy()` calls `Gate::authorize()` directly instead of using a Form Request. Why doesn't that particular action have one, when `store()`/`update()` do?"

### Dependency Injection

- **Where it appears**: every controller method's type-hinted parameters — `StoreProjectRequest $request`, `Task $task` — resolved automatically by Laravel's service container.
- **Relevant files**: any controller method signature in `app/Http/Controllers/`.
- **What you should understand**: you never write `new StoreProjectRequest()` or `Task::find($id)` inside these methods — the framework resolves and injects them before your method body runs, based purely on the type hint.
- **Common mistake**: not realizing that changing a method's type hint (e.g. from the generic `Request` to a specific Form Request class) changes what automatically happens before the method body executes (validation now runs automatically) — it's not just a cosmetic type annotation.
- **Interview-style question**: "How does Laravel know to run `StoreProjectRequest`'s validation rules before `ProjectController::store()`'s body executes, when nothing in that method explicitly calls `validate()`?"

### Request Validation

- **Where it appears**: every `Store*Request`/`Update*Request` class in `app/Http/Requests/`.
- **Relevant files**: `docs/backend-concepts/validation.md`; Failure Experiment 2 (removed the `required` rule from `name`, observed a raw 500 instead of a friendly error, with the data itself still protected by the database's `NOT NULL` constraint).
- **What you should understand**: validation and database constraints are complementary, not redundant — validation makes failure graceful (a specific, actionable message); the constraint is the last line of defense for data integrity regardless of how a bad request got there.
- **Common mistake**: treating a passing validation rule as proof the data is safe to insert, and skipping the database-level constraint as "redundant" — Failure Experiment 5 shows exactly what happens when a constraint is missing and something bypasses validation entirely (a direct SQL insert).
- **Interview-style question**: "If you removed all validation from `StoreTaskRequest` but left every database constraint intact, what specifically would still be safe, and what would get noticeably worse for users?"

### Authentication

- **Where it appears**: `RegisteredUserController`, `AuthenticatedSessionController`, `LoginRequest`.
- **Relevant files**: `docs/backend-concepts/authentication-and-sessions.md`, ADR 005.
- **What you should understand**: authentication answers "who is this," implemented here via Laravel's session guard (`Auth::attempt()`), password hashing (bcrypt via the model's `'password' => 'hashed'` cast — never compared or logged as plaintext), and rate limiting on failed attempts (per email+IP, not just per IP, so one victim's lockout can't be weaponized against a shared-IP innocent party).
- **Common mistake**: confusing authentication with authorization — being logged in proves identity, not permission (see the Authorization entry below).
- **Interview-style question**: "Why does this app rate-limit login attempts per email+IP combination rather than just per IP?"

### Sessions

- **Where it appears**: `SESSION_DRIVER=database` (`.env`), the `sessions` table, `$request->session()->regenerate()`/`invalidate()`.
- **Relevant files**: `docs/backend-concepts/authentication-and-sessions.md`.
- **What you should understand**: HTTP is stateless — a session (server-side state, keyed by a cookie the browser sends back automatically) is what makes "staying logged in" possible at all. `session()->regenerate()` after login prevents session fixation; `session()->invalidate()` on logout actually deletes the session ROW from the database, not just clears a flag.
- **Common mistake**: forgetting to regenerate the session ID after a privilege change (like login) — an attacker who fixed a victim's session ID before authentication could otherwise inherit the now-authenticated session.
- **Interview-style question**: "What's the concrete difference between `Auth::guard('web')->logout()` and `$request->session()->invalidate()`, and why does this app's logout call both?"

### Authorization

- **Where it appears**: every `Gate::authorize()`/`can()` call throughout the controllers and Form Requests.
- **Relevant files**: `docs/backend-concepts/authorization-and-policies.md`; Failure Experiment 1 (removed a single `Gate::authorize()` call, and a genuine outsider account could then view any project's full details including every real member's name).
- **What you should understand**: authorization is a DIFFERENT question from authentication — "is this identified user allowed to touch THIS specific resource" — and must be enforced server-side; hiding a UI button is never sufficient (proven concretely in Experiment 1, where the backend check alone was the entire difference between "secure" and "any user can view anyone's project").
- **Common mistake**: relying on `@can` Blade directives alone to "protect" an action — they only hide a button; the actual protection has to be the same Policy check re-run server-side on the controller action itself.
- **Interview-style question**: "A user manually changes `/projects/10` to `/projects/11` in the URL. What specifically prevents unauthorized access, and what happens if that mechanism is removed?" (Directly answered by Experiment 1 in `docs/testing/failure-experiments.md`.)

### Policies

- **Where it appears**: `ProjectPolicy`, `TaskPolicy`, `CommentPolicy`.
- **Relevant files**: `docs/backend-concepts/authorization-and-policies.md`, ADR 006, `app/Policies/`.
- **What you should understand**: Policies are auto-discovered by naming convention (no manual registration needed since Laravel 11+); each app in this project has genuinely DIFFERENT rules by design, not by oversight — `TaskPolicy::update()` extends to the task's own assignee (not just Owner/Manager), while `CommentPolicy` is author-only with NO management-role override at all, matching the spec's literal wording.
- **Common mistake**: copy-pasting one Policy's logic into another without re-deriving whether the same rule actually applies — `CommentPolicy` deliberately does NOT follow `TaskPolicy`'s "managers can act on anything" pattern, and this was specifically manually verified against a real project Manager to make sure that divergence actually holds.
- **Interview-style question**: "Why does `ProjectPolicy::delete()` require the Owner role specifically, while `ProjectPolicy::update()` allows Owner OR Manager? What's the real-world reasoning for making deletion strictly more restrictive?"

### Eloquent

- **Where it appears**: every model in `app/Models/` — no raw SQL strings exist anywhere in this codebase.
- **Relevant files**: `docs/backend-concepts/eloquent.md` (includes a REAL, measured N+1 demonstration — 7 queries vs. 3, captured via `DB::enableQueryLog()` against actually-seeded data, not a hypothetical example).
- **What you should understand**: `create()`/`update()`/`delete()`/`find()`/`findOrFail()` all use bound parameters automatically (no SQL injection risk by default); `#[Fillable(...)]` controls what mass-assignment actually accepts, which is why `$request->validated()` — not `$request->all()` — is what gets passed to `create()`.
- **Common mistake**: the N+1 query problem — accessing a relationship inside a loop without eager-loading it first, which was deliberately reproduced in Failure Experiment 4 (3 queries → 8 queries for just 6 rows, and that ratio gets worse, not better, as row count grows).
- **Interview-style question**: "A user reports that opening the project page causes 150 SQL queries. Where would you investigate first, and why?" (This app's own dashboard/project pages were specifically built to avoid this — see the `with(...)` calls throughout `ProjectController`/`TaskController` and the reasoning documented next to each one.)

### Relationships

- **Where it appears**: `belongsTo` (`Task::project()`), `hasMany` (`Project::tasks()`), `belongsToMany` (`Project::members()`, through the `project_user` pivot), `morphTo`/`morphMany` (`ActivityLog::subject()`, `Project::activities()`).
- **Relevant files**: `docs/backend-concepts/database-relationships.md`.
- **What you should understand**: `belongsToMany` needs a pivot table (`project_user`) because a single foreign key column can't represent a many-to-many relationship; `withPivot('role')` is what makes the pivot table's extra columns (like `role`) actually accessible on the returned models, and omitting it silently drops that data from query results even though it's stored.
- **Common mistake**: forgetting `withPivot(...)` for a column the pivot table genuinely has, then being confused why `$user->pivot->role` returns null despite the database row clearly having a value.
- **Interview-style question**: "Why does `activity_logs` use a polymorphic relationship (`subject_type`/`subject_id`) instead of separate `project_id`/`task_id`/`comment_id` columns?"

### SQL

- **Where it appears**: generated entirely by Eloquent (verified throughout the project via `DB::enableQueryLog()` and direct comparison against expected `EXPLAIN`-visible query shapes) — never written as raw strings.
- **Relevant files**: `docs/backend-concepts/eloquent.md`, `docs/backend-concepts/database-indexes.md`.
- **What you should understand**: what SQL a given Eloquent call approximately produces (`->with('owner')` → a single `WHERE id IN (...)` query, not N separate ones), and how to read the actual generated SQL when something seems slow or wrong, rather than guessing.
- **Common mistake**: assuming an ORM abstracts away the need to understand the underlying SQL — every N+1 problem, every constraint violation, every slow query in this project was diagnosed by looking at the actual generated SQL, not by reasoning about Eloquent syntax alone.
- **Interview-style question**: "The application works locally but becomes slow when there are 100,000 tasks. What would you investigate first?" (Start with `EXPLAIN ANALYZE` on the specific slow query, check whether the relevant index — see the Indexes section — is actually being used at that scale, and check for any accidentally-unbounded query, i.e., missing pagination.)

### PostgreSQL

- **Where it appears**: the actual database engine for every environment (dev, test) in this project — verified running natively on this machine back in Phase 1, never swapped for SQLite/MySQL.
- **Relevant files**: ADR 002, `docs/architecture/version-matrix.md`.
- **What you should understand**: real constraint enforcement (foreign keys, `NOT NULL`, unique constraints) happens here, not just in application code — demonstrated concretely by Failure Experiment 5, where removing a foreign key constraint let a genuinely invalid reference (`project_id=999999`, no such project) get inserted with zero complaint from anything else in the stack.
- **Common mistake**: treating "the ORM validated it" and "the database enforces it" as the same guarantee — they're not; a direct SQL insert, a buggy migration, or any code path that bypasses Eloquent's normal flow can only be caught by the database layer, and only if the constraint actually exists.
- **Interview-style question**: "Why does `tasks.project_id` need a foreign key constraint if `StoreTaskRequest` already validates the project exists at creation time?"

### Indexes

- **Where it appears**: every migration in this app — each index has an inline comment explaining the specific query it exists for, per this project's own "no blind indexing" principle.
- **Relevant files**: `docs/backend-concepts/database-indexes.md`.
- **What you should understand**: an index turns "scan every row" into "jump directly to matching rows" for a specific column/query pattern — but only for that pattern; it's not a blanket performance switch, and every index has a write-cost trade-off, which is why this app only indexes columns with an identified, real query need (documented per-column, not applied speculatively).
- **Common mistake**: assuming more indexes are always better — each one slows down every `INSERT`/`UPDATE` to that column, which is why `project_user`'s unique constraint on `(project_id, user_id)` deliberately serves double duty (enforcement AND the lookup index) rather than adding a second, redundant plain index alongside it.
- **Interview-style question**: "`activity_logs` uses `nullableMorphs('subject')`, which auto-creates an index on `(subject_type, subject_id)`. A migration once had a second, manually-added index on the same two columns — was that a good defensive habit or a genuine mistake, and how would you find out?"

### Transactions

- **Where it appears**: `ProjectController::store()` — the one place in this app where multiple related writes are wrapped in `DB::transaction()`.
- **Relevant files**: `docs/backend-concepts/transactions.md`, ADR 008; Failure Experiment 3, the single most consequential experiment performed — removing the transaction and forcing a mid-operation failure left a project that permanently locked out even its own creator, since `ProjectPolicy::view()` requires a membership row the aborted transaction never wrote.
- **What you should understand**: a transaction makes several writes atomic — all succeed or all roll back — and this app deliberately does NOT wrap every multi-write operation in one; only the operation where a partial failure produces a genuinely broken, unrecoverable state gets this treatment (see ADR 008 for the explicit reasoning on when NOT to use one too).
- **Common mistake**: assuming "the operation failed" always means "nothing happened" — without a transaction, whichever writes already executed before the failure point stay permanently committed; this project directly demonstrated a project existing in the database with zero owners as a result.
- **Interview-style question**: "Two users modify the same task at almost exactly the same time. What problems could occur?" (This app's single-transaction pattern protects atomicity of *multi-step* operations by one user, but doesn't address concurrent-write races between two DIFFERENT users updating the same row — a distinct concern this project didn't need to solve at its scale, worth naming as a known gap rather than pretending it's covered.)

### Pagination

- **Where it appears**: `ProjectController::index()` (`paginate(10)`), `TaskController::index()` (`paginate(15)->withQueryString()`).
- **Relevant files**: `docs/backend-concepts/pagination.md`, ADR 009.
- **What you should understand**: offset pagination (`LIMIT`/`OFFSET`, what this app uses) supports "jump to page N" but gets slower at very large offsets; cursor pagination stays flat-speed at any depth but loses that "jump to page N" capability — this app chose offset deliberately because its actual scale (tens to hundreds of rows) never reaches the point where that trade-off matters.
- **Common mistake**: forgetting `->withQueryString()` on a paginated + filtered list — without it, clicking "page 2" silently drops any active `?status=`/`?priority=` filter.
- **Interview-style question**: "At what point would you actually switch this app's Task list from offset to cursor pagination, and what's the concrete signal that tells you it's time?"

### Error Handling

- **Where it appears**: `resources/views/errors/404.blade.php`/`403.blade.php`/`500.blade.php`.
- **Relevant files**: `docs/backend-concepts/error-handling.md`; the `500` page deliberately does NOT extend `<x-layout>`, unlike every other view in this app, since it must render correctly even if auth/session/routing itself is what's broken.
- **What you should understand**: `APP_DEBUG` controls whether exception details leak to the response — verified empirically in this project by actually triggering a real exception with both settings and counting how many times the exception message/class appeared (6 times with `APP_DEBUG=true`, 0 times with `false`), not just assumed from documentation.
- **Common mistake**: leaving `APP_DEBUG=true` in a production-like environment — this project specifically tested and confirmed the difference is real and significant, not a formality.
- **Interview-style question**: "How would you verify — not just assume — that a production deployment of this app doesn't leak stack traces to users?" (Answer: reproduce exactly what this project did — trigger a real error with debug off, confirm the response body contains none of the exception's details.)

### Logging

- **Where it appears**: `Log::warning`/`Log::info` calls in `ProjectController`, `TaskController`, `ProjectMemberController`, `AuthenticatedSessionController`, `LoginRequest`.
- **Relevant files**: `docs/backend-concepts/logging.md` — includes the discovery that zero `Log::` calls existed anywhere in this app until this was specifically audited and fixed, distinct from the separate `ActivityLog` database model (a user-facing audit trail, not the same thing as an application log).
- **What you should understand**: application logs (developer/ops-facing, `storage/logs/laravel.log`) and audit logs (user-facing, the `activity_logs` table) serve different audiences and are NOT interchangeable — project deletion specifically gets a `Log::warning` but deliberately no `ActivityLog` row, since logging an activity entry pointing at a subject about to be deleted would leave a permanently broken reference.
- **Common mistake**: logging sensitive values "just in case they're useful for debugging" — this app's one exception (`LoginRequest` logs the attempted email on a failed login) is deliberate and justified (detecting brute-force patterns), documented explicitly as the one place a PII-adjacent value is logged on purpose, with the password never logged under any circumstance.
- **Interview-style question**: "Why does a failed login log the attempted email address, but a successful login only logs the user ID, not the email?"

### Testing

- **Where it appears**: `tests/Feature/` — 61 tests, all running against real PostgreSQL (`laravel_learning_test`), not SQLite.
- **Relevant files**: ADR 010 (why PostgreSQL, not SQLite, for tests — discovered `pdo_sqlite` wasn't even installed on this machine, and switched to match the project's own stated database preference rather than installing a new system package).
- **What you should understand**: feature tests (full HTTP request through the real middleware/controller/Eloquent stack) were favored throughout this project over isolated unit tests of controllers, because the actual behaviors that matter here (authorization, validation, the request lifecycle) only manifest correctly when the whole stack runs together — a controller method tested in isolation, calling it directly in PHP, wouldn't exercise the Form Request validation, the middleware, or route-model binding at all.
- **Common mistake**: mocking the database in tests that are specifically meant to prove real behavior (like transaction atomicity, or a real foreign-key rejection) — this project's `RefreshDatabase`-based tests against real PostgreSQL were deliberately chosen so a passing test suite actually proves something about production-like behavior.
- **Interview-style question**: "This project's test suite runs against real PostgreSQL rather than an in-memory SQLite database. What specific category of bug would an SQLite-based test suite risk missing entirely?" (Directly answered by Failure Experiment 5 — a foreign-key constraint violation, something SQLite enforces differently, or not at all, depending on configuration.)

### Docker

- **Where it appears**: nowhere in this project, by deliberate choice.
- **Relevant files**: `docs/SETUP.md` (documents the native local setup instead); the original project brief explicitly said "Docker/Sail is optional and must not be introduced unless there is a concrete learning or reproducibility benefit" — no such benefit was identified, since this machine already had a correctly-configured native PostgreSQL 16 instance verified working in Phase 1.
- **What you should understand conceptually, even without using it here**: Docker would package this app's exact runtime environment (PHP version, extensions, PostgreSQL version) into a portable, reproducible container — solving "works on my machine" by making "my machine" itself part of what's shipped. This project's native setup achieves the same reproducibility a different way: `docs/architecture/version-matrix.md` pins exact verified versions, and `docs/SETUP.md` gives exact bootstrap commands, on the assumption that a learner runs this on their own already-provisioned machine rather than needing environment isolation from other projects.
- **Common mistake**: reaching for Docker by default on every project regardless of whether it solves an actual problem you have — this project's own principle ("Do Not Over-Engineer") applied here too: introducing Docker with no environment-portability problem to solve would have been complexity for its own sake.
- **Interview-style question**: "This project explicitly chose NOT to use Docker. Under what concrete circumstance would that decision be wrong, and what would change your mind?" (A second developer joining with a different OS/PHP version already installed; needing to run this app's exact environment on a CI server; wanting to guarantee the dev/test/production environments are byte-for-byte identical rather than merely version-matched.)

---

## How to Use This Report

Each section above links back to a real file in this repository — if any explanation here feels incomplete, the corresponding `docs/backend-concepts/*.md` file (or ADR, for architectural decisions) goes into much more depth, including the full 10-question "what is it / why / how / where / what without it / alternatives / trade-offs / production / verify / test yourself" format used throughout this project.

The next step, when you're ready, is the final backend assessment — see `docs/PROJECT-STATE.md`'s NEXT ACTION for what that covers. Per the original project brief, answers are withheld until you explicitly ask for them.
