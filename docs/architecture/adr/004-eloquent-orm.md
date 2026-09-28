# 004 — Eloquent ORM, No Repository Pattern

# Decision

Use Eloquent (Laravel's Active Record ORM) directly in controllers and Form Requests for all database access. No repository layer wrapping Eloquent, no raw SQL strings anywhere in the codebase.

# Context

The org-level guidelines this session operates under state explicitly: "NEVER use raw SQL strings directly — use parameterised queries, Eloquent, or `$wpdb->prepare()`." The project brief separately has an entire "Eloquent Learning Requirement" section demanding the app teach `with()`, `where()`, `find()`, `findOrFail()`, `create()`, `update()`, `delete()`, relationships, and the N+1 query problem — and its "Important Architecture Principle" section explicitly warns against introducing "repositories, unnecessary interfaces, or complicated design patterns simply because they are considered 'enterprise architecture.'"

# Problem

What should sit between controllers and the PostgreSQL database — should there be an abstraction layer (repository pattern) between Eloquent and application code, or should Eloquent be used directly?

# Options Considered

## Option A — Raw SQL / plain PDO

Advantages:
- Full control over every generated query.
- No "magic" — the exact SQL is visible in the code.

Disadvantages:
- Explicitly forbidden by the org's security guidelines (raw SQL strings are a SQL-injection risk if any value is ever interpolated instead of bound).
- Would require hand-writing every JOIN for the relationships this app has (Project↔User membership, Task↔Project, Comment↔Task, polymorphic ActivityLog) — exactly the boilerplate Eloquent exists to eliminate, with no corresponding safety or clarity benefit for this app's needs.
- Completely misses the brief's explicit requirement to teach Eloquent specifically, not SQL in isolation.

## Option B — Repository pattern (interfaces + repository classes wrapping Eloquent, e.g. `ProjectRepositoryInterface` / `EloquentProjectRepository`)

Advantages:
- Common "enterprise" pattern; theoretically allows swapping the underlying persistence mechanism without touching calling code.
- Can centralize commonly-repeated queries in one place.

Disadvantages:
- Explicitly warned against in the project brief's own architecture principles ("Do not introduce repositories, unnecessary interfaces... simply because they are considered 'enterprise architecture'").
- The swappable-persistence benefit is theoretical here — this app will never swap Eloquent for a different persistence mechanism, so the abstraction pays for itself in extra files and indirection with no corresponding real benefit.
- Would hide the exact Eloquent method calls (`with()`, `where()`, relationship traversal) that the brief explicitly wants visible and explained — an interface boundary between controllers and Eloquent would work directly against the "Eloquent Learning Requirement" section's goal of understanding what SQL Eloquent generates and when eager loading is needed.

## Option C — Eloquent directly, with light logic-bearing helper methods on the models themselves (e.g. `Project::hasMember()`, `Task::isOverdue()`)

Advantages:
- Matches both the org's ORM requirement and the brief's explicit anti-repository guidance.
- Every controller in this app reads as: validate (Form Request) → authorize (Policy) → touch Eloquent directly → render (Blade) — a short, traceable path matching `docs/architecture/request-lifecycle.md`.
- Small, well-named model methods (`hasMember()`, `roleOf()`, `isOverdue()`) keep genuinely reusable *logic* (not just queries) in one place without introducing a whole abstraction layer — a middle ground between "everything inline in controllers" and "full repository pattern."

Disadvantages:
- Models can accumulate logic over time ("fat models") if not kept disciplined — mitigated here by only adding methods that are genuinely reused (each one added in this app was added because a real second call site needed it, not speculatively).
- Slightly less "swappable" than a repository-abstracted design — an accepted, deliberate trade-off given Option B's stated disadvantages.

# Decision Made

Option C — Eloquent directly, with small logic-bearing helper methods on models where genuinely reused.

# Why

This is simultaneously the org's security requirement (no raw SQL), the brief's explicit teaching requirement (Eloquent, not SQL abstracted away or hidden behind an interface), and the brief's explicit anti-over-engineering guidance (no repository pattern). All three point the same direction.

# Consequences

- Every model in this app (`Project`, `Task`, `Comment`, `ActivityLog`, `User`) is a normal Eloquent model with typed relationship methods — documented exhaustively in `docs/backend-concepts/database-relationships.md` and `docs/backend-concepts/eloquent.md` (including a real, measured N+1 demonstration).
- Controllers call Eloquent directly: `Project::create([...])`, `Auth::user()->projects()->with('owner')->paginate(10)`, etc. — visible and traceable, not hidden behind a repository interface.
- A small number of model methods encode genuinely reusable logic: `Project::hasMember()`/`roleOf()` (used by `ProjectPolicy`, `TaskPolicy`, `CommentPolicy`, and multiple controllers), `Task::isOverdue()` (used by `DashboardController`'s overdue-count query logic and available for future task-list badge display). Each exists because a second real call site needed the same logic, not speculatively.
- No `app/Repositories/` directory, no repository interfaces, anywhere in this codebase.

# When We Would Reconsider This Decision

If a specific query became complex enough that Eloquent's query builder produced measurably worse SQL than a hand-tuned raw expression (e.g. a reporting query with window functions) — Laravel supports dropping to raw SQL fragments *within* an Eloquent query (`DB::raw()`, still parameterized) for exactly that case, without abandoning Eloquent for the rest of the app. No such case exists in TaskFlow as built.
