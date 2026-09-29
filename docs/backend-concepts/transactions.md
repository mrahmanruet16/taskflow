# Transactions

## 1. What is it?

A database transaction is a group of operations that either ALL succeed together, or ALL fail together — there is no partial outcome. `DB::transaction(fn () => { ... })` in Laravel wraps a closure so that if anything inside it throws, everything already done inside that closure is automatically rolled back.

## 2. What problem does it solve?

Some real-world operations are actually multiple related database writes that only make sense together. This app's clearest example, `ProjectController::store()`, is really three writes: insert the `projects` row, insert a `project_user` row making the creator its Owner, and insert an `activity_logs` row recording the creation. Without a transaction, PostgreSQL commits each `INSERT` the instant it runs — if the second or third write fails (a bug, a dropped connection, anything), the first write has already permanently happened. The result: a project exists in the database with **no owner membership row** — a state nothing in the schema prevents, and one that silently breaks `ProjectPolicy::view()` (which requires `hasMember()` to return true for someone, including the creator) for that project forever.

## 3. How does it work?

```php
$project = DB::transaction(function () use ($request) {
    $project = Project::create([...]);
    $project->members()->attach(Auth::id(), ['role' => ProjectRole::Owner->value]);
    ActivityLog::create([...]);
    return $project;
});
```

Under the hood, PostgreSQL is told `BEGIN` before the closure runs. If the closure completes without throwing, Laravel issues `COMMIT` — all three writes become permanent together, atomically. If ANY statement inside throws (a validation failure that somehow got past the Form Request, a constraint violation, an unexpected exception), Laravel catches it, issues `ROLLBACK`, and re-throws the original exception — the database is left exactly as if none of the three writes had ever been attempted, not partially completed.

**Atomicity** is the technical term for this "all or nothing" property — one of the four ACID guarantees relational databases provide (Atomicity, Consistency, Isolation, Durability). This doc focuses on atomicity since it's the property this app's transaction usage directly demonstrates.

## 4. Where is it used in this project — and, just as importantly, where it's deliberately NOT used

**Used**: `ProjectController::store()` (see ADR 008 for the full decision record). That's the only `DB::transaction()` call in this codebase.

**Deliberately not used elsewhere**, and why each case is safe without one:
- `ProjectController::update()` — a single `$project->update([...])` call, followed by a single `ActivityLog::create([...])`. Two writes, but if the second (the activity log) failed after the first (the project update) succeeded, the result is a correctly-updated project with a missing log entry — a minor, non-corrupting gap, not the same category of problem as a project with zero owners. Not wrapped in a transaction because the cost (added complexity) doesn't buy a meaningfully better outcome.
- `TaskController::store()` — a single `Task::create([...])` plus a single `ActivityLog::create([...])`, same reasoning as above.
- `ProjectMemberController::destroy()` — `$project->members()->detach(...)` plus an `ActivityLog::create([...])`, same reasoning.

This selective usage is itself the point: the project's own principle is "Identify operations that genuinely require transactions... Do not use transactions everywhere without justification." Only the one operation where a partial failure produces a genuinely broken, hard-to-recover state (an ownerless project) gets one.

## 5. What happens without it? (What Failure Experiment 3 will demonstrate)

The spec's planned Failure Experiment 3 is exactly this: temporarily remove the `DB::transaction()` wrapper from `ProjectController::store()`, then force the SECOND operation (the `attach()` call) to fail on purpose (e.g. by temporarily making `Auth::id()` return an invalid user ID that violates `project_user`'s foreign key constraint). With the transaction removed, the project row would still exist in the database — created successfully by the first statement — while no membership row exists. Querying `Project::find($id)` afterward would find a project with zero members, and every `ProjectPolicy` check for it would fail for everyone, including its own creator, since `hasMember()` requires an actual `project_user` row that was never written.

## 6. Alternatives

- **Manual `try`/`catch` with explicit `DB::beginTransaction()`/`DB::commit()`/`DB::rollBack()`** — functionally equivalent to `DB::transaction(fn () => ...)`, but requires remembering to call `rollBack()` in every catch path and re-throw correctly. `DB::transaction()`'s closure form does this automatically and is less error-prone — chosen here for that reason.
- **Application-level "eventual consistency" checks** (e.g. a scheduled job that finds and fixes ownerless projects after the fact) — treats the symptom instead of preventing the cause; adds an entire second mechanism (the job) to compensate for a problem a transaction prevents outright, for free, at write time.

## 7. Trade-offs

A transaction holds a database connection/lock for its full duration — for this app's three simple `INSERT`s (no external I/O, no slow computation inside the closure), that duration is negligible. If a transaction's closure ever needed to make a slow external call (an API request, a queued job dispatch), that call should move OUTSIDE the transaction (e.g. dispatched via `DB::afterCommit()`) — holding a database transaction open across slow I/O blocks other connections waiting on the same rows/table, a real production concern this app doesn't currently have (nothing in `ProjectController::store()`'s transaction is slow).

## 8. Production considerations

At scale, the specific risk this app's transaction protects against (a multi-write operation completing partially due to one write failing) becomes MORE likely, not less — more concurrent load means more chances for a deadlock, a connection drop, or a constraint violation mid-operation. The one transaction this app has exists precisely because that risk, however small today, corresponds to a genuinely broken state if it ever occurs.

## 9. How do I verify it?

Automated: `tests/Feature/ProjectMemberTest.php::test_creating_a_project_creates_membership_and_activity_log_atomically` asserts all three rows exist after one `POST /projects` request — proves the happy path writes atomically, though it doesn't (yet) prove the rollback behavior on failure.

Manual (this IS Failure Experiment 3, to be run when that phase is reached): temporarily remove the `DB::transaction()` wrapper, force the second write to fail, observe that the project row exists but the membership row does not — then revert the change.

## 10. Questions for me

1. `ProjectController::update()` does two writes without a transaction, while `store()` wraps three writes in one. What's the actual difference in risk between these two cases that justifies treating them differently, rather than either wrapping everything or wrapping nothing?
2. If the THIRD write inside `store()`'s transaction (the `ActivityLog::create()`) were the one that failed, rather than the second, what would happen to the already-created `Project` row and the already-attached `project_user` row? Would they survive, or roll back too?
3. `DB::transaction()`'s closure returns `$project` at the end. Does that return happen before or after the `COMMIT` is issued? Why does the answer matter for code that runs immediately after the `DB::transaction()` call (like the `redirect()->route('projects.show', $project)` line)?
4. Why does holding a transaction open across a slow external API call cause problems for OTHER requests, not just the one making the call? What's actually being held during that time?
