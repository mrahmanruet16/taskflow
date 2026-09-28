# 006 — Policy-Based Authorization

# Decision

Authorization decisions ("can this user do this to this resource") are implemented as Laravel Policy classes (`app/Policies/`), invoked via `Gate::authorize()` in controllers or `authorize()` in Form Requests — never as ad-hoc `if` checks scattered through controllers, and never as UI-only checks (hidden buttons).

# Context

Authentication (ADR 005) answers "who is this?" Authorization answers a different question: "is this specific, identified user allowed to do this specific thing to this specific resource?" This app has per-resource ownership (a `Project` has a `created_by`), so the answer depends on both the user *and* the resource instance, not just the user's role in the abstract.

# Problem

Where should "can user X edit project Y" logic live, and how do we guarantee it's actually enforced on every code path that touches a project (not just the ones a developer remembered to check)?

# Options Considered

## Option A — Inline `if` checks in each controller method

Advantages:
- No new files, no framework concept to learn.
- Obvious what's happening, at the point it happens.

Disadvantages:
- Easy to add a new controller method (or a second route to the same action) and forget the check — there's no structural reminder.
- Duplicates the same `$user->id === $project->created_by` logic in `show`, `edit`, `update`, `destroy` — a change to the ownership rule (e.g. adding roles) means hunting down every duplicate.
- Hard to unit test in isolation from HTTP.

## Option B — Laravel Policies (`Gate::authorize()` / `$user->can()`)

Advantages:
- One class per model, one method per action — a predictable place to look, and a predictable place to *add* a check when a new action is added.
- `Gate::authorize()` throws a `AuthorizationException` automatically rendered as HTTP 403 — consistent failure behavior everywhere, not hand-rolled per controller.
- Testable directly: `$policy->view($user, $project)` needs no HTTP request at all.
- Laravel auto-discovers policies by naming convention (`Project` → `ProjectPolicy`), so registering a new policy requires zero configuration — reduces the chance of "I wrote the policy but forgot to register it."

Disadvantages:
- One more file/concept per model.
- For very simple true/false rules, feels like ceremony compared to an inline `if`.

# Decision Made

Option B.

# Why

This app is explicitly built to teach authorization as a distinct concept from authentication, and to demonstrate why hiding UI elements alone is insufficient (a core stated requirement of the spec). A Policy class *is* the backend enforcement point — the same check backs both "should I show the Edit button" (`@can('update', $project)` in Blade, used for UI convenience) and "should I actually process this PUT request" (`Gate::authorize('update', $project)` in the controller, the real enforcement). Using the identical check in both places, sourced from one class, makes it structurally impossible for the UI check and the backend check to drift apart — which is exactly the failure mode Failure Experiment 1 (see `docs/testing/manual-verification.md`, added later) demonstrates by deliberately removing the backend check.

# Implementation

`app/Policies/ProjectPolicy.php` — `viewAny`, `view`, `create`, `update`, `delete`. Current rule (interim, until Project Membership exists): only the project's creator may view/update/delete; any authenticated user may create a project and see the (empty-for-them) index. See the TODO-equivalent comments in the policy file for exactly how this expands once roles (owner/manager/member/viewer) exist.

Invoked via:
- `Gate::authorize('viewAny', Project::class)` / `Gate::authorize('view', $project)` / `Gate::authorize('delete', $project)` directly in `ProjectController`
- `$this->user()->can('create', Project::class)` / `$this->user()->can('update', $this->route('project'))` inside `StoreProjectRequest`/`UpdateProjectRequest::authorize()` — Laravel calls a Form Request's `authorize()` automatically before validation runs, so an unauthorized request never even reaches the validation rules or the controller body.

# Consequences

- Every new Project action must ask "does this need a Policy check?" as a matter of course — the pattern is established, so skipping it on a new method would be the anomaly, not the norm.
- When Project Membership (roles) lands, only `ProjectPolicy` needs to change — controllers, Form Requests, and Blade `@can` directives stay untouched, because they all call through the same interface.

# When We Would Reconsider This Decision

If authorization rules become organization-wide rather than per-model (e.g. "any admin can do anything, anywhere"), a single global Gate-based rule or middleware might replace some per-model Policy checks — not warranted yet at this app's scale.
