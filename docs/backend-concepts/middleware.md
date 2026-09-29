# Middleware

## 1. What is it?

Middleware is code that runs BEFORE (and sometimes after) a request reaches its controller — a pipeline every request passes through, each stage able to inspect, modify, or reject the request before it continues.

## 2. What problem does it solve?

Some checks need to happen identically for many different routes ("is this user logged in," "does this request have a valid CSRF token," "is the session started") — without middleware, every controller method would need to repeat those checks manually, with every route at risk of a developer forgetting one. Middleware lets those checks be declared once, applied to a whole group of routes, and run automatically and consistently.

## 3. How does it work — the middleware this app actually uses, in the order they run

Every route in this app runs through Laravel's default `web` middleware group (applied automatically by `bootstrap/app.php`'s `->withRouting(web: ...)` — no explicit `Route::middleware('web')` needed anywhere in `routes/web.php`), which includes (among Laravel's defaults): session handling (`StartSession` — makes `$request->session()` and `Auth::user()` work at all), CSRF verification (`VerifyCsrfToken` — rejects `POST`/`PUT`/`DELETE` requests without a valid `_token`, the actual mechanism behind every `@csrf` directive in this app's forms), and cookie encryption/decryption.

On top of that, this app adds two of its own middleware groups in `routes/web.php`:

```php
Route::middleware('guest')->group(function () {
    // /register, /login
});

Route::middleware('auth')->group(function () {
    // everything else that requires being logged in
});
```

**`guest`**: rejects (redirects away) an already-authenticated user trying to reach `/login` or `/register` — there's no reason for a logged-in user to see a login form, and `Auth::attempt()` behaves oddly if called on top of an existing session.

**`auth`**: rejects any request without a valid session, redirecting to the named `login` route. This is the actual server-side enforcement boundary that makes "you must be logged in to do X" real, not just a UI convention — every protected route in this app (`/dashboard`, `/projects/*`, `/tasks/*`, `/comments/*`) sits inside this group.

## 4. Where is it used in this project

- `routes/web.php` — the `guest`/`auth` groups described above, wrapping every route that needs them.
- Individual `Gate::authorize()`/Form-Request `authorize()` calls are explicitly NOT middleware in this app — they're a separate, finer-grained check (see `docs/backend-concepts/authorization-and-policies.md`) that depends on which SPECIFIC resource is being touched, something route-level middleware can't express (middleware runs before route-model binding resolves which project/task is even involved).

## 5. What happens without it? (Failure Experiment 1, planned)

The spec's Failure Experiment 1 is exactly this: temporarily remove the `auth` middleware from the route group wrapping `/projects`, `/tasks`, etc. Without it, any visitor — logged in or not — could load `/dashboard`, `/projects`, or any other currently-protected page directly by URL, with no authentication check at all. This is already flagged directly in `routes/web.php`'s own code comment on the `auth` group ("removing this middleware is Failure Experiment 1"), written back in Phase 2.1 specifically to make this connection obvious to whoever runs that experiment later.

Critically, removing `auth` middleware would NOT be caught by this app's Policy checks — `ProjectPolicy`/`TaskPolicy`/`CommentPolicy` all assume `$user` is already a real, authenticated `User` instance; they answer "is this specific logged-in user allowed to do X," not "is anyone logged in at all." The `auth` middleware is a genuinely separate layer, checked first, that the Policy layer depends on already having happened.

## 6. Alternatives

- **Per-controller-method manual checks** (`if (! Auth::check()) { return redirect('/login'); }` at the top of every protected controller method) — works, but must be remembered and repeated in every single protected method; one omission silently reopens a route. Middleware applied to a route group makes the omission structurally impossible for any route inside that group.
- **Policy-only enforcement, no `auth` middleware** — insufficient on its own; a Policy check like `ProjectPolicy::view($user, $project)` requires an actual `$user` object to check against. Without `auth` middleware guaranteeing one exists first, an unauthenticated request would either crash (calling a method on `null`) or require every single Policy method to separately re-implement "and also, is anyone logged in" — exactly the kind of repeated-check problem middleware exists to avoid.

## 7. Trade-offs

Middleware applied to a whole route group is all-or-nothing for that group — if one route inside `Route::middleware('auth')->group(...)` genuinely needed to be public, it would need to be pulled out of the group (or have a per-route middleware exception), not silently left inside and unprotected. This app's grouping is deliberately simple (exactly two groups, `guest` and `auth`, plus the ungrouped fully-public routes like `/` and `/register`/`/login`'s GET forms) — no route currently needs a more granular exception.

## 8. Production considerations

Laravel's built-in rate-limiting middleware (`throttle:`) isn't applied at the route level in this app — login-specific rate limiting is instead handled inside `LoginRequest::ensureIsNotRateLimited()` (see `docs/backend-concepts/validation.md`), a deliberate choice to scope the limit per-email+IP rather than a blanket per-route limit. A production deployment might additionally want route-level throttling on top of this as a broader defense.

## 9. How do I verify it?

```bash
php artisan route:list --path=dashboard -v
```
The `⇂ web` / `⇂ auth` lines shown under each route confirm exactly which middleware apply, in order — used throughout this project's phases to confirm new routes landed in the correct group before testing them.

Manual: while logged out, visit `/dashboard` directly — confirm the `auth` middleware redirects to `/login` rather than showing the dashboard.

## 10. Questions for me

1. `VerifyCsrfToken` (part of the `web` group) runs on every `POST`/`PUT`/`DELETE` in this app automatically — no explicit code anywhere in `routes/web.php` enables it. Where does the `@csrf` directive in a Blade form actually connect to this middleware checking it?
2. If `auth` middleware were removed from the `/projects` route group but `ProjectPolicy::viewAny()` still existed and still returned `true` for any authenticated user, what would an actual UNauthenticated visitor see when hitting `GET /projects`? Would the Policy save the day?
3. Why does the `guest` middleware exist at all — what's the actual harm (not just "it's weird") in letting an already-logged-in user view the `/login` page?
4. Middleware runs BEFORE route-model binding resolves `{project}` into an actual `Project` instance. What does this ordering fact tell you about why `auth` middleware can't be the thing that decides "is this user allowed to see THIS SPECIFIC project" — what would it be missing at the point it runs?
