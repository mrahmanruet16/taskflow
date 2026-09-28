# Authentication and Sessions

## 1. What is it?

Authentication is the process of confirming *who* is making a request. In this app, once a user registers or logs in, the server remembers them across subsequent requests using a **session** — a piece of server-side state tied to the browser via a cookie.

## 2. What problem does it solve?

HTTP is stateless: every request is independent, with no memory of previous ones. Without sessions, a user would have to re-send their username and password on every single request (every click, every page load). Sessions let the server say "this cookie means the same person who logged in five minutes ago" without re-checking a password every time.

## 3. How does it work?

Step by step, for `POST /login` in this app (`app/Http/Controllers/Auth/AuthenticatedSessionController.php`):

1. The browser submits `email` + `password` in a form.
2. `LoginRequest::authenticate()` (`app/Http/Requests/Auth/LoginRequest.php`) calls `Auth::attempt(['email' => ..., 'password' => ...])`.
3. Internally, Laravel's `EloquentUserProvider` looks up a `User` by email, then calls `Hash::check($plaintextPassword, $user->password)` — it compares a *hash* of the submitted password against the stored hash, never the raw password itself (see `docs/backend-concepts/` password hashing notes below).
4. If it matches, Laravel writes the user's ID into the session data and marks the request as authenticated for the current guard (`web`).
5. The controller calls `$request->session()->regenerate()` — this issues a **new** session ID while keeping the session's data. This defends against *session fixation*: without regenerating, an attacker who tricked a victim into using a known session ID before login could hijack the now-authenticated session.
6. Laravel's session middleware (part of the default `web` middleware group) writes the session ID into a cookie (`laravel_session`, in this app's config `SESSION_DRIVER=database` → session data is stored in the `sessions` table, keyed by that ID) and sends it back in the `Set-Cookie` response header.
7. On every subsequent request, the browser automatically sends that cookie back. Laravel's session middleware reads the session ID, loads the matching row from the `sessions` table, and `Auth::user()` becomes available — this is how `Auth::user()` "knows" who's logged in without a password being sent again.

Logout (`AuthenticatedSessionController::destroy`) does the reverse: `Auth::guard('web')->logout()` clears the authenticated user from the guard, `$request->session()->invalidate()` deletes the session's row from the `sessions` table entirely (not just clearing the "logged in" flag — the whole session record is gone), and `regenerateToken()` issues a fresh CSRF token so a stale token can't be replayed.

## 4. Where is it used in this project?

- `app/Http/Controllers/Auth/RegisteredUserController.php` — registration, logs the user in immediately after creating the account
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — login (`store`) and logout (`destroy`)
- `app/Http/Requests/Auth/LoginRequest.php` — where `Auth::attempt()` actually happens, plus rate limiting
- `routes/web.php` — `guest` middleware on `/login`/`/register` (blocks already-logged-in users from re-registering/re-logging-in), `auth` middleware on `/dashboard` and `/logout`
- `config/session.php` — `SESSION_DRIVER=database` (from `.env`), 120-minute lifetime
- `database/migrations/..._create_users_table.php` — creates the `sessions` table (id, user_id, ip_address, user_agent, payload, last_activity)
- `app/Models/User.php` — `casts()` returns `'password' => 'hashed'`, which makes `User::create(['password' => $plaintext])` automatically run it through `bcrypt()` before it ever reaches the database

## 5. What happens without it?

If `$request->session()->regenerate()` were removed from the login flow: the session ID stays the same before and after login. An attacker who can set a victim's session cookie before they log in (e.g. via a subdomain cookie or a captured pre-login session ID) would have their known session ID become "logged in" the moment the victim authenticates — classic **session fixation**.

If the `'password' => 'hashed'` cast were removed from `User.php` and passwords were stored as-is: anyone with read access to the `users` table (a DBA, a backup file, a SQL injection elsewhere in the app) would see every user's actual password in plaintext. Since people reuse passwords across sites, this compromises far more than just this app.

If the `auth` middleware were removed from `/dashboard`: any visitor, logged in or not, could load the dashboard directly by URL. This is exactly what Failure Experiment 1 (authorization) demonstrates later in this project.

## 6. Alternatives

- **Token-based auth (API tokens, JWT)** — stateless, the server holds no session; instead every request carries a signed token proving identity. Common for SPAs/mobile apps talking to an API. Not chosen here because this is a server-rendered Blade app where the browser and server are tightly coupled — cookies+sessions are the conventional, simpler fit (see ADR-005).
- **Laravel Sanctum** — token auth for SPAs/mobile, built by the Laravel team. Overkill for a pure Blade app with no separate frontend.
- **OAuth / social login** — delegates authentication to a third party (Google, GitHub). Not part of this project's scope.

## 7. Trade-offs

Session-based auth requires server-side storage (here, a `sessions` table row per active session) and doesn't scale to being *fully* stateless — if you horizontally scale the app across multiple servers, all servers need access to the same session store (a shared database, as configured here, or Redis). Token-based auth avoids that shared-state requirement but pushes complexity (token expiry, refresh, revocation) onto the client and server both.

## 8. Production considerations

- `SESSION_DRIVER=database` (current config) means every authenticated request does a session table lookup. At high scale, `SESSION_DRIVER=redis` is faster (in-memory) — a one-line config change if it ever becomes a bottleneck, not an architecture rewrite.
- `SESSION_ENCRYPT` (currently `false`, from `.env`) — when `true`, Laravel encrypts the session cookie payload. Consider enabling in a real production deployment.
- Rate limiting login attempts (already implemented in `LoginRequest::ensureIsNotRateLimited`) is a production-necessary defense against brute-force credential guessing — without it, an attacker can submit thousands of password guesses per second, since bcrypt only slows down each *individual* check, it doesn't limit the *count* of attempts over HTTP.

## 9. How do I verify it?

Automated:
```bash
php artisan test --filter=Auth
```
(10 tests: registration screen renders, registration succeeds + hashes password, registration validation failures, login screen renders, login succeeds/fails, dashboard is guest-blocked, dashboard is visible when authenticated, logout works.)

Manual (matches this session's actual verification):
```bash
php artisan serve
```
1. Visit `/register`, create an account.
2. You should land on `/dashboard` showing your name/email — confirms the session was established.
3. Open the same URL in an incognito window (no cookie) — you should be redirected to `/login`, confirming the boundary is enforced server-side, not just hidden in the UI.
4. Click Logout — you should return to `/`, and `/dashboard` should redirect to `/login` again.

Database-level verification:
```bash
psql -h 127.0.0.1 -U laravel_learning -d laravel_learning_dev -c "SELECT id, payload IS NOT NULL as has_payload, last_activity FROM sessions;"
```
You should see a row appear after login and disappear after logout (because `invalidate()` deletes it).

## 10. Questions for me

1. Why does `AuthenticatedSessionController::store()` call `$request->session()->regenerate()` but `RegisteredUserController::store()` also calls it? What attack does this specifically prevent, and at what point in the request would it matter if it were missing?
2. `LoginRequest::throttleKey()` combines email *and* IP address. Why not just IP? Why not just email?
3. If you inspected the `sessions` table row for a logged-in user, what columns would you expect, and which one changes on every single request (even ones that don't touch auth)?
4. What's the actual difference between `Auth::guard('web')->logout()` and `$request->session()->invalidate()` — why does the logout method call both instead of just one?
5. Suppose someone removed the `'password' => 'hashed'` cast from `User.php` but the rest of the auth code stayed identical. Would `Auth::attempt()` still work for *existing* correctly-hashed users? Would it work for *newly registered* users? Why might those two answers differ?
