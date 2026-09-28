# 005 — Session-Based Authentication, Hand-Rolled (No Starter Kit)

# Decision

Implement authentication manually using Laravel's core `Auth` facade, `web` guard, and session driver, with our own controllers/routes/Form Requests/Blade views — rather than installing a starter kit (Breeze, Fortify, or Jetstream).

# Context

This is a server-rendered Blade application (ADR 003). A logged-out visitor must be able to register and log in; a logged-in visitor's identity must persist across requests without re-sending credentials every time; certain routes must be inaccessible to guests. Laravel's ecosystem offers pre-built starter kits that scaffold this in one `composer require` + `artisan install` command.

# Problem

How should registration, login, logout, and route protection be implemented, given that the explicit purpose of this project is to *learn* backend engineering rather than ship a product quickly?

# Options Considered

## Option A — Laravel Breeze / Fortify / Jetstream (starter kit)

Advantages:
- Fast: working auth in minutes.
- Battle-tested, matches Laravel conventions exactly.
- Handles edge cases (password reset, email verification) out of the box.

Disadvantages:
- Generates a large amount of code the developer didn't write and may not read line-by-line — directly works against the stated learning goal ("I need to understand... not merely generate code").
- Adds a dependency with its own upgrade/compatibility surface, with no documented justification (the project's own rule: "Every third-party dependency must have a documented reason").
- Breeze in particular scaffolds its own frontend scaffolding conventions (Blade + Tailwind, or Inertia) that may not match this project's intentionally minimal UI philosophy.

## Option B — Hand-rolled controllers, Form Requests, and Blade views using core `Auth`/`Session` facades

Advantages:
- Every line of the auth flow is visible and explainable — matches the project's explicit teaching requirement to trace requests through middleware, controllers, validation, and sessions.
- Zero new dependencies.
- Full control over exactly which fields/flows exist (this app has no email verification or password-reset requirement in the spec, so nothing unused is scaffolded).

Disadvantages:
- More code to write by hand.
- Easier to miss a security edge case (e.g. session fixation) that a maintained package would handle — mitigated by using Laravel's own `Auth::attempt()` and `$request->session()->regenerate()`, which are the same primitives Breeze itself calls internally.

# Decision Made

Option B — hand-rolled authentication using `Illuminate\Support\Facades\Auth`, the default `web` guard (session driver, already configured in `config/auth.php` and `config/session.php` from the Laravel skeleton), Form Request classes for validation, and plain Blade views.

# Why

The project's stated primary objective is understanding *how* authentication works (sessions, cookies, password hashing, middleware), not shipping the feature fastest. A starter kit would hide exactly the mechanics this project exists to teach. The app's auth requirements (register, login, logout, protected routes — no email verification, no password reset, no 2FA) are small enough that hand-rolling is not meaningfully more code than configuring a starter kit's options would be.

# Consequences

- We own the full account lifecycle. If we later want password reset or email verification, we implement it explicitly (and document it) rather than getting it "for free."
- Session configuration comes from Laravel's own defaults (`config/session.php`: `SESSION_DRIVER=database`, 120 minute lifetime) — no changes needed there.
- Route protection uses Laravel's built-in `auth` middleware alias (registered by the framework itself, no custom middleware needed for the basic case).

# When We Would Reconsider This Decision

If a future phase of this learning track needs password reset, email verification, 2FA, or social login, and the goal shifts from "understand every mechanic" to "build a second app faster," re-evaluate Fortify/Jetstream at that point — not before.
