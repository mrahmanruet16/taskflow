# 003 — Blade Server-Rendered UI, No Separate Frontend Framework

# Decision

Render all UI with Laravel Blade templates, served directly from Laravel controllers. No separate React/Vue/Angular frontend, no separate JSON API layer between backend and UI.

# Context

The project brief is explicit: "Use Laravel Blade for the UI. Do NOT build a separate React/Vue frontend," and states the UI's entire purpose is to let the learning backend concepts be visually verified — "The goal is NOT to build a beautiful UI... a minimal Blade-based UI that allows me to visually verify that the backend actually works."

# Problem

How should the browser-facing UI be built, and how should it communicate with the backend?

# Options Considered

## Option A — Separate SPA (React/Vue) + JSON API

Advantages:
- Modern, common pattern for production apps with rich client-side interactivity.
- Clean separation between frontend and backend teams/concerns.

Disadvantages:
- Explicitly forbidden by the project brief.
- Adds an entire second application (build tooling, client-side routing, client-side state management, CORS configuration, API versioning concerns) whose complexity has nothing to do with this project's actual learning goals (backend request lifecycle, middleware, Eloquent, authorization).
- Every request becomes two hops to trace (browser → API → JSON response → client-side render) instead of one (browser → Laravel → Blade → HTML response) — directly works against the project's explicit goal to "trace an actual browser request through Laravel."

## Option B — Inertia.js (server-side routing/controllers, but Vue/React components instead of Blade)

Advantages:
- Gets some of React/Vue's component ergonomics while keeping Laravel's routing/controller model.
- Popular "best of both worlds" pattern in the Laravel ecosystem.

Disadvantages:
- Still not Blade, and still not what the brief asks for.
- Adds a rendering layer (Inertia's client-side page resolution) between the controller and the final HTML, which is unnecessary complexity for a project whose UI exists purely to verify backend state, not to demonstrate frontend component architecture.

## Option C — Blade, server-rendered

Advantages:
- Exactly what the brief requires.
- The simplest possible path from "controller returns data" to "browser shows HTML" — one hop, directly inspectable, matching `docs/architecture/request-lifecycle.md`'s traced example exactly.
- No build step beyond Vite compiling the app's minimal CSS/JS (already set up and verified in Phase 1) — no separate frontend dev server, no client-side routing to reason about.
- Forms are plain HTML `<form>` elements with Laravel's `@csrf`/`@method` directives — the actual HTTP verbs (GET/POST/PUT/DELETE) used throughout this app's routes are directly visible in the templates, reinforcing the HTTP-methods teaching goal (`docs/backend-concepts/http-and-rest.md`).

Disadvantages:
- No client-side interactivity beyond what plain HTML/CSS provides (no live-updating UI without a full page reload) — a real limitation for a production app, but explicitly not a goal here ("No need for... complex JavaScript").
- Full page reloads on every action — again, acceptable given the stated priorities.

# Decision Made

Option C — Blade.

# Why

This was an explicit, non-negotiable project requirement, and it also happens to be the correct choice given the actual goal: every screen in this app (login, projects list, task detail, etc.) exists to prove a specific backend behavior works, not to demonstrate frontend engineering. Blade keeps the "browser → Laravel → response" path as short and inspectable as possible.

# Consequences

- Every screen in `resources/views/` is a `.blade.php` file rendered directly by a controller's `return view(...)` call — no API endpoints returning JSON exist anywhere in this app (the one exception, `bootstrap/app.php`'s `shouldRenderJsonWhen()` for `api/*` routes, is Laravel skeleton boilerplate for a hypothetical future API surface that doesn't currently exist).
- Shared UI (`<x-layout>`) is a single Blade component, not a component library — deliberately minimal per the project's UI Philosophy section.
- Authorization checks that hide UI elements (`@can` directives) are explicitly documented throughout this app's views as *convenience only* — the actual enforcement is server-side (Policies, Gate::authorize) — see ADR 006 and `docs/backend-concepts/authorization-and-policies.md`, since a server-rendered app still fully depends on backend enforcement (a user could still craft a raw HTTP request bypassing whatever the rendered HTML shows).

# When We Would Reconsider This Decision

If a future project in this learning series explicitly wants to teach SPA/API architecture (the natural "next lesson" after mastering the request/response cycle in a monolith) — not applicable to TaskFlow as scoped, but a reasonable direction noted in `docs/FINAL-LEARNING-REPORT.md`'s "recommended concepts to study before Project 2" section (to be written).
