# Routing

## 1. What is it?

Routing is how Laravel decides which piece of code should handle an incoming request, based on its URL and HTTP method. All of this app's routes live in one file, `routes/web.php`.

## 2. What problem does it solve?

An HTTP request only carries a method and a URL — Laravel needs a lookup mechanism to turn `GET /tasks/7` into "call `TaskController::show()` with `$task` bound to the row with ID 7." Routing is that lookup table, checked in registration order until a match is found.

## 3. How does it work — every routing pattern actually used in this app

**Closures**, for the two routes with no real logic:
```php
Route::get('/', function () {
    return view('welcome');
});
```

**Controller method references**, the pattern used for every real feature:
```php
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
```
`{task}` is a **route parameter** — Laravel extracts the URL segment and passes it to the matching method parameter. Because `TaskController::show(Task $task)` type-hints `Task`, Laravel performs **route-model binding** automatically: it runs `Task::findOrFail($id)` for you before your method body even executes, throwing `ModelNotFoundException` (→ 404) if no matching row exists. No manual `Task::find($request->task)` lookup is ever written in this app's controllers — route-model binding does it implicitly everywhere a model is type-hinted in a route.

**`Route::resource()`**, generating 7 conventional routes in one line:
```php
Route::resource('projects', ProjectController::class);
```
generates exactly:
```
GET    /projects              → index
GET    /projects/create       → create
POST   /projects              → store
GET    /projects/{project}    → show
GET    /projects/{project}/edit → edit
PUT    /projects/{project}    → update
DELETE /projects/{project}    → destroy
```
confirmed via `php artisan route:list --name=projects` throughout this project's development — every phase's checkpoint verification ran this command to confirm routes registered as expected before writing views/tests against them.

**Named routes**, on every single route in this app (`->name('tasks.show')`), used throughout Blade views (`route('tasks.show', $task)`) and controller redirects (`redirect()->route('projects.index')`) — this decouples the *code* referencing a route from that route's actual URL string; the URL could change without touching any Blade file or controller, only the `routes/web.php` definition.

**Middleware groups**, wrapping related routes:
```php
Route::middleware('auth')->group(function () {
    // every protected route in this app
});
```
(covered fully in `docs/backend-concepts/middleware.md`).

**Explicitly named routes instead of `Route::resource()`**, for resources that don't follow the full 7-action convention — `ProjectMemberController` (only index/store/destroy — no "edit a membership" screen) and `CommentController` (only store/edit/update/destroy — no standalone comment page). A deliberate choice: forcing these onto `Route::resource()` would generate unused route definitions for actions that don't exist (`create`, `show` for comments) — see `docs/backend-concepts/http-and-rest.md` for the REST-convention reasoning behind this.

## 4. Where is it used in this project

Every single route this app has lives in `routes/web.php` — no `routes/api.php` routes exist (this is a Blade-only app, ADR 003). Full current route list confirmable with `php artisan route:list`.

## 5. What happens without it?

If a route weren't registered for a given URL/method combination, Laravel would return a 404 automatically (no matching route → `NotFoundHttpException`) — the same custom 404 page covers this case identically to a `ModelNotFoundException`, from the user's perspective (see `docs/backend-concepts/error-handling.md`).

## 6. Alternatives

- **Convention-based routing** (URL directly maps to a controller/method name by naming pattern, e.g. some frameworks' "file-based routing") — Laravel deliberately uses explicit route registration instead, trading a small amount of upfront declaration for total clarity about exactly what URLs exist (`routes/web.php` IS the complete list, nothing implicit).

## 7. Trade-offs

Explicit route registration means every new endpoint requires a `routes/web.php` entry — slightly more typing than convention-based routing, in exchange for `routes/web.php` being a single, greppable source of truth for every URL this app responds to (confirmed useful throughout this project: every phase's route additions were verified via `route:list` before building the corresponding controller/views).

## 8. Production considerations

Laravel can cache the compiled route table (`php artisan route:cache`) for a small performance win in production — skips re-parsing `routes/web.php` on every request. Not used in this app's local-development setup (the cache would need clearing on every route change, adding friction during active development); worth enabling for an actual production deployment.

## 9. How do I verify it?

```bash
php artisan route:list
php artisan route:list --name=tasks
```
Shows every registered route, its HTTP method(s), URI, name, and controller — the exact command used throughout this project's development to confirm routes after every phase's changes.

## 10. Questions for me

1. `Route::resource('projects', ProjectController::class)` generates 7 routes. `ProjectMemberController`'s routes are registered individually instead. What would have gone wrong (or just been wasted) if `Route::resource('projects.members', ProjectMemberController::class)` had been used instead?
2. Route-model binding means `TaskController::show(Task $task)` never manually calls `Task::find()`. What HTTP status code does a visitor get if they request `/tasks/999999` (no such row), and which specific exception is responsible?
3. Every route in this app has a `->name(...)`. What would break, concretely, if `route('tasks.show', $task)` were replaced everywhere with a hardcoded `/tasks/'.$task->id` string, and the URL prefix later changed from `/tasks` to `/work-items`?
4. `routes/web.php` registers `/projects/{project}/tasks/create` (task creation, nested under its project) but `/tasks/{task}` (task viewing, NOT nested). Why does that asymmetry make sense given what each URL actually needs to express?
