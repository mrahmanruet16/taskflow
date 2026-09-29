# Logging

## 1. What is it?

Logging is writing a record of what happened, to a destination a developer or ops person can read later — separate from anything shown to the end user. This app uses Laravel's `Log` facade, writing to `storage/logs/laravel.log`.

## 2. What problem does it solve?

Without logging, once a request finishes, all knowledge of what happened during it is gone — you can't investigate "who deleted this project" or "why did this user's login keep failing" after the fact. Logging keeps a record independent of the application's regular database state, specifically for diagnosing and auditing behavior over time.

## 3. How does it work?

Laravel's `Log` facade (`Illuminate\Support\Facades\Log`) writes to whatever channel `config('logging.default')` points at — this app uses the unmodified Laravel default, `stack` → `single`, meaning every log call appends a line to `storage/logs/laravel.log`. Each call specifies a **level** (`Log::info(...)`, `Log::warning(...)`, `Log::error(...)`, etc.) and a **context array** — structured key-value data attached to that line, not just free text:

```php
Log::warning('Project deleted', [
    'user_id' => Auth::id(),
    'project_id' => $project->id,
    'project_name' => $project->name,
]);
```
renders as:
```
[2026-09-28 14:37:00] testing.WARNING: Project deleted {"user_id":57,"project_id":34,"project_name":"Phased content-based complexity"}
```

The level chosen matters — this app uses `info` for routine-but-worth-recording events (task deletion, member removal, login, logout) and `warning` specifically for project deletion, since it's the one operation that cascades destructively through every task/comment/membership row belonging to that project (via the migrations' `cascadeOnDelete()` — see `docs/backend-concepts/database-relationships.md`), and for failed login attempts, since a pattern of these is a security signal worth a human noticing faster than routine `info` noise.

## 4. Where is it used in this project — and what's deliberately NOT logged

Four call sites, chosen because each represents either a destructive action or a security-relevant event — not blanket "log everything":

- `LoginRequest::authenticate()` — `Log::warning('Failed login attempt', ['email' => ..., 'ip' => ...])`. The email (not the password) is logged specifically because a pattern of failed attempts against one email, or many emails from one IP, is exactly what security monitoring looks for — this is the one log line in the app that deliberately includes a value close to PII, and it's justified because detecting that pattern is the log's entire purpose.
- `AuthenticatedSessionController::store()`/`destroy()` — `Log::info('User logged in'/'User logged out', ['user_id' => ...])`. Deliberately does NOT log the email here (unlike the failed-attempt case above) — once a user is successfully identified, their `user_id` is sufficient to look them up; there's no detection-pattern reason to duplicate their email into every successful-login line.
- `ProjectController::destroy()` — `Log::warning('Project deleted', [...])`.
- `TaskController::destroy()` — `Log::info('Task deleted', [...])`.
- `ProjectMemberController::destroy()` — `Log::info('Project member removed', [...])` — an access-revocation event, worth a system-level record distinct from the user-facing activity feed.

**Never logged, anywhere in this app**: passwords (the `LoginRequest` context array explicitly includes only `email`/`ip`, never `password`, even on failure), session tokens, or the CSRF token. This matches the org guideline this session operates under and the project spec's explicit instruction.

## 5. Application Logs vs. Database Logs vs. Error Logs vs. Audit Logs

This app actually demonstrates two of these four concepts concretely, which makes the distinction easy to ground in real code rather than abstract definitions:

- **Application logs** — exactly what section 3/4 above describe: `Log::info()`/`Log::warning()` calls writing to `storage/logs/laravel.log`, for developers/ops, never shown to end users.
- **Audit logs** — this app's `ActivityLog` model (see `docs/backend-concepts/database-relationships.md`) is a genuinely different mechanism serving a genuinely different audience: it's stored in the `activity_logs` **database table** (not a log file), and it's **shown directly to end users** in the UI (a project's Activity feed, a task's Activity feed). Compare the two side by side for the exact same event, `ProjectController::destroy()`:
  ```php
  Log::warning('Project deleted', ['user_id' => Auth::id(), 'project_id' => $project->id, ...]);
  // vs., elsewhere in this app, the pattern used for non-deletion events:
  ActivityLog::create(['user_id' => ..., 'subject_type' => Project::class, 'subject_id' => ..., 'description' => '...']);
  ```
  (Project deletion specifically only gets the system log, not an `ActivityLog` row — logging an activity entry pointing at a subject that's about to be deleted, via `nullableMorphs()`, would leave an orphaned reference with nothing left to view; every other destructive/creative action in this app — task deletion, member removal, task creation — DOES get an `ActivityLog` row, since the parent Project/Task remains viewable afterward.)
- **Error logs** — a subset of application logs, specifically for exceptions. This app doesn't call `Log::error()` explicitly anywhere, because Laravel's own exception handler already logs every unhandled exception automatically (the mechanism `docs/backend-concepts/error-handling.md` covers) — adding a manual `Log::error()` call in a `catch` block would only be needed if this app caught and suppressed an exception instead of letting it propagate, which it doesn't do anywhere.
- **Database logs** — PostgreSQL's own server-side logs (query logs, connection logs, slow-query logs), configured at the database server level (`postgresql.conf`), not from application code at all. This app doesn't configure these (out of scope for a Laravel application's own codebase), but they're the layer you'd check if a query was slow for reasons the application layer can't see (lock contention, missing statistics, etc.) — distinct from anything `Log::` calls can tell you.

## 6. What happens without it?

Without the `Log::warning('Project deleted', ...)` call specifically: if a project unexpectedly disappeared (a bug, or a user claiming they didn't delete it), there would be zero record of who deleted it or when — the `activity_logs` table can't help here either, since (as explained above) that event deliberately isn't recorded there. The application log is the *only* record of that specific event.

## 7. Alternatives

- **Third-party logging services** (e.g. Sentry, Papertrail, a centralized ELK stack) — appropriate at real production scale for searching/alerting across many servers' logs. Not needed for this single-instance learning app; Laravel's local file logging is sufficient and requires no external dependency.
- **Logging every single controller action** — rejected deliberately. The project's own principle of avoiding unnecessary complexity applies here too: only destructive and security-relevant events get a log call, not routine reads/updates, which would create noise without adding investigative value.

## 8. Trade-offs

File-based logging (`storage/logs/laravel.log`) is simple but doesn't scale to multiple server instances without a shared/centralized log destination — acceptable at this app's single-instance scale, a real constraint to revisit if the monolith were ever horizontally scaled (see ADR 001's "when we would reconsider" note).

## 9. How do I verify it?

```bash
php artisan test
tail -20 storage/logs/laravel.log
```
The test suite itself exercises every one of these four log call sites (`AuthenticationTest` for login/logout/failed-login, `ProjectTest`/`ProjectMemberTest`/`TaskTest` for the destructive actions) — running the full suite and then reading the log file is a legitimate way to observe all four in one pass, confirmed during this phase: real entries appeared for all four events with correct structured context and zero leaked credentials.

Manual: log in with a wrong password, then check `storage/logs/laravel.log` for a `WARNING: Failed login attempt` line with your email and IP but no password.

## 10. Questions for me

1. Why does `ProjectController::destroy()` get a `Log::warning()` call but NOT an `ActivityLog::create()` call, while every other destructive action in this app (task deletion, member removal) gets BOTH? What would go wrong if project deletion also tried to write an `ActivityLog` row the normal way?
2. The failed-login log includes the attempted email address, but the successful-login log does not. Why is that asymmetry intentional rather than an oversight?
3. If this app's `Log::error()` were never called anywhere, does that mean errors aren't being logged at all? What's actually happening instead?
4. Where would you look — `activity_logs` table or `storage/logs/laravel.log` — to answer "what did user #4 do across all their projects this month, in a way I could show them in the UI"? Where would you look to answer "which user IDs have had repeated failed login attempts in the last hour"? Why are these different sources for different questions?
