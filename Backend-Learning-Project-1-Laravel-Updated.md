# Execution Plan — Two Explicit Phases

Do not implement the whole application in one unverified pass. Work in two phases and pause for my confirmation between them.

## Phase 1 — Bootstrap and prove the foundation

First inspect the repository and environment. Do not modify system packages or install global tools. Create the Laravel application using the latest stable compatible version specified above, configure it to use my existing local PostgreSQL 16 instance, and build only a minimal working foundation.

Phase 1 must:

1. Confirm PHP, Composer, Node/npm, PostgreSQL connectivity, and required extensions.
2. Create the Laravel project and explain the chosen Laravel version.
3. Configure `.env` for PostgreSQL without committing secrets. Use a dedicated development database and ask before creating, dropping, or resetting any database.
4. Run migrations against PostgreSQL and verify the connection through Laravel/Eloquent.
5. Add one minimal Blade page and verify it renders in a browser.
6. Verify Vite builds successfully.
7. Run the default test suite.
8. Add a concise `docs/architecture/version-matrix.md` and `docs/SETUP.md`.
9. Report commands run, results, files changed, any warnings, and how I can verify the page locally.

Do not implement projects, tasks, comments, roles, activity logs, or other business features during Phase 1. Stop and wait for my approval before Phase 2.

## Phase 2 — Implement the learning project

Only after I approve Phase 1, implement the functional requirements below. Keep the app runnable, run relevant tests after each major feature group, and update the learning docs and ADRs alongside the implementation. Do not claim a test/build/browser check passed unless you actually ran it and report its result.

---

# Role

You are my senior Laravel backend engineer AND backend instructor.

I am primarily a frontend engineer and I am learning backend engineering.

Your job is to generate a complete, working, full-stack Laravel application while teaching me the backend concepts behind every important implementation decision.

The goal is NOT to build a beautiful UI.

The goal is to build a realistic backend system with a minimal Blade-based UI that allows me to visually verify that the backend actually works.

I will use the generated codebase as a backend learning project.

I want to understand:

- what happens when a browser sends a request
- how Laravel routes the request
- how middleware works
- how authentication works
- how authorization works
- how controllers work
- how validation works
- how services/business logic work
- how database queries work
- how Eloquent works
- how relationships work
- how transactions work
- how errors are handled
- how sessions/authentication work
- how Blade receives backend data
- how database changes become visible in the UI
- what can go wrong
- how to test and verify everything

Every meaningful architectural decision must be documented with its reasoning and trade-offs.

---

# Project

Build a full-stack **Project Management / Task Management application**.

The application should be similar to a simplified Jira/Trello.

It should support:

```text
Users
  ↓
Projects
  ↓
Project Members
  ↓
Tasks
  ↓
Comments
  ↓
Activity Logs
```

Use Laravel Blade for the UI.

Do NOT build a separate React/Vue frontend.

---

# Verified Local Environment and Technology Stack

My development machine has been verified with this environment:

```text
OS: Ubuntu 24.04.5 LTS (Noble)
PHP CLI: PHP 8.5.x (installed from the Ondrej Surý PHP PPA)
Composer: 2.10.3
Node.js: 20.19.6
npm: 10.8.2
PostgreSQL: 16, native Ubuntu service/cluster, online on port 5432
PHP extensions: pdo_pgsql and pgsql enabled; Laravel-required extensions verified
Git: 2.43.0
```

Use **the latest stable Laravel major version compatible with PHP 8.5**, targeting Laravel 13 if it is still the latest stable compatible release at implementation time. Do not use an alpha, beta, RC, nightly, or other prerelease version. Verify current compatibility against official Laravel release/upgrade documentation and the package metadata before creating the app; record the URLs and date checked in `docs/architecture/version-matrix.md`.

Do not blindly install a version based only on this prompt. If the latest stable Laravel release has changed, select that release only if it supports PHP 8.5 and the installed PostgreSQL/Node toolchain. If there is a conflict, stop and explain it before proceeding.

Use:

- PHP 8.5
- latest compatible stable Laravel (target: Laravel 13)
- Blade server-rendered UI
- PostgreSQL 16 (use the existing native PostgreSQL instance; do not start a second PostgreSQL container)
- Eloquent ORM
- Laravel migrations, seeders, and factories
- Laravel validation, authentication, and authorization/policies
- PHPUnit or Pest, following the Laravel version's conventional default
- Vite for the assets that actually need building
- Native local development by default; Docker/Sail is optional and must not be introduced unless there is a concrete learning or reproducibility benefit

Before implementation, verify and document PHP, Laravel, PostgreSQL, Node, npm, Composer, and required PHP extensions. Do not downgrade PHP or silently switch to MySQL/SQLite. Tests may use SQLite only if there is a documented reason and PostgreSQL-specific behavior is separately tested against PostgreSQL; prefer PostgreSQL for this learning project.

Do not introduce unnecessary libraries. Every third-party dependency must have a documented reason. Do not install or upgrade system packages without asking me first; the environment has already been prepared.

---

# Important Architecture Principle

Keep the application understandable.

Do not over-engineer it.

Use Laravel conventions wherever they are appropriate.

Prefer:

```text
Route
  ↓
Middleware
  ↓
Controller
  ↓
Form Request / Validation
  ↓
Service / Business Logic
  ↓
Eloquent Model
  ↓
PostgreSQL
  ↓
Controller
  ↓
Blade View
  ↓
Browser
```

Do not introduce repositories, unnecessary interfaces, or complicated design patterns simply because they are considered "enterprise architecture".

If a service class is useful for business logic, use one.

If a simpler Laravel approach is better, explain why.

The purpose is to learn good backend engineering, not to maximize abstraction.

---

# Functional Requirements

## 1. Authentication

Implement:

- Registration
- Login
- Logout
- Protected routes
- Session-based authentication
- Password hashing
- Authentication middleware

Users should be able to log in through the browser.

After login, redirect them to a dashboard.

---

# 2. Dashboard

Create a simple Blade dashboard.

Display:

```text
Projects
Tasks
Completed Tasks
Pending Tasks
Overdue Tasks
```

Also display the current authenticated user.

The dashboard exists primarily to demonstrate that backend queries are correctly retrieving and aggregating database information.

Keep the UI simple.

---

# 3. Projects

Users should be able to:

- Create project
- View project
- Edit project
- Delete project
- List projects

Project fields:

```text
id
name
description
status
start_date
due_date
created_by
created_at
updated_at
```

Possible statuses:

```text
planned
active
completed
archived
```

---

# 4. Project Members

A project can contain multiple users.

A user can belong to multiple projects.

Implement a many-to-many relationship.

Example:

```text
Project
    ↓
Project Members
    ↓
Users
```

Each project member should have a role:

```text
owner
manager
member
viewer
```

Users should be able to:

- Add project members
- Remove project members
- View project members

Explain how Laravel's many-to-many Eloquent relationship works.

---

# 5. Tasks

Each project can contain multiple tasks.

Task fields:

```text
id
project_id
assigned_to
created_by
title
description
status
priority
due_date
created_at
updated_at
```

Status:

```text
todo
in_progress
completed
cancelled
```

Priority:

```text
low
medium
high
urgent
```

Users should be able to:

- Create task
- View task
- Edit task
- Delete task
- Assign task
- Change status
- Change priority

---

# 6. Comments

Users can comment on tasks.

Implement:

```text
Task
 ↓
Comments
 ↓
User
```

Users should be able to:

- Add comment
- View comments
- Edit their own comment
- Delete their own comment

---

# 7. Activity Logs

Create an activity log system.

Record important events such as:

```text
Project created
Project updated
Project deleted
Member added
Member removed
Task created
Task updated
Task assigned
Task status changed
Comment created
Comment deleted
```

Each activity should contain enough information to understand:

```text
who
did what
to which resource
when
```

For example:

```text
John changed Task #15 status from "todo" to "completed".
```

The activity log should be visible from the project/task UI.

---

# Blade UI Requirements

The UI is intentionally minimal.

Do NOT spend significant time on visual design.

Use:

- Blade layouts
- Blade components where useful
- simple CSS
- forms
- tables
- buttons
- alerts
- badges
- navigation
- basic responsive layout

The UI should be clean enough to use but should primarily function as a verification interface.

---

# Required Screens

Create at least:

```text
/login

/register

/dashboard

/projects

/projects/create

/projects/{id}

/projects/{id}/edit

/projects/{id}/members

/tasks/{id}
```

---

# Project List UI

Display:

```text
Project Name
Status
Owner
Members
Tasks
Due Date
Actions
```

Actions:

```text
View
Edit
Delete
```

Include:

```text
Create Project
```

button.

---

# Project Details UI

Display:

```text
Project name
Description
Status
Owner
Dates
Members
Tasks
Activity
```

Provide:

```text
Add Member
Create Task
Edit Project
Delete Project
```

---

# Task UI

Display:

```text
Title
Description
Status
Priority
Assigned User
Due Date
Created By
Comments
Activity
```

Allow:

```text
Edit
Delete
Change Status
Assign User
Add Comment
```

---

# Database

Use PostgreSQL.

Create proper Laravel migrations.

Tables should include approximately:

```text
users

projects

project_user

tasks

comments

activity_logs
```

You may introduce additional tables if technically justified.

Use:

- primary keys
- foreign keys
- unique constraints
- NOT NULL constraints
- indexes
- timestamps

Do not add indexes blindly.

Every non-obvious index must have a reason documented.

---

# Database Relationships

Implement and document:

```text
User
 ├── projectsCreated
 ├── projects
 ├── tasksCreated
 ├── assignedTasks
 ├── comments
 └── activities

Project
 ├── owner
 ├── members
 ├── tasks
 └── activities

Task
 ├── project
 ├── creator
 ├── assignee
 ├── comments
 └── activities

Comment
 ├── task
 └── user
```

Explain how each Eloquent relationship works.

---

# Eloquent Learning Requirement

Do not hide all database access behind magic.

I need to understand Eloquent.

For important queries, document:

- what SQL Eloquent approximately generates
- which relationship is being used
- whether lazy loading occurs
- whether eager loading is needed
- how N+1 queries can occur

Create a learning document:

`docs/backend-concepts/eloquent.md`

Include examples such as:

```php
Project::with('members', 'tasks')->get();
```

Explain:

- `with()`
- `where()`
- `find()`
- `findOrFail()`
- `create()`
- `update()`
- `delete()`
- relationships
- scopes if used
- eager loading
- lazy loading

---

# N+1 Query Demonstration

Intentionally create a small learning demonstration showing the difference between:

```php
Project::all();
```

followed by accessing relationships individually,

versus:

```php
Project::with('tasks')->get();
```

Document the number/type of database queries generated.

The application should ultimately use the correct approach in production code.

The purpose is to teach me why N+1 queries matter.

---

# Validation

Use Laravel Form Request classes where appropriate.

Example:

```text
StoreProjectRequest
UpdateProjectRequest
StoreTaskRequest
UpdateTaskRequest
StoreCommentRequest
```

Explain:

- request validation
- authorization inside Form Requests
- validation rules
- validation vs business rules
- validation errors
- how Blade displays validation errors

Demonstrate at least one validation failure through the UI.

---

# Authorization

Implement Laravel Policies.

Examples:

```text
ProjectPolicy
TaskPolicy
CommentPolicy
```

Users should not automatically be allowed to modify every resource.

Examples:

- Only authorized project members can view a project.
- Only appropriate project roles can modify a project.
- Users can edit their own comments.
- Users cannot access another project's tasks.
- Users cannot modify resources belonging to projects they don't have access to.

Explain:

```text
Authentication
vs
Authorization
```

and show where each happens.

---

# Important Security Requirement

Never rely only on hiding buttons in Blade.

For example, this is NOT sufficient:

```blade
@if($userCanDelete)
    <button>Delete</button>
@endif
```

The backend must enforce authorization.

Explain why a malicious user can bypass UI restrictions.

---

# Transactions

Identify operations that genuinely require transactions.

At least one operation should demonstrate a transaction involving multiple database changes.

For example:

```text
Create project
+
Add creator as project owner
+
Create activity log
```

or another appropriate workflow.

Use Laravel's transaction facilities.

Document:

- atomicity
- rollback
- consistency
- what happens when an operation fails halfway
- why a transaction is needed
- what happens without a transaction

Do not use transactions everywhere without justification.

---

# Error Handling

Implement consistent error handling.

Handle:

```text
404
403
422
500
database failures
validation failures
authentication failures
```

Create appropriate Blade error pages where useful.

For example:

```text
resources/views/errors/404.blade.php
resources/views/errors/403.blade.php
resources/views/errors/500.blade.php
```

Explain:

- expected errors
- unexpected errors
- validation errors
- authorization errors
- server errors

Do not expose stack traces or sensitive internal information to normal users in production mode.

---

# Sessions

Because this is a Blade application using Laravel authentication, explain:

- what a session is
- how Laravel stores session information
- what happens after login
- how the browser maintains authentication
- cookies
- session IDs
- logout behavior

Create:

`docs/backend-concepts/authentication-and-sessions.md`

---

# HTTP Learning

Create:

`docs/backend-concepts/http-and-rest.md`

Explain:

- HTTP request
- HTTP response
- URL
- route
- HTTP method
- headers
- cookies
- sessions
- status codes
- GET
- POST
- PATCH
- DELETE
- redirects

Trace an actual browser request through Laravel.

---

# Request Lifecycle

Create:

`docs/architecture/request-lifecycle.md`

Explain this exact flow:

```text
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

Explain what happens at every stage.

Use one concrete example:

```text
User submits "Create Task"
```

and trace the entire request.

---

# Pagination

Implement pagination on:

```text
Projects
Tasks
Comments
Activity Logs
```

Use Laravel pagination.

Document:

- offset pagination
- cursor pagination
- trade-offs
- when cursor pagination becomes useful

Explain what SQL/database behavior occurs when pagination is used.

---

# Search / Filtering / Sorting

Implement basic task filtering:

```text
status
priority
assignee
```

and sorting:

```text
created_at
due_date
priority
```

Use query parameters.

Example:

```text
/tasks?status=in_progress&priority=high
```

Document:

- query parameters
- query builder
- filtering
- sorting
- indexes
- SQL query construction

---

# Database Seeding

Create realistic seed data.

The seed database should contain:

```text
10+ users
5+ projects
50+ tasks
100+ comments
activity logs
```

Create convenient demo accounts.

For example:

```text
admin@example.test
manager@example.test
member@example.test
viewer@example.test
```

Use clearly documented demo passwords.

---

# Factories

Create Laravel factories for:

```text
User
Project
Task
Comment
ActivityLog
```

Explain the difference between:

```text
Factories
vs
Seeders
```

---

# Testing

Use Laravel's testing tools.

Create:

## Unit tests

For business logic that genuinely benefits from unit testing.

## Feature tests

Test:

- authentication
- authorization
- project creation
- project editing
- project deletion
- task creation
- task assignment
- comment creation
- validation failures
- unauthorized access

## Database tests

Test relationships and important database behavior.

Explain why Laravel feature tests are often more valuable than testing controllers in isolation.

---

# Browser Verification

The application must be visually testable.

For every major feature, provide a manual verification checklist.

Example:

```text
1. Login as admin.
2. Open Projects.
3. Create a project.
4. Verify project appears in list.
5. Open project.
6. Add a member.
7. Create a task.
8. Assign task.
9. Change task status.
10. Add a comment.
11. Verify activity log.
12. Logout.
13. Login as another user.
14. Verify authorization restrictions.
```

Create:

`docs/testing/manual-verification.md`

---

# Failure Experiments

This is extremely important.

For each major backend concept, provide an experiment where I intentionally break something.

Examples:

## Experiment 1 — Authorization

Remove a policy check.

Try accessing another user's project.

Explain what becomes possible.

---

## Experiment 2 — Validation

Remove a validation rule.

Submit invalid data.

Observe what reaches the database.

---

## Experiment 3 — Transaction

Remove the transaction from a multi-step operation.

Force the second operation to fail.

Observe partial data.

---

## Experiment 4 — N+1

Remove eager loading.

Enable query logging.

Observe the number of queries.

---

## Experiment 5 — Database Constraint

Temporarily remove a foreign key constraint.

Try inserting invalid data.

Explain why database-level constraints still matter even when application validation exists.

---

# Logging

Implement useful Laravel logging.

For important operations include:

- user ID
- request information where appropriate
- resource ID
- action
- timestamp

Do NOT log:

- passwords
- authentication tokens
- sensitive personal information

Explain the difference between:

```text
Application logs
Database logs
Error logs
Audit logs
```

---

# Architecture Decision Records

Create:

```text
docs/architecture/adr/
```

Every meaningful architecture decision must have an ADR.

At minimum:

```text
001-laravel-monolith.md
002-postgresql.md
003-blade-server-rendered-ui.md
004-eloquent-orm.md
005-session-authentication.md
006-policy-based-authorization.md
007-service-layer.md
008-database-transactions.md
009-pagination-strategy.md
010-testing-strategy.md
```

Each ADR must contain:

```text
# Decision

# Context

# Problem

# Options Considered

## Option A

Advantages:
Disadvantages:

## Option B

Advantages:
Disadvantages:

# Decision Made

# Why

# Consequences

# When We Would Reconsider This Decision
```

Do not write:

> "Laravel is better."

Instead explain concrete trade-offs.

---

# Backend Learning Documentation

Create:

```text
docs/
├── architecture/
│   ├── system-overview.md
│   ├── request-lifecycle.md
│   ├── database-design.md
│   ├── architecture-decisions.md
│   └── adr/
│
├── backend-concepts/
│   ├── http-and-rest.md
│   ├── laravel-request-lifecycle.md
│   ├── routing.md
│   ├── middleware.md
│   ├── controllers.md
│   ├── validation.md
│   ├── authentication-and-sessions.md
│   ├── authorization-and-policies.md
│   ├── eloquent.md
│   ├── database-relationships.md
│   ├── database-indexes.md
│   ├── transactions.md
│   ├── pagination.md
│   ├── error-handling.md
│   └── logging.md
│
├── database/
│   └── database-design.md
│
├── security/
│   └── security-model.md
│
└── testing/
    ├── testing-strategy.md
    └── manual-verification.md
```

---

# Learning Note Format

Every concept document must answer:

## 1. What is it?

Explain it simply.

## 2. What problem does it solve?

Explain why it exists.

## 3. How does it work?

Explain the mechanism.

## 4. Where is it used in this project?

Point to actual files/classes/routes.

## 5. What happens without it?

Show a concrete failure example.

## 6. Alternatives

Explain reasonable alternatives.

## 7. Trade-offs

Explain advantages and disadvantages.

## 8. Production considerations

Explain what changes at scale.

## 9. How do I verify it?

Give commands or UI steps.

## 10. Questions for me

Give questions that test understanding.

---

# Code Comments

Do NOT fill the code with comments explaining obvious syntax.

Bad:

```php
// Get the project
$project = Project::find($id);
```

Instead, comments should explain non-obvious backend reasoning.

For example:

```php
// Eager-load members and tasks because this view renders both
// collections. Without eager loading this can create N+1 queries.
$project = Project::with(['members', 'tasks'])->findOrFail($id);
```

---

# Agent Teaching Behavior

Whenever you make a meaningful backend decision:

1. Identify the decision.
2. Explain the problem.
3. List reasonable alternatives.
4. Explain trade-offs.
5. Select an approach.
6. Record it in an ADR.
7. Implement it.
8. Point me to the relevant files.
9. Explain how I can verify it.
10. Give me a small experiment that demonstrates why the decision matters.

Do not hide implementation decisions behind generated code.

---

# Do Not Over-Engineer

This is a learning project.

Avoid:

- microservices
- Kubernetes
- event sourcing
- CQRS
- unnecessary repositories
- unnecessary interfaces
- unnecessary design patterns
- excessive abstractions
- complex frontend frameworks

We will introduce these concepts in later projects when they are actually useful.

The first project should be a well-structured Laravel monolith.

---

# UI Philosophy

The UI is NOT the learning target.

Use a simple application layout:

```text
------------------------------------------------
| Logo | Dashboard | Projects | Tasks | Logout |
------------------------------------------------

                  Page Content

------------------------------------------------
```

Use basic:

- forms
- tables
- buttons
- badges
- alerts
- pagination
- confirmation dialogs

No need for:

- animations
- sophisticated design system
- complex JavaScript
- SPA architecture

Every UI element should exist because it helps me verify backend behavior.

---

# Docker / Local Setup

Provide an easy setup.

Document:

```text
1. Clone repository
2. Install dependencies
3. Configure .env
4. Start PostgreSQL
5. Run migrations
6. Run seeders
7. Start Laravel
8. Open browser
```

Use my existing native PostgreSQL 16 instance by default. Do not launch a PostgreSQL Docker container or alter the system PostgreSQL service. If you recommend Docker for application reproducibility, explain why first and keep it optional; document container/image/volume/network/environment variables/port mapping only if Docker is actually added and I approve it.

---

# Final Project Structure

Aim for a conventional Laravel structure:

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Models/
├── Policies/
├── Services/
└── ...

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── dashboard/
│   ├── projects/
│   ├── tasks/
│   └── components/
└── css/

routes/
├── web.php
└── ...

tests/
├── Feature/
└── Unit/

docs/
└── ...

docker/
└── ...
```

Use Laravel conventions rather than forcing this exact structure if the selected Laravel version has a better conventional approach.

---

# Final Deliverables

At the end provide:

1. Complete Laravel application.
2. Blade UI.
3. PostgreSQL database.
4. Migrations.
5. Factories.
6. Seeders.
7. Authentication.
8. Authorization/policies.
9. Eloquent relationships.
10. Validation.
11. Transactions.
12. Pagination.
13. Filtering.
14. Sorting.
15. Activity logging.
16. Tests.
17. Docker/local development setup.
18. Complete documentation.
19. ADRs.
20. Manual verification guide.
21. Failure experiments.
22. Backend learning questions.
23. Backend concepts checklist.
24. Recommended concepts to study before Project 2.

---

# Final Learning Report

When the project is complete, generate:

`docs/FINAL-LEARNING-REPORT.md`

It should contain:

## Concepts Learned

```text
[ ] HTTP
[ ] REST
[ ] Routing
[ ] Middleware
[ ] Controllers
[ ] Dependency Injection
[ ] Request Validation
[ ] Authentication
[ ] Sessions
[ ] Authorization
[ ] Policies
[ ] Eloquent
[ ] Relationships
[ ] SQL
[ ] PostgreSQL
[ ] Indexes
[ ] Transactions
[ ] Pagination
[ ] Error Handling
[ ] Logging
[ ] Testing
[ ] Docker
```

For every item explain:

- where it appears in the project
- relevant files
- what I should understand
- one common mistake
- one interview-style question

---

# Final Challenge

After everything is implemented, give me a backend assessment.

Do NOT give me the answers immediately.

Ask me approximately 20 questions based on this exact codebase.

Questions should include:

- request lifecycle
- database relationships
- Eloquent
- authorization
- transactions
- SQL
- performance
- security
- testing
- debugging

Include several scenario questions.

For example:

> A user reports that opening the project page causes 150 SQL queries. Where would you investigate and why?

> Two users modify the same task at almost exactly the same time. What problems could occur?

> A user manually changes `/projects/10` to `/projects/11` in the URL. What prevents unauthorized access?

> The application works locally but becomes slow when there are 100,000 tasks. What would you investigate first?

Do not provide the answers until I ask for them.

---

# Most Important Objective

At the end of this project I should be able to look at a Laravel backend and understand:

```text
Request
   ↓
Route
   ↓
Middleware
   ↓
Authentication
   ↓
Authorization
   ↓
Controller
   ↓
Validation
   ↓
Business Logic
   ↓
Eloquent
   ↓
SQL
   ↓
PostgreSQL
   ↓
Response
   ↓
Blade
   ↓
Browser
```

I should be able to explain not only **what the code does**, but also:

**WHY it was implemented this way, WHAT alternatives existed, WHAT can go wrong, and HOW I would verify/fix it.**

Generate the complete working project and documentation accordingly.