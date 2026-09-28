# Error Handling

## 1. What is it?

Error handling is what happens when a request can't complete normally — a resource doesn't exist (404), the user isn't allowed to see it (403), input fails validation (422), or something genuinely breaks (500). This app uses custom Blade views for the first and last two; validation errors reuse Laravel's built-in session-flash mechanism (see `docs/backend-concepts/validation.md`).

## 2. What problem does it solve?

Without deliberate error handling, a broken request either crashes with a raw PHP error (exposing internals to anyone) or silently does nothing (confusing, with no feedback). Consistent, purpose-built error responses tell the user *what* happened (in general terms) without leaking *how* the failure occurred internally — the second half matters as much as the first, which is why `docs/backend-concepts/error-handling.md` exists as its own concept, not folded into validation.

## 3. How does it work?

Laravel's exception handler auto-discovers `resources/views/errors/{code}.blade.php` by HTTP status code — no registration required. This app has:

- **`errors/404.blade.php`** — rendered automatically when a route doesn't exist, OR when route-model binding fails (e.g. `GET /projects/999999` where no project 999999 exists — Eloquent's `findOrFail()`, used implicitly by route-model binding, throws `ModelNotFoundException`, which Laravel converts to a 404 HTTP response before it ever reaches this view).
- **`errors/403.blade.php`** — rendered when `Gate::authorize()` or a Form Request's `authorize()` returns `false`, throwing `AuthorizationException` (see `docs/backend-concepts/authorization-and-policies.md`).
- **`errors/500.blade.php`** — rendered for any unhandled exception. **Deliberately does NOT use `<x-layout>`** (unlike every other view in this app) — a 500 page's entire purpose is to render correctly even when something else is broken (session, auth, database), and `<x-layout>` depends on `@auth`/`route()` resolution succeeding. The 500 view has zero dependencies on purpose.

The critical behavior is controlled by `APP_DEBUG` (`config('app.debug')`, from `.env`):
- **`APP_DEBUG=true`** (local development): Laravel shows a detailed debug page with the exception message, stack trace, and request context — essential for the developer fixing the bug.
- **`APP_DEBUG=false`** (production): Laravel renders `errors/500.blade.php` instead, with **no exception details at all** — the custom view has no access to (and doesn't attempt to display) the actual error.

This was **verified empirically, not assumed**: the app was run with a route that deliberately throws `RuntimeException('Manual APP_DEBUG verification exception.')`. With `APP_DEBUG=true`, the exception message and class name appeared 6 times in the response body (Laravel's detailed debug page). With `APP_DEBUG=false`, they appeared **zero** times — only the generic "Something went wrong" message rendered. See `tests/Feature/ErrorPagesTest.php::test_debug_mode_off_does_not_expose_stack_traces_on_a_server_error` for the automated version of this same check, which runs on every `php artisan test`.

## 4. Where is it used in this project?

- `resources/views/errors/404.blade.php`, `403.blade.php`, `500.blade.php`
- `tests/Feature/ErrorPagesTest.php` — 4 tests: undefined route → 404, route-model-binding miss → 404, unauthorized access → 403, `APP_DEBUG=false` → no stack trace leak
- `.env` → `APP_DEBUG` (never hardcoded; always read from environment)
- Validation errors (422-equivalent, via redirect + flashed session errors) are documented separately in `docs/backend-concepts/validation.md` — a different mechanism (no dedicated error page; the *same* page re-renders with errors) for a different kind of failure (expected user input mistakes, not exceptional/unexpected states).

## 5. What happens without it?

Without custom error views, Laravel falls back to its own default error pages — functional, but generic and inconsistent with the rest of this app's minimal styling. The more serious risk is the `APP_DEBUG` behavior itself: if a real production deployment of this app ever ran with `APP_DEBUG=true`, every unhandled exception would leak stack traces, file paths, and potentially query details (including table/column names) to any visitor who happened to trigger an error — a real information-disclosure risk, which is exactly why this app's error-handling verification specifically tested the `false` case rather than just confirming the views exist.

## 6. Alternatives

- **JSON error responses** (`{"error": "Not Found"}`) — appropriate for an API; this app is a server-rendered Blade UI, so an HTML error page matching the rest of the app's visual style is the correct fit. `bootstrap/app.php` already has `shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson())` configured by the Laravel skeleton, so a future API surface on this app would automatically get JSON errors without any change here.
- **A single generic error page for all statuses** — simpler, but loses the ability to give the user a specific, actionable message ("you don't have permission" vs. "this doesn't exist" are different problems with different next steps).

## 7. Trade-offs

Custom error views are static content — they can't display context-specific information (e.g. "you don't have permission to view *this specific project*") without either passing that data in (risking the same over-disclosure problem custom error pages exist to prevent) or accepting a generic message. This app accepts the generic message deliberately.

## 8. Production considerations

`APP_DEBUG` must be `false` in any real deployment — this is Laravel's own strong recommendation, and this app's test suite enforces it stays correct going forward (`ErrorPagesTest` would fail if the 500 view somehow started leaking exception details). Beyond the view layer, actual error *logging* (so developers can still diagnose the problem even though users don't see it) is a separate concern — see `docs/backend-concepts/logging.md`.

## 9. How do I verify it?

Automated:
```bash
php artisan test --filter=ErrorPagesTest
```

Manual — reproduces exactly what was done during this phase's verification:
1. Temporarily add a route that throws (e.g. `Route::get('/debug-test', fn () => throw new \RuntimeException('test'));`).
2. Visit it with `APP_DEBUG=true` in `.env` — confirm you see Laravel's detailed debug page with the exception message.
3. Change `.env` to `APP_DEBUG=false`, visit again (no server restart needed — `php artisan serve` re-reads `.env` per request) — confirm you now see the generic custom 500 page with zero mention of the actual error.
4. Revert both the temporary route and `.env` change.

## 10. Questions for me

1. Why does `errors/500.blade.php` specifically avoid `<x-layout>` while every other view in this app uses it? What's the actual failure scenario this protects against?
2. `ErrorPagesTest`'s 404 test for route-model binding hits `/projects/999999` — walk through exactly what happens between that HTTP request and the 404 response: which class throws first, and where does Laravel intercept it to render the custom view instead of a raw PHP fatal error?
3. If you removed `resources/views/errors/404.blade.php` entirely, what would a visitor see when hitting a nonexistent route — would the app crash, or would something else render? Why?
4. `APP_DEBUG=false` was tested by throwing a real exception through the actual HTTP pipeline (both in the automated test and manually), rather than just checking that the `.blade.php` file's content doesn't contain "stack trace" as a string. Why does that distinction matter — what could a weaker test miss?
