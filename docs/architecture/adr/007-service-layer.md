# 007 — No Dedicated Service Layer

# Decision

Business logic lives directly in controllers (and, where genuinely reusable, in small Eloquent model methods — see ADR 004), not in a separate `app/Services/` layer of service classes sitting between controllers and models.

# Context

"Service layer" (a class like `ProjectService` with methods like `createProject()` that a controller calls instead of touching Eloquent directly) is a common pattern in larger Laravel applications, used to keep controllers thin and centralize business rules. The project brief explicitly leaves the door open to this — "If a service class is useful for business logic, use one. If a simpler Laravel approach is better, explain why" — while also warning generally against unnecessary abstraction.

# Problem

TaskFlow has at least one genuinely multi-step business operation (`ProjectController::store()` — create a project, attach the creator as Owner, write an activity log entry, all inside one transaction — see ADR 008). Should this logic live in the controller, or be extracted into a `ProjectService::createProject()` method?

# Options Considered

## Option A — Dedicated service classes (`app/Services/ProjectService.php`, `TaskService.php`, etc.)

Advantages:
- Keeps controllers extremely thin (a controller method becomes one line: `$this->projectService->create($request->validated(), Auth::user())`).
- If the same business operation needed to be triggered from more than one place (e.g. both an HTTP controller and a console command or queued job), a service class would be the natural shared entry point.
- Centralizes where to look for "what actually happens when a project is created."

Disadvantages:
- At TaskFlow's current scale, every piece of business logic has exactly ONE call site (the corresponding controller action) — there is no second caller that would benefit from extraction into a shared service.
- Adds a layer of indirection (controller → service → Eloquent) for logic that's currently short enough to read in place — the `DB::transaction()` block in `ProjectController::store()` is 10 lines, fully readable in its controller context without needing a jump to a separate file.
- Risk of becoming a "second controller" — a service class with no clear boundary can just as easily become a dumping ground for logic that duplicates what Eloquent model methods or Form Requests already do well (e.g. a service `validateProjectData()` method would just be reimplementing what `StoreProjectRequest` already does).

## Option B — No service layer; business logic lives in controllers, with genuinely reusable pieces factored into Eloquent model methods

Advantages:
- Matches this app's actual scale: every "business operation" (create project + attach owner + log activity; add member + log activity; update task + conditionally log status-change/assignment activity) has exactly one caller.
- The full request-handling flow (validate → authorize → business logic → persist → respond) stays visible in one file per action, directly matching `docs/architecture/request-lifecycle.md`'s traced example — a key teaching goal of this project.
- Reusable *logic* (not full workflows) still gets extracted, just onto the models it concerns (`Project::hasMember()`, `Task::isOverdue()`) rather than into a separate services directory — see ADR 004's reasoning for why this specific middle ground was chosen over both "everything inline" and "full repository/service layering."

Disadvantages:
- If a second caller for the same business operation appeared (e.g. a console command to bulk-import projects, needing the exact same create+attach+log sequence), that logic would need to be extracted at that point rather than being pre-extracted now.
- Controllers carry slightly more responsibility than a "thin controller, fat service" style would prescribe.

# Decision Made

Option B — no dedicated service layer.

# Why

The project brief's own core principle is explicit: don't introduce abstractions "simply to make the project look architecturally sophisticated," and use a service class only "if useful." At TaskFlow's actual scale — every business operation has exactly one HTTP call site — a service layer would be exactly that kind of premature abstraction: it would look more "enterprise," but every service class would have exactly one caller, exactly one collaborator (Eloquent), and would add a file and an indirection hop without solving any real problem this app currently has.

# Consequences

- `ProjectController::store()`, `ProjectMemberController::store()`/`destroy()`, and `TaskController::update()` each contain their own business logic directly — transaction boundaries, conditional activity-log writes, and the last-owner-removal guard are all readable in the controller method itself, not one hop away in a service class.
- If TaskFlow later needs the same "create a project" logic from a second entry point (e.g. a scheduled job, an Artisan command, or an API endpoint), that would be the concrete trigger to extract a service — at that point, with two real callers, the abstraction pays for itself.
- This decision is explicitly a middle ground, not an extreme: ADR 004 already established that reusable *logic* (not full workflows) belongs on models, not scattered as duplicated code across controllers — service classes are the option skipped specifically for full multi-step *workflows* with a single caller.

# When We Would Reconsider This Decision

The moment any business operation needs a second real caller (a second controller action, a console command, a queued job, or a future API endpoint) — extract a service class for that specific operation at that point, rather than speculatively building the whole layer now.
