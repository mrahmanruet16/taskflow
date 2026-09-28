# Eloquent

## 1. What is it?

Eloquent is Laravel's Active Record ORM (Object-Relational Mapper) — each model class (`Project`, `Task`, `User`) maps to a database table, each model instance maps to a row, and model methods generate SQL for you (`Project::find(1)`, `$project->tasks`, `Task::create([...])`).

## 2. What problem does it solve?

Without an ORM, every database interaction means hand-writing SQL strings, manually binding parameters (to avoid SQL injection — a hard requirement in this codebase per the org guidelines), and manually mapping result rows back into PHP objects. Eloquent handles all three, and lets relationships (see `docs/backend-concepts/database-relationships.md`) be expressed as PHP method calls instead of repeated JOIN clauses.

## 3. Core methods used throughout this app

- **`find($id)`** — `User::find(4)` returns the model or `null` if not found. Used when "not found" is a valid, expected outcome.
- **`findOrFail($id)`** — used implicitly by Laravel's route-model binding (`Route::get('/tasks/{task}', ...)` type-hinting `Task $task` in the controller signature triggers this automatically). Throws `ModelNotFoundException`, which Laravel's exception handler converts to an HTTP 404 — see `docs/backend-concepts/error-handling.md`.
- **`where(...)`** — builds a `WHERE` clause; e.g. `Task::where('status', 'todo')` → `SELECT * FROM tasks WHERE status = ?` (the `?` is a bound parameter, not string-interpolated — this is what makes Eloquent queries safe against SQL injection by default).
- **`create([...])`** — `Project::create(['name' => ..., 'created_by' => ...])` generates an `INSERT`. Only succeeds for attributes listed in the model's `#[Fillable(...)]` attribute (see `docs/backend-concepts/validation.md` on why `$request->validated()` — not `$request->all()` — is what gets passed here).
- **`update([...])`** — `$task->update([...])` generates an `UPDATE ... WHERE id = ?` for the already-loaded model.
- **`delete()`** — `$project->delete()` generates a `DELETE`. Cascading behavior (whether related rows are also deleted) is controlled at the *database* level via each migration's `cascadeOnDelete()`/`restrictOnDelete()`/`nullOnDelete()` — not by Eloquent — see `docs/backend-concepts/database-relationships.md`.
- **`with(...)`** — eager loading, the subject of the rest of this document.

No query scopes are currently defined in this app (none of the current queries repeat often enough across different controllers to justify one yet — see section 6, "Alternatives," for when a scope would be worth introducing).

## 4. Where is it used in this project?

Every controller in `app/Http/Controllers/` uses Eloquent for all database access — no raw SQL strings exist anywhere in this codebase (a hard org requirement). Representative examples: `ProjectController::index()`, `TaskController::index()` (see section 5 for its filtering logic), `TaskController::store()`.

## 5. Eager Loading vs. Lazy Loading — the N+1 Problem, Measured

This is not a hypothetical — the following was run against this actual codebase, with `DB::enableQueryLog()`, seeding 3 real projects with 2 tasks each via `php artisan tinker`.

### Without eager loading (`Project::all()`, then accessing relations per-row)

```php
$projects = Project::all();
foreach ($projects as $p) {
    $p->tasks;   // triggers a query PER project
    $p->owner;   // triggers ANOTHER query PER project
}
```

**Result: 7 queries for 3 projects.**

```sql
[0] select * from "projects"
[1] select * from "tasks" where "tasks"."project_id" = ? and "tasks"."project_id" is not null
[2] select * from "users" where "users"."id" = ? limit 1
[3] select * from "tasks" where "tasks"."project_id" = ? and "tasks"."project_id" is not null
[4] select * from "users" where "users"."id" = ? limit 1
[5] select * from "tasks" where "tasks"."project_id" = ? and "tasks"."project_id" is not null
[6] select * from "users" where "users"."id" = ? limit 1
```

Query `[0]` fetches the N projects. Then, because the loop accesses `->tasks` and `->owner` on each project individually, Eloquent has no way to know in advance that you'll want that data for *every* row — so it fetches it one row at a time, the moment each relation is first accessed. That's **1 initial query + N rows × 2 relations = 1 + (3×2) = 7 queries.** At 100 projects, this becomes 201 queries. At 1,000 projects, 2,001 queries — the query count scales linearly with row count, which is the actual defining characteristic of "N+1": not "exactly N+1" as a literal count, but "1 initial query plus one additional query per row per relation accessed."

### With eager loading (`Project::with(['tasks', 'owner'])->get()`)

```php
$projects = Project::with(['tasks', 'owner'])->get();
foreach ($projects as $p) {
    $p->tasks;   // already loaded, no query
    $p->owner;   // already loaded, no query
}
```

**Result: 3 queries for the same 3 projects — regardless of how many projects there are.**

```sql
[0] select * from "projects"
[1] select * from "tasks" where "tasks"."project_id" in (4, 5, 6)
[2] select * from "users" where "users"."id" in (9)
```

`with('tasks')` runs ONE query that fetches every task for every project in the result set at once (`WHERE project_id IN (4, 5, 6)`), then Eloquent distributes each task to the correct project in PHP memory. `with('owner')` does the same for the owning users. This is **1 initial query + 1 query per relation = 3 queries, flat, no matter how many projects exist.**

### Where this matters in the actual app (not hypothetical)

- `ProjectController::index()` — eager-loads `with('owner')` because the project list prints `$project->owner->name` per row
- `ProjectController::show()` — eager-loads `['members', 'activities.user', 'tasks.assignee']` because the project detail page renders all three
- `TaskController::index()` — eager-loads `['project', 'assignee']` because the global task list prints both per row

All of these were written with eager loading **from the start** in this codebase, not discovered as a bug and fixed later — but the measurement above is exactly the check that would catch it if one were ever missed: enable query logging, look at the count, and ask "does this number grow with the row count, or stay flat?"

## 6. Alternatives

- **Lazy eager loading** (`$projects->load('tasks')` after the fact, on an already-fetched collection) — useful when you don't know until runtime whether you'll need a relation; not used here since every view in this app knows upfront exactly what it needs to render.
- **Query scopes** (`Project::active()->get()` via a `scopeActive()` method) — not yet introduced in this app; would be worth adding if a `where('status', 'active')`-style filter started repeating across 3+ different places (none currently does).
- **Raw SQL / query builder without Eloquent** (`DB::table('projects')->get()`) — bypasses model casts, relationships, and events entirely; explicitly disallowed by the org's "never use raw SQL strings" rule anyway, and would lose the `ProjectStatus`/`TaskStatus` enum casting this app relies on throughout.

## 7. Trade-offs

Eager loading fetches data you might not end up using on every code path (e.g. `with('activities.user')` loads every activity log entry even if the page never scrolls to see them) — a deliberate trade-off of "slightly more data transferred" for "predictably few queries." The alternative (lazy-loading only what's accessed) trades unpredictable query counts for slightly less data per request — worse for this app's actual usage pattern, where nearly every field eager-loaded IS rendered on the page.

## 8. Production considerations

At real scale, the `IN (4, 5, 6)`-style eager-load query itself needs to stay bounded — eager-loading a relation for 10,000 rows in one page load (e.g. an unpaginated `Project::with('tasks')->get()`) would generate one query with a 10,000-item `IN` clause, which is its own performance problem. This is why every list view in this app also uses `paginate()` (see ADR 009) — eager loading and pagination work together: pagination bounds *how many* parent rows exist per page, eager loading bounds *how many queries* per page, regardless of how many rows are on it.

## 9. How do I verify it?

Reproduce the exact measurement above:
```bash
php artisan tinker
```
```php
use Illuminate\Support\Facades\DB;
DB::enableQueryLog();
$projects = App\Models\Project::all();
foreach ($projects as $p) { $p->tasks; $p->owner; }
count(DB::getQueryLog()); // grows with project count — N+1

DB::flushQueryLog();
$projects = App\Models\Project::with(['tasks', 'owner'])->get();
foreach ($projects as $p) { $p->tasks; $p->owner; }
count(DB::getQueryLog()); // stays flat — eager loaded
```

Or in a real request: set `LOG_LEVEL=debug` and inspect Laravel's query log, or use `DB::listen(fn ($query) => logger($query->sql))` temporarily in a controller to see exactly what runs per request.

## 10. Questions for me

1. `ProjectController::show()` eager-loads `tasks.assignee` (dot notation) rather than just `tasks`. What's the difference, and what would break (or just get slower) if it were `with('tasks')` alone while the view still printed `$task->assignee?->name`?
2. The eager-loading measurement showed 3 queries for 3 projects with 2 relations each. If a 4th relation (say, `comments`) were added to that same `with([...])` call, how many total queries would you expect — and why is the growth "+1 per relation," not "+1 per relation per row"?
3. Why does `Project::all()` followed by a loop that does NOT touch `->tasks` or `->owner` at all still only cost exactly 1 query? What specifically triggers the extra queries in the lazy-loading case — is it the loop itself, or something else?
4. If you ran the "without eager loading" measurement against 500 projects instead of 3, roughly how many queries would you expect, and what does that number's *growth rate* (not just its size) tell you about why this is called an "N+1" problem rather than just "a slow query"?
