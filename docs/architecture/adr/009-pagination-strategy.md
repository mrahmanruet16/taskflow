# 009 — Pagination Strategy: Offset Pagination via `paginate()`

# Decision

Use Laravel's standard offset-based `paginate()` (not cursor pagination) for all list views (Projects now; Tasks/Comments/Activity Logs in later phases).

# Context

`ProjectController::index()` lists a user's projects. Even at this app's small demo scale, an unbounded `->get()` would return every row in one response — fine for 5 projects, a real problem once seed data reaches "50+ tasks, 100+ comments" (per the spec's seeding requirements) or beyond.

# Problem

How should large lists be split across pages, and which of Laravel's two built-in pagination strategies fits this app?

# Options Considered

## Option A — Offset pagination (`Model::paginate($perPage)`)

Advantages:
- One method call; Blade rendering (`$projects->links()`) is built in.
- Supports "jump to page N" — useful for a small admin-style list.
- `total()`/`lastPage()` are available, letting the UI show "Page 2 of 5."

Disadvantages:
- Under the hood, uses `OFFSET`/`LIMIT` in SQL — for very large offsets (e.g. page 10,000), the database still has to scan and discard all preceding rows, making later pages progressively slower.
- Can show duplicate/skipped rows if data is inserted/deleted between page loads (the "page 2" the user clicks might not be the same "page 2" that existed when they loaded page 1).

## Option B — Cursor pagination (`Model::cursorPaginate($perPage)`)

Advantages:
- Uses a `WHERE id > :last_seen_id ORDER BY id LIMIT :n`-style query instead of `OFFSET` — performance stays flat regardless of how deep into the list you go.
- No duplicate/skipped-row problem from concurrent inserts/deletes.

Disadvantages:
- No "jump to page N" or total page count — only "next"/"previous," because a cursor only knows its neighbors, not its absolute position.
- Slightly less familiar UI pattern for a simple internal tool.

# Decision Made

Option A (offset pagination) for now.

# Why

At this app's actual scale (a learning project's seed data — tens to low hundreds of rows per list), offset pagination's performance downside doesn't materialize: `OFFSET 40 LIMIT 10` is trivial at that size. The "jump to a specific page" UX and simpler `$projects->links()` Blade integration are worth more here than cursor pagination's large-scale performance guarantee, which this app will never actually stress-test.

# Consequences

- If any list in this app later needs true "infinite scroll" UX or must handle tens of thousands+ of rows per user, cursor pagination becomes the better fit for *that specific list* — this is a per-list decision, not necessarily an app-wide one.
- Every paginated query in this app follows the same pattern (`->paginate(N)` in the controller, `{{ $collection->links() }}` in the view) for consistency.

# Verification

`php artisan test --filter=ProjectTest` doesn't currently assert pagination directly (12 projects or fewer in any test). Manual check: seed 15+ projects for one user (once seeders exist), visit `/projects`, confirm only 10 show per page and page-2 navigation works.

# When We Would Reconsider This Decision

If a list in this app is expected to regularly exceed several thousand rows per user, or if "jump to page N" stops being a meaningful UX (e.g. a live activity feed where only "load more" makes sense).
