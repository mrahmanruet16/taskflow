# Database Indexes

## 1. What is it?

An index is a separate data structure PostgreSQL maintains alongside a table, letting it find matching rows without scanning every row in the table — similar to a book's index letting you jump to a page instead of reading cover to cover.

## 2. What problem does it solve?

Without an index on a column a query filters or joins on, PostgreSQL must perform a **sequential scan** — read every single row in the table and check each one against the condition. At small scale (a handful of rows) this is invisible. As a table grows, a sequential scan's cost grows linearly with row count — a query that took 1ms at 100 rows can take seconds at millions of rows, purely because there's no faster way to find the matching rows without an index.

## 3. How does it work?

Every migration in this app that adds `->index('column')` (or gets one automatically via `->constrained()`, which indexes foreign key columns by default) tells PostgreSQL to build and maintain a B-tree index on that column. When a later query does `WHERE column = ?`, PostgreSQL's query planner can use that index to jump directly to matching rows instead of scanning the whole table — turning an O(n) scan into roughly O(log n) lookup.

This app does **not** index columns blindly — the project's own principle is explicit: "Do not add indexes blindly. Every non-obvious index must have a reason documented." Every index in this schema has an inline comment in its migration explaining the specific query it exists for.

## 4. Every index in this schema, and why

| Table | Column(s) | Why |
|---|---|---|
| `projects` | `created_by` | Every dashboard/list query filters "projects I'm a member of" and, before Phase 2.3, "projects I created" — this column is read on nearly every page load. |
| `projects` | `status` | Filtering/grouping by status (dashboard aggregation, future `/projects?status=` filtering). |
| `project_user` | `(project_id, user_id)` — **unique**, not just indexed | Enforces "one role per user per project" at the database level (Eloquent's `attach()` alone wouldn't prevent a duplicate row) AND, as a side effect, this unique constraint IS a usable index for `WHERE project_id = ? AND user_id = ?` lookups — `Project::hasMember()`/`roleOf()` benefit from it on every single Policy check in the app. |
| `activity_logs` | `(subject_type, subject_id)` | Created automatically by `nullableMorphs('subject')` — every project/task activity feed query filters on both columns together. Caught and removed a REDUNDANT manually-added duplicate of this exact index during Phase 2.3 (`nullableMorphs()` already creates it) — a concrete example of checking rather than assuming what a Laravel schema helper does. |
| `tasks` | `project_id` | Every task list (a project's detail page, a project-scoped query) filters on this. |
| `tasks` | `status`, `priority`, `assigned_to` | The spec's explicit required filtering — `/tasks?status=&priority=&assignee=` — queries directly against all three columns; `TaskController::index()` uses exactly this filtering. |
| `comments` | `task_id` | The only query pattern comments have in this app: "comments for this task" (rendered on the task show page). |

Every foreign key column in this schema (`created_by`, `assigned_to`, `user_id`, `task_id`, `project_id` in `project_user`, etc.) is ALSO automatically indexed by Laravel's `->constrained()` helper, even where not called out with an explicit `->index()` — this is standard practice since foreign keys are joined/filtered on constantly.

## 5. What happens without it?

Without the index on `tasks.status`/`tasks.priority`/`tasks.assigned_to`: `TaskController::index()`'s filtering (`?status=in_progress&priority=high`) would still return correct results — indexes never change query *correctness*, only *speed* — but at scale, every filtered task list request would force PostgreSQL to scan the entire `tasks` table, checking every row's status/priority/assignee, instead of jumping straight to matching rows. At this app's actual current data volume (tens of rows), the difference is unmeasurable; the index exists because the spec explicitly requires this filtering as a core feature, and a feature explicitly built for scale deserves the index that makes it actually scale.

## 6. Alternatives

- **Composite indexes** (e.g. one index covering `(project_id, status)` together, rather than two separate single-column indexes) — could speed up queries that filter on both columns simultaneously. Not introduced here because no current query in this app filters `tasks` by `project_id` AND `status` together in a way that would benefit measurably over the two separate indexes already in place — adding one speculatively would violate the project's own "no blind indexing" principle.
- **Full-text search indexes** (e.g. GIN indexes for searching task titles/descriptions) — not implemented; this app has no search feature, only exact-match filtering.

## 7. Trade-offs

Every index has a cost, not just a benefit: it must be updated on every `INSERT`/`UPDATE`/`DELETE` to the indexed column, and it consumes disk space. This is why the project deliberately doesn't index every column "just in case" — each index here corresponds to an actual, identified query pattern in the app, not a speculative one.

## 8. Production considerations

At real production scale, `EXPLAIN ANALYZE` on the actual slow queries (not guessing) is how you'd verify an index is being used and is actually helping — PostgreSQL's query planner sometimes chooses a sequential scan even with an index present, if the table is small enough that a scan is genuinely faster (this app's current tiny data volume means the planner may well prefer a seq scan today regardless of the indexes existing — that's expected and fine; the indexes are there for when volume grows, per the "why" column above).

## 9. How do I verify it?

```bash
psql -h 127.0.0.1 -U laravel_learning -d laravel_learning_dev -c "\d tasks"
```
Lists every index on the `tasks` table (PostgreSQL's `\d` command shows indexes alongside columns and constraints). To see whether a specific query actually uses an index:
```sql
EXPLAIN ANALYZE SELECT * FROM tasks WHERE status = 'in_progress';
```
Look for `Index Scan using tasks_status_index` (index used) vs. `Seq Scan on tasks` (index not used, or table too small for the planner to bother).

## 10. Questions for me

1. `project_user`'s unique constraint on `(project_id, user_id)` serves two purposes at once. What are they, and would a plain (non-unique) index on the same two columns be enough for just one of those purposes but not the other?
2. Why does `nullableMorphs('subject')` create an index automatically, while `foreignId('created_by')->constrained()` also creates one automatically, but a plain `$table->string('status')` does NOT — what's different about how Laravel decides when to auto-index a column?
3. If you ran `EXPLAIN ANALYZE` against this app's current `tasks` table (a few dozen rows) and saw a sequential scan instead of an index scan on a status filter, would that mean the index is broken or unnecessary? What would you check before concluding either way?
4. Which of this schema's indexes would become the MOST valuable first, if this app's task volume grew from dozens to millions of rows — and which would matter least? Why?
