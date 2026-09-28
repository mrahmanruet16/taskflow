# 008 — Database Transactions for Project Creation

# Decision

Wrap the "create a project" operation — which is really three related writes (the `projects` row, a `project_user` row making the creator its Owner, and an `activity_logs` row recording the event) — in a single `DB::transaction()` closure, so all three succeed or all three roll back together.

# Context

`ProjectController::store()` needs to: (1) insert the project, (2) attach the creator as a member with the `owner` role, (3) record an activity log entry. Each is a separate SQL `INSERT` against a different table.

# Problem

What happens if the second or third write fails after the first has already succeeded — e.g. a database connection drop between statements, or (more realistically here) a bug that throws an exception partway through? Without any coordination, PostgreSQL commits each `INSERT` independently the moment it runs. A failure after step 1 but before step 3 leaves a project that exists with no owner recorded in `project_user` — silently broken data that nothing in the schema prevents.

# Options Considered

## Option A — No transaction; three independent writes

Advantages:
- Simplest code, no new concept.

Disadvantages:
- Not atomic: a partial failure leaves inconsistent data (a project with no owner membership row, or no creation log entry) — and nothing about the *symptoms* would clearly point back to "the create-project flow was interrupted." A future bug — e.g. `ProjectPolicy::hasMember()` returning false for a project's own creator — could take real debugging effort to trace back to a transaction that was never there.
- This is exactly the failure mode Failure Experiment 3 (see `docs/testing/manual-verification.md`, added later) demonstrates by deliberately removing the transaction and forcing the second write to fail.

## Option B — `DB::transaction(fn () => ...)`

Advantages:
- Atomic: if any statement inside the closure throws, Laravel automatically issues `ROLLBACK` and no partial state is left in the database. If all statements succeed, `COMMIT` runs once at the end.
- Small, explicit, and scoped to exactly the operation that needs it — doesn't wrap unrelated code.

Disadvantages:
- Slightly harder to unit test in total isolation (though feature tests exercise it naturally via HTTP, which is how Laravel's own testing conventions favor testing this kind of flow anyway — see `docs/backend-concepts/testing-strategy.md`, added later).
- Holds a database transaction open for the duration of the closure — for this closure (three simple inserts, no external I/O), that duration is negligible.

# Decision Made

Option B — `DB::transaction()` wrapping all three writes in `ProjectController::store()`.

# Why

The spec explicitly calls out "create project + add creator as owner + create activity log" as the canonical example of an operation that needs a transaction, precisely because it's multiple related writes where partial completion produces meaningfully broken state (an "orphaned" project with no owner). This is also a deliberately *small, scoped* use of transactions — not applied blanket-wide to every write in the app, matching the project's stated principle to avoid unnecessary abstraction ("Do not use transactions everywhere without justification").

# Implementation

`app/Http/Controllers/ProjectController.php::store()` — the three writes (`Project::create`, `$project->members()->attach(...)`, `ActivityLog::create(...)`) all happen inside `DB::transaction(function () { ... })`. The closure's return value (the created `Project`) is captured and used for the redirect after the transaction commits.

# Consequences

- Any future addition to project creation (e.g. sending a welcome notification) that must succeed-or-fail together with these three writes belongs inside the same closure. Anything that's fine to happen independently (e.g. firing an async job) should stay outside it — transactions should stay scoped to what genuinely needs atomicity, not grow to wrap everything nearby "just in case."
- `project_user`'s foreign keys use `cascadeOnDelete()` (see the migration), so even without the transaction, an already-committed orphaned project wouldn't leave a *dangling* foreign key — but it would still leave a project with zero members, which breaks `ProjectPolicy::view()` (nobody, including the creator, could pass `hasMember()` for it) — this is the actual failure mode the transaction prevents, not a foreign-key-constraint violation.

# Verification

```bash
php artisan test --filter=ProjectTest
```
`test_user_can_create_a_project` and related tests implicitly verify the happy path (project + membership + activity log all exist after one request). A dedicated Failure Experiment (temporarily removing the transaction, then forcing the activity-log insert to throw, then checking whether the project and membership rows still exist despite the "failure") is documented in `docs/testing/manual-verification.md` once that document is written.

# When We Would Reconsider This Decision

If project creation grows to include a step that's slow or makes an external network call (e.g. provisioning a third-party integration), that step should move *outside* the transaction (dispatched as a queued job after commit, via `DB::afterCommit()` or an event listener) — holding a database transaction open across slow I/O blocks other connections and should be avoided.
