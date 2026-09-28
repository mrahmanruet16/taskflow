# Authorization and Policies

## 1. What is it?

Authorization is the check that runs *after* authentication succeeds: "OK, I know you're user #4 — but are you allowed to edit *this specific* project?" In this app, authorization decisions live in Policy classes (`app/Policies/ProjectPolicy.php`).

## 2. What problem does it solve?

Authentication alone only proves identity. Without a separate authorization step, any logged-in user could edit or delete *any* project, not just their own — logging in would be the only barrier, and once past it, everything would be fair game.

## 3. How does it work?

1. A Policy class has one method per action: `viewAny`, `view`, `create`, `update`, `delete` (Laravel's conventional names, matching REST-ish CRUD operations).
2. Laravel auto-discovers `App\Policies\ProjectPolicy` for `App\Models\Project` by naming convention — no manual registration needed (this changed from older Laravel versions, which required registering policies in `AuthServiceProvider`).
3. `Gate::authorize('view', $project)` (used in `ProjectController::show`) calls `ProjectPolicy::view($currentUser, $project)`. If it returns `false`, Laravel throws `Illuminate\Auth\Access\AuthorizationException`, which the framework's exception handler automatically converts into an HTTP 403 response.
4. Inside Form Requests (`StoreProjectRequest`, `UpdateProjectRequest`), `authorize()` is called automatically by Laravel *before* the `rules()` are even evaluated — an unauthorized request is rejected before validation logic runs, let alone before it reaches the controller body.
5. In Blade, `@can('update', $project)` re-runs the *exact same* policy method to decide whether to show an "Edit" link. This reuses the same source of truth as the backend check — see section 5 for why that matters.

## 4. Where is it used in this project?

- `app/Policies/ProjectPolicy.php` — the actual rules
- `app/Http/Controllers/ProjectController.php` — `Gate::authorize(...)` calls in `index`, `create`, `show`, `edit`, `destroy`
- `app/Http/Requests/StoreProjectRequest.php`, `UpdateProjectRequest.php` — `authorize()` methods delegating to `$this->user()->can(...)`
- `docs/architecture/adr/006-policy-based-authorization.md` — the decision record

## 5. What happens without it?

If `Gate::authorize('view', $project)` were removed from `ProjectController::show` (while leaving everything else intact — including the Blade template's `@can` checks that hide the Edit button from non-owners): a malicious or simply curious user could type `/projects/2`, `/projects/3`, etc. directly into their browser's address bar and view every project in the system, regardless of who created it. The UI would still *look* correctly restricted to its own user (no Edit button appears for a project they don't own) — but the actual data would be fully exposed to direct URL access. This is precisely why the spec insists: "Never rely only on hiding buttons in Blade... the backend must enforce authorization." A hidden button is a UX nicety; the `Gate::authorize()` call is the actual lock.

This exact scenario is Failure Experiment 1 (see `docs/testing/manual-verification.md`, added in a later phase) — remove the policy check, observe what becomes accessible.

## 6. Alternatives

- **Gates only (no Policy classes)** — `Gate::define('view-project', fn ($user, $project) => ...)` registered globally. Works for simple apps with few models, but doesn't scale cleanly — one giant list of closures instead of one focused class per model.
- **Middleware-based authorization** (e.g. a custom `CheckProjectOwnership` middleware) — works for simple "does this route require role X" checks, but is awkward for "does this user own *this specific* route-model-bound instance" without essentially re-implementing what Policies already do.
- **Role/permission packages** (e.g. spatie/laravel-permission) — powerful for complex many-role, many-permission systems. Not introduced here: the spec's own roles (owner/manager/member/viewer, coming in the Project Membership phase) are small and specific enough that Laravel's built-in Policy mechanism is sufficient without an extra dependency.

## 7. Trade-offs

Policies add one file per model that needs authorization — for models with genuinely no restrictions (anyone can do anything), this is unnecessary ceremony. For this app, every model that matters (Project, and later Task/Comment) does have real ownership/membership restrictions, so the ceremony pays for itself.

## 8. Production considerations

At scale, repeatedly calling `Gate::authorize()` per request is cheap (it's just PHP method calls against already-loaded data, not extra database queries — assuming the resource, e.g. `$project`, was already fetched via route-model binding). The thing to watch for at scale is Policies that *themselves* trigger extra queries (e.g. checking `$project->members()->where('user_id', $user->id)->exists()` on every single authorization check) — that's addressed properly once the Project Membership phase introduces real membership checks; a well-designed Policy method should be a single indexed query, not something looped.

## 9. How do I verify it?

Automated:
```bash
php artisan test --filter=ProjectTest
```
(includes `test_non_owner_cannot_view_the_project`, `test_non_owner_cannot_update_the_project`, `test_non_owner_cannot_delete_the_project` — each asserts a 403.)

Manual:
1. Register as User A, create a project, note its ID from the URL (e.g. `/projects/1`).
2. Log out, register as User B.
3. Manually navigate to `/projects/1` (the URL User A's project used).
4. You should see a 403 Forbidden response — not the project, and not a redirect that quietly hides the restriction.

## 10. Questions for me

1. `ProjectController::index()` doesn't call `Gate::authorize('view', $project)` on every row — why not, and what does it do instead to make sure a user only sees their own projects in the list?
2. Why does `StoreProjectRequest::authorize()` check `$this->user()->can('create', Project::class)` — a *class*, not an instance — while `UpdateProjectRequest::authorize()` checks `$this->route('project')` — an *instance*? What's the actual difference `create` vs `update` reflects here?
3. If you deleted `ProjectPolicy.php` entirely, what HTTP status code would `Gate::authorize('view', $project)` produce, and why (hint: think about what Laravel does when no policy is registered for a model at all, versus a policy that explicitly returns `false`)?
4. The `@can` Blade directive and the controller's `Gate::authorize()` call the same policy method. Can you think of a scenario where they'd disagree — where `@can` says "show the button" but the controller-level check would still reject the request? (Hint: think about *when* each one runs relative to the request.)
