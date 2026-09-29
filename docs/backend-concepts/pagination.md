# Pagination

## 1. What is it?

Pagination splits a large result set into smaller "pages" — instead of returning every matching row in one response, a query returns a bounded slice (e.g. 10 or 15 rows) plus metadata about how to get the next slice.

## 2. What problem does it solve?

Without pagination, `Project::all()` or `Task::all()` returns EVERY row matching the query in one response — fine at a handful of rows, but as the table grows (the spec's own seed-data target is 50+ tasks, and a real deployment could have far more), an unbounded query means an unbounded response: more memory to build the response, more data to transmit, more HTML for the browser to render in one page load. Pagination caps all three at a fixed, predictable size regardless of how much data actually exists.

## 3. How does it work?

This app uses Laravel's built-in offset pagination — a single method call:

```php
$projects = Auth::user()->projects()->with('owner')->latest('projects.created_at')->paginate(10);
```

Under the hood, `paginate(10)` runs (approximately) two queries: one `SELECT COUNT(*) ...` to know the total row count (needed for "Page 2 of 5"-style UI), and one `SELECT * ... LIMIT 10 OFFSET ?` to fetch just the current page's rows — `OFFSET` is calculated from the current page number (`?page=2` → `OFFSET 10` for a page size of 10). The returned `LengthAwarePaginator` object knows the total count, current page, and last page, and `{{ $projects->links() }}` in the Blade view renders the actual page-number navigation automatically.

`TaskController::index()` additionally calls `->withQueryString()` on its paginator — this preserves the current filter query parameters (`?status=&priority=&assignee=&sort=`) across page-number links, so clicking "page 2" doesn't silently drop an active filter.

## 4. Where is it used in this project

- `ProjectController::index()` — `->paginate(10)`, since Phase 2.2.
- `TaskController::index()` — `->paginate(15)->withQueryString()`, since Phase 2.4, combined with the spec's required filtering/sorting (see `docs/backend-concepts/eloquent.md` and ADR 009 for the full offset-vs-cursor decision).
- Both `resources/views/projects/index.blade.php` and `resources/views/tasks/index.blade.php` render `{{ $collection->links() }}`, which produces the actual clickable page-number UI.

## 5. What happens without it?

If `ProjectController::index()` used `->get()` instead of `->paginate(10)`, the query would return every project the user is a member of, all at once. At this app's current data volume, invisible. At the spec's stated seed-data volume (5+ projects per typical demo user, but potentially far more for a heavy user in a real deployment) or beyond, the page would grow proportionally to however many projects that user has — no upper bound on response size, and every row rendered into the HTML table regardless of whether the user is looking at it.

## 6. Alternatives — Offset vs. Cursor Pagination

**Offset pagination** (what this app uses): `LIMIT n OFFSET m`. Simple, supports jumping to an arbitrary page number, and Laravel's `paginate()` handles it with zero extra code. Its real downside only appears at large offsets: `OFFSET 10000 LIMIT 10` still requires the database to scan through (and discard) the first 10,000 matching rows before returning the next 10 — the further into the list you page, the more work each page costs, even though each page returns the same 10 rows.

**Cursor pagination** (Laravel's `cursorPaginate()`): instead of "skip N rows," a cursor query says "give me the next 10 rows after the one I last saw" — `WHERE id > :last_seen_id ORDER BY id LIMIT 10`. This stays equally fast on page 1 and page 10,000, because it's always an indexed lookup for "rows after X," never a count-and-skip. The trade-off: no "jump to page 47" UI (a cursor only knows its immediate neighbors, not its absolute position in the list), and concurrent inserts/deletes can't shift already-issued cursor pages the way they can subtly duplicate/skip rows under offset pagination.

## 7. Why offset was chosen here (see ADR 009 for full reasoning)

At TaskFlow's actual scale — a learning project's seed data, tens to low hundreds of rows per list — offset pagination's large-offset performance cost never materializes; `OFFSET 40 LIMIT 15` is trivially fast regardless of table size at that volume. The "jump to a specific page" UX offset pagination provides was judged more valuable here than a performance guarantee this app will never actually stress-test.

## 8. Trade-offs

Offset pagination's `COUNT(*)` query (needed to compute "Page 1 of N") itself has a cost that grows with table size — at very large scale, some systems skip the exact count and show "many results" instead of a precise page count, specifically to avoid that cost. Not a concern at this app's scale.

## 9. How do I verify it?

```bash
php artisan tinker
```
```php
$sql = App\Models\Task::query()->toSql(); // without ->paginate() first, just to see base query
// then compare against what paginate() adds:
App\Models\Task::paginate(15)->toArray(); // includes 'total', 'current_page', 'last_page' keys
```
Manual: with more than 15 tasks across your projects, visit `/tasks` — confirm only 15 rows show per page and the pagination links at the bottom navigate correctly; combine with a filter (`/tasks?status=todo`) and confirm the filter persists across page-2 navigation (proves `withQueryString()` is working).

## 10. Questions for me

1. `TaskController::index()` calls `->withQueryString()` but `ProjectController::index()` does not. Why does that difference make sense given what each index view actually offers the user?
2. If `Auth::user()->projects()` (the query `paginate(10)` is called on) returned zero projects, what would `{{ $projects->links() }}` render — nothing at all, an error, or something else? Why?
3. Cursor pagination avoids the `OFFSET`-scanning cost by querying "rows after the last cursor" instead of "skip N rows." What indexed column does that comparison need to exist for the cursor approach to actually be fast — and does this app's `tasks` table already have it?
4. If you switched `ProjectController::index()` from `paginate()` to `cursorPaginate()`, what specific piece of this app's current UI (in `projects/index.blade.php`) would stop working correctly, and why?
