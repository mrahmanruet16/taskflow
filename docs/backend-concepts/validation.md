# Validation

## 1. What is it?

Validation is checking that incoming request data has the right *shape* before it's used — required fields present, correct types, strings within length limits, dates that parse, emails that look like emails. In this app, validation lives in Form Request classes (`app/Http/Requests/`).

## 2. What problem does it solve?

Without validation, a `POST /projects` request with no `name` field, or a `due_date` of `"banana"`, would either crash somewhere deep in Eloquent/PostgreSQL with a confusing error, or — worse — silently insert bad data (an empty name, a null where the column allows it). Validation catches bad input at the door, with a clear, user-facing error message, before it reaches any business logic or the database.

## 3. How does it work?

1. A controller method type-hints a Form Request instead of the generic `Request` (e.g. `store(StoreProjectRequest $request)`).
2. Before the controller method body runs, Laravel: (a) calls `authorize()` — see `docs/backend-concepts/authorization-and-policies.md` — and (b) runs the data through `rules()`.
3. If any rule fails, Laravel automatically redirects back to the previous page (for a normal form POST) with the errors flashed into the session, and the old input flashed too (so `old('name')` in Blade can repopulate the form) — the controller method body never even runs.
4. If validation passes, `$request->validated()` returns only the fields that were declared in `rules()` — this is a deliberate safety net: even if the HTML form (or a malicious client) submits extra fields like `created_by` or `id`, `validated()` won't include them unless they were explicitly listed as a rule. This is what actually prevents "mass assignment" style attacks in this app, working together with the model's `#[Fillable(...)]` attribute.
5. In Blade, `@if ($errors->any())` / `$errors->all()` reads the flashed error bag and displays messages; `old('field')` repopulates what the user typed so they don't have to retype everything after a validation failure.

## 4. Where is it used in this project?

- `app/Http/Requests/Auth/RegisterRequest.php`, `LoginRequest.php`
- `app/Http/Requests/StoreProjectRequest.php`, `UpdateProjectRequest.php`
- `resources/views/auth/register.blade.php`, `auth/login.blade.php`, `projects/create.blade.php`, `projects/edit.blade.php` — all display `$errors` and use `old()`

### Validation vs. business rules

`StoreProjectRequest` includes `'due_date' => ['nullable', 'date', 'after_or_equal:start_date']` — this is a *validation* rule because it's purely about the shape/consistency of the submitted data itself, checkable without touching the database or any other resource. A rule like "a project cannot be marked `completed` if it still has open tasks" (not yet implemented — will matter once Tasks exist) would be a *business rule*, not a validation rule — it requires querying related data and belongs in a Service or the model, not the Form Request, because Form Requests are meant to answer "is this input well-formed," not "is this operation currently allowed given the rest of the system's state."

## 5. What happens without it?

If the `'name' => ['required', ...]` rule were removed from `StoreProjectRequest`: a `POST /projects` with no `name` field would reach `Project::create([...])`. Since the `projects.name` database column is `NOT NULL` (per the migration), PostgreSQL itself would reject the insert with a database-level constraint violation — which Laravel would surface as an unhandled `QueryException`, producing a raw 500 error page instead of a friendly "Name is required" message next to the form field. The database constraint is a real safety net (see `docs/architecture/adr/002-postgresql.md` and Failure Experiment 5), but it's the *wrong layer* to be the user's only feedback — validation is supposed to catch this earlier, with a better error.

## 6. Alternatives

- **Validating inline in the controller** (`$request->validate([...])`) — works for very simple cases, but mixes validation rules into the controller body and doesn't have its own `authorize()` hook — Form Requests were chosen because this app also needs per-request authorization (see ADR 006), and Form Requests are the conventional place both concerns live together.
- **Model-level validation** (rules defined on the Eloquent model itself) — Laravel has no first-party built-in for this; third-party packages exist, but this couples validation to the model rather than to the specific *request* being made (e.g. "required on create, optional on update" is naturally expressed as two different Form Requests, awkward to express as one set of model rules).

## 7. Trade-offs

One Form Request class per "shape of input" (Store vs Update, since required-ness sometimes differs) means more files than inline validation, but each one is independently testable and readable in isolation, and rules aren't duplicated by copy-paste across controller methods.

## 8. Production considerations

Complex cross-field or cross-resource validation (e.g. "this date range must not overlap another project's date range for the same team") can get expensive if it triggers extra queries on every request — worth caching/optimizing only if it's demonstrated to matter, not preemptively.

## 9. How do I verify it?

Automated:
```bash
php artisan test --filter=ProjectTest
```
(`test_project_creation_requires_a_name`, `test_due_date_cannot_be_before_start_date` both assert `assertSessionHasErrors` and that nothing was written to the database.)

Manual (a concrete validation failure through the UI, as the spec requires):
1. Log in, go to `/projects/create`.
2. Leave "Name" blank, submit.
3. You should stay on the create form (not be redirected away) with a red error message listing "The name field is required," and any other fields you'd already typed should still be filled in.

## 10. Questions for me

1. Why does `$request->validated()` — not `$request->all()` — get passed to `Project::create()` in `ProjectController::store()`? What's the concrete difference in what could happen if `all()` were used instead?
2. `due_date`'s `after_or_equal:start_date` rule only fires if `start_date` is also present. What happens if a request submits `due_date` but omits `start_date` entirely — does the rule still run, and against what?
3. If two different validation rules on the same field both fail (e.g. `name` is both empty and would have been too long), does Laravel report both errors or just the first one? How would you check?
4. `RegisterRequest` and `StoreProjectRequest` both have an `authorize()` method, but one always returns `true`. Why does a validation-only request still need this method defined at all — what would happen if it were omitted entirely?
