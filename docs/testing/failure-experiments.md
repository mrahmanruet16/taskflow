# Failure Experiments

Five deliberate breaks, each performed against real running code (either the dev database with seeded data, or the isolated test database), observed with real captured output, then cleanly reverted. Every experiment below actually happened — the command output quoted is real, not illustrative. `git diff` was confirmed empty after every code-level revert; schema was confirmed byte-for-byte identical after the one schema-level experiment.

## Experiment 1 — Authorization

**What was broken**: `Gate::authorize('view', $project)` removed from `ProjectController::show()`.

**Automated signal**: `php artisan test --filter=ProjectTest` immediately failed:
```
test_non_owner_cannot_view_the_project
Expected response status code [403] but received 200.
```

**Manual demonstration**: registered a brand-new user (`outsider@example.test`) with zero relationship to any seeded project, then requested a real seeded project's page directly by URL (`GET /projects/16`):
```
GET /projects/16 as outsider: 200
```
The full page rendered — project name, "Members (6)", "Tasks (10)" — and the actual member list leaked every real name on that project:
```
Admin User — Manager User — Member User — Viewer User — Titus Quitzon — Stoltenberg PhD
```

**What became possible**: any authenticated user — regardless of membership — could view any project's full details, member roster (including real names), and task list simply by guessing or incrementing an ID in the URL (`/projects/1`, `/projects/2`, ...). This is a textbook IDOR (Insecure Direct Object Reference) vulnerability. It directly answers the spec's own scenario question: *"A user manually changes `/projects/10` to `/projects/11` in the URL. What prevents unauthorized access?"* — the answer is exactly the one line removed here, and nothing else in the request pipeline would have caught it (authentication middleware only confirms *someone* is logged in, not that *this* person may see *this* resource).

**Reverted**: line restored, `git diff` empty, `ProjectTest` back to 12/12 passing. Throwaway outsider account deleted.

---

## Experiment 2 — Validation

**What was broken**: `'required'` removed from `StoreProjectRequest`'s `name` rule (kept `'string', 'max:255'`).

**Manual demonstration**: submitted `POST /projects` with the `name` field omitted entirely (not even an empty string — absent from the request body):
```
STATUS: 500
```
The response body contained:
```
SQLSTATE[23502]: ... violates not-null constraint
```

**What reached the database**: nothing successfully — PostgreSQL's `NOT NULL` constraint on `projects.name` (from the original migration) rejected the `INSERT` at the database layer. Confirmed directly:
```sql
SELECT count(*) FROM projects WHERE name IS NULL;
-- 0
```

**Why this matters**: the *data* stayed correct — no broken row was created — but the *user experience* degraded from a friendly, specific 422-style redirect ("The name field is required") to a raw, unhandled 500 error exposing a PostgreSQL error code. This is the concrete illustration of why validation and database constraints are complementary, not redundant: the constraint is the last line of defense for data integrity; validation is what makes failure *graceful* rather than *catastrophic*. Removing validation doesn't corrupt your data if your constraints are solid — but it does turn every mistake into an ugly crash instead of a helpful message.

**Reverted**: `'required'` restored, `git diff` empty, `ProjectTest` back to 12/12 passing. No cleanup needed (nothing was actually inserted).

---

## Experiment 3 — Transactions

**What was broken**: `DB::transaction()` removed from `ProjectController::store()`; a forced `RuntimeException` inserted between the `Project::create()` call and the `project_user`/`activity_logs` writes.

**Automated signal**: `php artisan test --filter=ProjectMemberTest` immediately failed:
```
test_creating_a_project_creates_membership_and_activity_log_atomically
Failed asserting that a row in the table [project_user] matches the attributes ...
The table is empty.
```

**Manual demonstration**: submitted a real `POST /projects` request, which returned `500` (the forced exception) — then queried the database directly:
```sql
SELECT id, name, created_by FROM projects WHERE name='Broken Transaction Project';
--  23 | Broken Transaction Project | 44

SELECT pu.* FROM project_user pu JOIN projects p ON p.id=pu.project_id WHERE p.name='Broken Transaction Project';
-- (0 rows)
```
The project row survived the failed request. It has zero members.

**The concrete, permanent consequence**: the project's own creator then tried to view their own project:
```
GET /projects/23 (as the creator): 403
```
**Even the creator was locked out of a project they just created**, because `ProjectPolicy::view()` requires an actual `project_user` row, and none exists — the transaction that would have guaranteed one never ran. This project is permanently unrecoverable through the application UI: nobody can view it, edit it, add members to it, or delete it, because every one of those actions also requires passing a membership check that can never succeed. Only direct database intervention could fix or remove it.

**Reverted**: transaction and both writes restored, `git diff` empty, full suite back to 61/61 passing. Orphaned project and test user deleted from the database.

---

## Experiment 4 — N+1 Queries

**What was broken**: `->with('owner')` removed from `ProjectController::index()`.

**Baseline measurement (WITH eager loading, unmodified code)**, run against the real seeded admin account (6 projects):
```
WITH eager loading: 3 queries
```

**After removing eager loading**, measured by calling the actual `ProjectController::index()` method (not a simulation) against the same real data:
```
WITHOUT eager loading (real controller call): 8 queries for 6 projects
  [0] select count(*) ... (pagination count)
  [1] select "projects".* ... (the project list itself)
  [2] select * from "users" where "users"."id" = ? limit 1
  [3] select * from "users" where "users"."id" = ? limit 1
  [4] select * from "users" where "users"."id" = ? limit 1
  [5] select * from "users" where "users"."id" = ? limit 1
  [6] select * from "users" where "users"."id" = ? limit 1
  [7] select * from "users" where "users"."id" = ? limit 1
```
Six separate, individual `SELECT * FROM users WHERE id = ?` queries — one per project's owner, resolved lazily the moment each was accessed in the Blade loop.

**Why this matters at scale**: 8 queries for 6 projects (2 base + 6 lazy) will become 2 + N queries for any N — at 100 projects, 102 queries instead of 3; the query count scales linearly with row count purely because eager loading was removed, with zero change in what data is ultimately displayed. See `docs/backend-concepts/eloquent.md` for the general pattern and a second independent measurement of the same phenomenon.

**Reverted**: `->with('owner')` restored, `git diff` empty, full suite back to 61/61 passing.

---

## Experiment 5 — Database Constraint

**What was broken**: the `tasks_project_id_foreign` foreign key constraint, dropped directly via `ALTER TABLE` against the isolated test database (`laravel_learning_test` — deliberately not the dev database with real seed data, since this experiment mutates schema).

**Schema snapshot taken first** (`\d tasks`), confirmed identical again at the end — the revert was verified as byte-for-byte, not just "looks fine."

**Manual demonstration**: with the constraint gone, inserted a task pointing at a project ID that does not exist anywhere in the database:
```sql
INSERT INTO tasks (project_id, created_by, title, ...) VALUES (999999, 80, 'Orphaned Task - Experiment 5', ...) RETURNING id, project_id, title;
--  33 | 999999 | Orphaned Task - Experiment 5
```
It succeeded. Nothing in Eloquent, the application code, or (with the constraint removed) the database itself checks that `project_id` actually refers to a real project.

**The application-level consequence**, demonstrated by loading this exact row through Eloquent:
```php
$task = App\Models\Task::find(33);
$task->project;  // NULL — belongsTo returns null for a missing row, it does not throw
$task->project->name;  // WARNING: Attempt to read property "name" on null
```
Every view in this app (`tasks/show.blade.php`, `projects/show.blade.php`'s task list, `tasks/index.blade.php`) accesses `$task->project` or a task's project-derived data unconditionally, assuming it always exists — because normally, the foreign key constraint makes that assumption safe. With the constraint gone, this single orphaned row would crash any page that tried to render it.

**Why database-level constraints still matter even with application validation**: this row was never created through the application (no controller, no Form Request, no Eloquent `create()` call with validated input) — it was inserted directly via SQL, exactly the kind of write that application-level validation can never see or prevent. A future migration bug, a bulk-import script, a direct database fix gone wrong, or any code path that doesn't go through the normal validated flow could introduce exactly this kind of corruption. The foreign key constraint is the only layer that protects against *all* of these, regardless of how the bad data was attempted.

**Reverted**: orphaned task and throwaway user deleted, constraint re-added with the exact original definition, schema confirmed byte-for-byte identical to the pre-experiment snapshot, full test suite (61/61) and Pint confirmed passing afterward.

---

## Summary

| # | Broke | Real observed consequence | Layer that would have prevented it |
|---|---|---|---|
| 1 | Policy check | Any user could view any project's full details + member names via direct URL | Application-level authorization (Policy) |
| 2 | Validation rule | 500 error instead of a friendly message (data itself stayed safe) | Request validation (Form Request) |
| 3 | Transaction | A project permanently locked out of its own creator | Database transaction (atomicity) |
| 4 | Eager loading | Query count scales linearly with row count (3 → 8 for just 6 rows) | Eloquent eager loading (`with()`) |
| 5 | Foreign key constraint | An orphaned reference that crashes any page rendering it | Database-level constraint |

Every experiment used the actual application code and, where practical, real seeded data — not synthetic examples constructed to make a point. Every revert was verified, not assumed: `git diff` checked empty after code changes, schema diffed byte-for-byte after the constraint change, and the full test suite (61/61) confirmed passing after every single experiment before moving to the next.
