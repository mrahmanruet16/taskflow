# Request Lifecycle

This document traces one concrete request — **"User submits Create Task"** — through every layer of this application, from the browser to the database and back. It uses the actual code paths in this repository, not a generic/hypothetical example.

## The Flow

```
Browser
 ↓
HTTP Request
 ↓
Web Server
 ↓
Laravel Entry Point
 ↓
HTTP Kernel / Middleware Pipeline
 ↓
Route
 ↓
Authentication Middleware
 ↓
Authorization
 ↓
Controller
 ↓
Form Request Validation
 ↓
Service / Business Logic
 ↓
Eloquent
 ↓
PostgreSQL
 ↓
Controller
 ↓
Blade
 ↓
HTML Response
 ↓
Browser
```

## Traced Step by Step: "User submits Create Task"

**Setup**: a logged-in user, already viewing `GET /projects/7/tasks/create` (the task creation form, `resources/views/tasks/create.blade.php`), fills in a title/status/priority and clicks "Create Task."

### 1. Browser → HTTP Request

The browser sends:
```
POST /projects/7/tasks HTTP/1.1
Host: 127.0.0.1:8000
Cookie: laravel-session=eyJpdiI6...
Content-Type: application/x-www-form-urlencoded

_token=Abc123...&title=Write+ADR&status=todo&priority=high&due_date=2026-12-01
```
The `_token` field is the CSRF token from `@csrf` in the form — checked in step 4.

### 2. Web Server

`php artisan serve` (this project's local dev server — see `docs/SETUP.md`) receives the raw HTTP request and routes it to `public/index.php`, Laravel's single entry point for every request (in a real production deployment, this would be Nginx/Apache passing the request to PHP-FPM, which still ultimately runs `public/index.php` — the entry point itself doesn't change between environments).

### 3. Laravel Entry Point

`public/index.php` bootstraps the framework: loads Composer's autoloader, builds the Application instance (`bootstrap/app.php` — where this app's route files, middleware groups, and exception handling are registered), and hands the request to the HTTP kernel.

### 4. HTTP Kernel / Middleware Pipeline

The request passes through Laravel's default `web` middleware group (session start, cookie encryption, CSRF verification) — **this is where the `_token` from step 1 is actually checked**; a missing or mismatched token here produces a 419 response before the request ever reaches routing (see `docs/backend-concepts/middleware.md`).

### 5. Route

`routes/web.php` matches `POST /projects/{project}/tasks` to `TaskController::store`, with `{project}` (`7`) queued for route-model binding.

### 6. Authentication Middleware

This route sits inside `Route::middleware('auth')->group(...)`. Since a session cookie was present and valid (checked in step 4's `StartSession`), `Auth::check()` returns `true` and the request proceeds. If it had returned `false`, the request would have redirected to `/login` here — never reaching the controller at all (see `docs/backend-concepts/middleware.md` and the planned Failure Experiment 1).

### 7. Authorization

Route-model binding resolves `{project}` into a real `Project` instance (running `Project::findOrFail(7)`) before `TaskController::store()`'s parameters are populated. Then `StoreTaskRequest`'s `authorize()` method runs — **before** its `rules()` are checked — calling `$this->user()->can('create', [Task::class, $this->route('project')])`, which invokes `TaskPolicy::create()`. If the user isn't at least a Member/Manager/Owner of project 7, this returns `false` and Laravel throws `AuthorizationException` (→ 403, the custom `errors/403.blade.php` page) — the request stops here, never reaching validation or the controller body.

### 8. Controller (entry) → Form Request Validation

`TaskController::store(StoreTaskRequest $request, Project $project)` is called. Because `StoreTaskRequest` is type-hinted, Laravel validates the request body against its `rules()` BEFORE the method body executes — `title` required, `status`/`priority` must be valid enum values, `assigned_to` (if present) must be a project member (see `docs/backend-concepts/validation.md`). If validation fails, Laravel redirects back to the create-task form with flashed errors — the controller body still never runs.

### 9. Service / Business Logic

Validation passed. `TaskController::store()`'s body now runs:
```php
$task = Task::create([...$request->validated(), 'project_id' => $project->id, 'created_by' => Auth::id()]);
ActivityLog::create([...]);
```
This app has no separate service-layer class (ADR 007) — the business logic (create the task, log the activity) is short enough to live directly in the controller method.

### 10. Eloquent

`Task::create([...])` builds an `INSERT INTO tasks (...) VALUES (...)` statement with bound parameters (never raw string interpolation — see `docs/backend-concepts/eloquent.md`), using the `Task` model's `#[Fillable(...)]` attribute to determine which submitted fields are actually allowed to be mass-assigned.

### 11. PostgreSQL

The bound `INSERT` statement executes against `laravel_learning_dev` (or `laravel_learning_test` under the test suite — see ADR 010), returns the new row's generated ID, and PostgreSQL's `tasks_project_id_foreign`/`tasks_created_by_foreign` constraints are checked automatically at this point too — a request that somehow got this far with an invalid `project_id` would fail here at the database level, not just the application level (see the planned Failure Experiment 5).

### 12. Controller (exit)

Back in `TaskController::store()`, with `$task` now a real, saved model (its `id` populated from PostgreSQL's response):
```php
return redirect()->route('tasks.show', $task)->with('status', 'Task created.');
```
No Blade view is rendered directly by THIS request — a redirect response is returned instead (the Post/Redirect/Get pattern — see `docs/backend-concepts/http-and-rest.md`), status code 302, `Location: /tasks/{new id}`.

### 13. Browser → follows the redirect → a SECOND, separate request

The browser automatically issues `GET /tasks/{id}` in response to the 302. This is a distinct request that repeats steps 2–8 (server → kernel → route → auth middleware → authorization, this time `TaskPolicy::view()`) before reaching `TaskController::show()`.

### 14. Blade

`TaskController::show()` calls `return view('tasks.show', ['task' => $task])`. Blade compiles `resources/views/tasks/show.blade.php` (and its `<x-layout>` component) into plain PHP, executes it with `$task` in scope, and produces a complete HTML string.

### 15. HTML Response → Browser

Laravel wraps the rendered HTML in an HTTP response (`200 OK`, `Content-Type: text/html`) and sends it back. The browser parses and renders it — the user sees the new task's detail page, including the "Task Tester created task ..." activity log entry written in step 9, now visible because step 13's fresh `GET` re-queried the database and found it.

## Why Trace It This Way

Every phase of this project's development verified pieces of this exact flow independently (route registration via `route:list`, middleware groups, Policy checks via feature tests, Eloquent's generated SQL via query logging) — this document exists to show how those independently-verified pieces compose into one coherent request, end to end, using a real feature (task creation) rather than an abstract example.

## How to Verify This Yourself

```bash
php artisan serve
```
Open browser dev tools' Network tab, log in, create a task, and observe: the `POST /projects/{id}/tasks` request (302 response, `Location` header pointing at the new task), followed automatically by a `GET /tasks/{id}` request (200, the actual HTML). This is steps 1–15 above, directly observable.
