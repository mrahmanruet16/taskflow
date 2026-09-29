# Controllers

## 1. What is it?

A controller is a class whose methods handle incoming requests once routing has matched them — the orchestration point between "a request arrived" and "here's the response." Every controller in this app lives in `app/Http/Controllers/`.

## 2. What problem does it solve?

Without controllers, route definitions would need to contain all their logic inline as closures — workable for the two trivial routes in this app (`/` and `/system-check`) but unmanageable for anything with real logic, hard to test in isolation, and impossible to reuse across routes. Controllers give each meaningful action a named, testable, reusable home.

## 3. How does it work — the shape every controller in this app follows

Every non-trivial controller method in TaskFlow follows the same shape, directly matching `docs/architecture/request-lifecycle.md`'s traced flow:

```php
public function store(StoreProjectRequest $request): RedirectResponse
{
    // 1. Validation + authorization already happened — see below
    $project = DB::transaction(function () use ($request) {
        // 2. Business logic
        $project = Project::create([...]);
        // ...
        return $project;
    });

    // 3. Redirect (Post/Redirect/Get — see docs/backend-concepts/http-and-rest.md)
    return redirect()->route('projects.show', $project)->with('status', 'Project created.');
}
```

**Dependency injection** is how `StoreProjectRequest $request` gets there: Laravel's service container resolves the type-hinted class automatically before calling the method — the controller never manually instantiates a Form Request, session, or any other framework object it needs; it just type-hints the parameter and Laravel supplies it. This same mechanism is how `Task $task` in `TaskController::show(Task $task)` receives an already-resolved Eloquent model instance (route-model binding — see `docs/backend-concepts/routing.md`) rather than a raw string ID.

**Authorization happens two ways**, both BEFORE the controller's real logic runs: either automatically (a type-hinted Form Request's own `authorize()` method runs before `rules()` are even checked, so an unauthorized `POST /projects` never reaches `ProjectController::store()`'s body at all), or explicitly via `Gate::authorize(...)` as the first line of a controller method that has no corresponding Form Request (e.g. `ProjectController::destroy()`, which has no request body to validate but still needs a Policy check).

## 4. Where is it used in this project

Nine controllers, each scoped to one resource: `RegisteredUserController`, `AuthenticatedSessionController` (auth — Phase 2.1), `DashboardController` (Phase 2.1 stub → Phase 2.6 real aggregation), `ProjectController`, `ProjectMemberController` (Phase 2.2/2.3), `TaskController` (Phase 2.4), `CommentController` (Phase 2.5). No controller in this app handles more than one resource's actions — `ProjectMemberController` is separate from `ProjectController` even though membership is "part of" a project, because membership has its own distinct authorization rules (`manageMembers`, not `update`) and its own URL namespace (`/projects/{project}/members`).

## 5. What happens without it?

If controller logic were duplicated inline across multiple route closures instead of centralized in one controller method, a bug fix or behavior change would need to be applied in every duplicate location — and Laravel's own testing helpers (`$this->get(route('tasks.show', $task))` throughout this app's feature tests) work the same either way, but a controller method is what actually gets covered by name in stack traces and test failure output, making debugging noticeably easier than an anonymous closure buried in `routes/web.php`.

## 6. Alternatives

- **Single-action invokable controllers** (`__invoke()`, one controller class per action) — Laravel supports this (`Route::get(..., SomeSingleAction::class)`), useful when an action doesn't naturally group with others. Not used in this app because every action here DOES naturally group by resource (all of a project's CRUD actions belong together in `ProjectController`) — introducing single-action classes would fragment related logic across many tiny files for no benefit here.
- **API Resource controllers returning JSON** — irrelevant to this app (ADR 003, no API layer exists); every controller method here returns either a Blade `View` or a `RedirectResponse`.

## 7. Trade-offs

Grouping every action for a resource into one controller class means that class grows as the resource's feature set grows — `ProjectController` has 7 methods, `TaskController` has 7. Acceptable at this app's scale; a resource with dramatically more actions might eventually warrant splitting (e.g. a hypothetical `ProjectExportController` for export-specific actions) — not needed here.

## 8. Production considerations

Nothing this app does differs from local development at the controller layer specifically — controllers don't have their own separate production-vs-development behavior (that distinction lives at the `APP_DEBUG`/error-handling layer, and at the `.env`-driven config layer, both already covered in their own docs).

## 9. How do I verify it?

```bash
php artisan route:list --name=projects
```
The `Controller` column of the output confirms exactly which class/method handles each route.

Automated: every feature test in `tests/Feature/` exercises real controller methods through the full HTTP stack (`$this->actingAs($user)->post(route('projects.store'), [...])`) — the controllers aren't tested by calling their methods directly in isolation, but through the same routing/middleware/validation pipeline a real request goes through, deliberately (see `docs/testing/manual-verification.md`, planned, for the reasoning on why feature tests are favored over unit-testing controllers in isolation).

## 10. Questions for me

1. `ProjectController::store()` type-hints `StoreProjectRequest $request` instead of the generic `Illuminate\Http\Request`. What specifically would you lose if it type-hinted the generic `Request` class instead — what currently happens automatically that you'd have to do manually?
2. Why does `ProjectMemberController` exist as a separate class from `ProjectController`, rather than adding `addMember()`/`removeMember()` methods directly onto `ProjectController`?
3. `TaskController::destroy()` calls `Gate::authorize('delete', $task)` as its first line, with no corresponding Form Request. Why doesn't THIS particular action have a Form Request the way `store()`/`update()` do?
4. If a bug caused `ProjectController::show()` to sometimes return the wrong project's data, would you expect that bug to live in the controller, the route definition, or route-model binding? What would you check first, and why?
