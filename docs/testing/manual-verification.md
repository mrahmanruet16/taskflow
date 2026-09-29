# Manual Verification Checklist

A step-by-step walkthrough to confirm the whole application actually works, end to end, in a real browser — not just via the automated test suite. Every step below has been performed at least once during this project's development (via `curl` against a real running server, documented throughout `docs/PROJECT-STATE.md`'s per-phase Verification History); this document reorganizes those scattered checks into one coherent sequence a human can follow.

## Prerequisites

```bash
cd taskflow
php artisan serve
```

Seed data must be present (`php artisan db:seed` — see `docs/SETUP.md` if not already seeded). Open `http://127.0.0.1:8000` in a browser.

## Part 1 — Authentication

- [ ] **1.** Visit `/`. Confirm the welcome page loads (no login required for this page).
- [ ] **2.** Visit `/dashboard` directly, without logging in. Confirm you're redirected to `/login` — this is the `auth` middleware enforcing the boundary (see `docs/backend-concepts/middleware.md`).
- [ ] **3.** Visit `/login`. Enter an intentionally wrong password for `admin@example.test`. Confirm you see a validation error ("These credentials do not match our records") and remain on the login page — not a crash.
- [ ] **4.** Log in as `admin@example.test` with the correct password (`password`, from `docs/SETUP.md`). Confirm you land on `/dashboard`.
- [ ] **5.** While logged in, visit `/login` again. Confirm you're redirected away (the `guest` middleware — an already-authenticated user has no reason to see a login form).

## Part 2 — Dashboard

- [ ] **6.** On `/dashboard`, confirm you see your name/email, and five real numbers: Projects, Tasks, Completed Tasks, Pending Tasks, Overdue Tasks. As `admin@example.test` with the standard seed data, these should read approximately 6 / 60 / 13 / 35 / 12 (exact values may differ slightly if the seed data has been modified since seeding).
- [ ] **7.** Confirm the Overdue count is non-zero — the seeder deliberately creates overdue tasks so this is always demonstrable without extra setup.

## Part 3 — Projects

- [ ] **8.** Click "Projects" in the nav. Confirm the list shows only projects `admin@example.test` is a member of (all 6 seeded projects, since admin is on every one) — not every project in the system.
- [ ] **9.** Click "Create Project". Submit the form with a name, status, and dates. Confirm you're redirected to the new project's detail page with a "Project created." confirmation message.
- [ ] **10.** Go back to `/projects`. Confirm the new project now appears in the list.
- [ ] **11.** Open the new project. Confirm you see its name, description, status, owner, dates, an empty members/tasks list (aside from yourself as Owner), and an Activity section showing "... created project ..." — written automatically by the transaction in `ProjectController::store()` (see ADR 008).
- [ ] **12.** Click "Manage Members". Add a member by email — use `member@example.test`, role "Manager". Confirm the member list updates and an activity entry appears.
- [ ] **13.** Attempt to add the SAME email again. Confirm you get a validation error ("This user is already a member") rather than a raw database error.

## Part 4 — Tasks

- [ ] **14.** From the project detail page, click "Create Task". Submit with a title, status, priority, and assign it to the member you just added. Confirm redirect to the task's own page (`/tasks/{id}`).
- [ ] **15.** Confirm the task page shows title, status, priority, assigned user, due date, created-by, and an Activity section with "... created task ...".
- [ ] **16.** Go back to the project page. Confirm the new task now appears in its task list.
- [ ] **17.** On the task page, use the "Change Status" quick form to move it to a different status. Confirm the page reflects the change and a NEW activity entry appears with the exact format: `"[name] changed task '...' status from '...' to '...'."`
- [ ] **18.** Visit `/tasks` (the global task list). Confirm your new task appears. Filter by `?status=` matching its current status — confirm it still appears; filter by a different status — confirm it does NOT appear.

## Part 5 — Comments

- [ ] **19.** On the task page, submit a comment. Confirm it appears immediately with your name and a timestamp, and a "... commented on task ..." activity entry is added.
- [ ] **20.** Click "Edit" next to your own comment. Change the text, save. Confirm the update is reflected.
- [ ] **21.** Log out (Part 6 below), log back in as a DIFFERENT user who is also a member of this project (e.g. `manager@example.test`), and confirm there is **no** Edit/Delete option next to the comment you just wrote as admin — even though `manager@example.test` has full authority over the task itself, comment ownership is author-only (see `docs/architecture/adr/` — `CommentPolicy` deliberately breaks from every other policy's "management roles can act on anything" pattern).

## Part 6 — Logout and Cross-User Authorization

- [ ] **22.** Click "Logout". Confirm you're redirected to `/` and that `/dashboard` now redirects to `/login` again (the session was actually invalidated, not just hidden client-side).
- [ ] **23.** Log in as `viewer@example.test`. Confirm the dashboard shows different numbers than admin's did (Viewer sees the same 6 projects, since seeded onto all of them, but this confirms the aggregation is genuinely per-user, not a shared/cached value).
- [ ] **24.** As `viewer@example.test`, open one of the **original seeded projects** — NOT the one you created in Part 3 of this walkthrough. This distinction matters: `viewer@example.test` was never added as a member of your newly-created project (you only added `member@example.test` in step 12), so opening it as viewer correctly returns a **403 for the entire page**, not just a missing button — this is different from, and easy to confuse with, step 25 below. On a project viewer genuinely belongs to (one of the 6 seeded ones), the page loads (200) but confirm there is **no** "Create Task" button and **no** "Add Member" capability — Viewer is excluded from both by `TaskPolicy::create()`/`ProjectPolicy::manageMembers()`. *(This distinction — "not a member at all" vs. "a member with a restricted role" — was discovered and corrected during this document's own verification: an earlier draft of this checklist didn't distinguish the two cases and would have sent you to the wrong project.)*
- [ ] **25.** Manually navigate to `/projects/{id}/edit` for one of the SEEDED projects (where viewer is a genuine member) as `viewer@example.test` (typing the URL directly, not clicking a hidden button). Confirm you get a **403 page**, not the edit form — this is the concrete proof that authorization is enforced server-side, not just by hiding UI elements (the spec's own explicit warning: hiding a button is never sufficient).

## Part 7 — Error Pages

- [ ] **26.** Visit a nonsense URL, e.g. `/this-does-not-exist`. Confirm you see the custom 404 page (`resources/views/errors/404.blade.php`), not Laravel's default.
- [ ] **27.** Visit `/projects/999999` (a nonexistent project ID) while logged in. Confirm you also see the custom 404 page — this is `ModelNotFoundException` from route-model binding, a different code path than an unmatched route, converging on the same view.
- [ ] **28.** (Optional, requires editing `.env`) Set `APP_DEBUG=false`, restart `php artisan serve`, trigger a genuine server error (there is no built-in way to do this without temporarily adding a throwing route — see `docs/testing/failure-experiments.md`'s Experiment methodology if you want to reproduce this exactly). Confirm the custom 500 page renders with no exception details. Revert `APP_DEBUG` to `true` afterward.

## What This Checklist Does NOT Cover

- Pagination beyond one page of results (requires more seed data than the default 10-per-page projects list or 15-per-page task list to observe page 2 — the seeded data has enough tasks (60) to see this on `/tasks`, but not enough projects (6) to see it on `/projects`).
- Load/performance testing at scale — this app has never been tested beyond the seeded data's volume (tens to hundreds of rows), consistent with its purpose as a learning project, not a production load-testing target.
- Browser compatibility testing across different browsers/devices — the UI is intentionally minimal HTML/CSS (no JavaScript framework, no browser-specific features used), so this was not treated as a priority area.

## How This Relates to the Automated Test Suite

Every behavior checked manually above also has a corresponding automated test in `tests/Feature/` (61 tests total, `php artisan test`). The manual checklist exists for a different purpose: automated tests confirm the *code* behaves correctly under controlled conditions; this checklist confirms the *actual rendered pages* look and behave sensibly when a real person clicks through them — catching issues automated tests structurally can't (a broken CSS layout, a confusing error message, a button that's technically present but visually broken) — see `docs/backend-concepts/eloquent.md`'s note on why Laravel favors feature tests, and treat this checklist as the manual complement to that automated coverage, not a replacement for it.
