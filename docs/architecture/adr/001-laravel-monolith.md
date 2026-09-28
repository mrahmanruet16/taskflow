# 001 — Laravel Monolith, Not Microservices

# Decision

Build TaskFlow as a single Laravel application (one codebase, one deployable unit, one database), not as a set of independently deployable services.

# Context

TaskFlow is a project-management app with a handful of related entities (Users, Projects, Membership, Tasks, Comments, Activity Logs) that all reference each other heavily — a task belongs to a project, a comment belongs to a task, an activity log entry can point at any of them. This is being built as a solo backend-learning project, not a team product with independent release cadences per domain.

# Problem

How should the application's code and deployment boundaries be structured?

# Options Considered

## Option A — Microservices (e.g. separate Auth service, Projects service, Tasks service, each with its own database, communicating over HTTP/message queue)

Advantages:
- Independent scaling and deployment per service, if load patterns genuinely diverge.
- Forces strict boundaries between domains.
- Different services could theoretically use different tech stacks.

Disadvantages:
- Every cross-entity operation in this app (create a project → attach a membership row → write an activity log, all in one transaction — see ADR 008) becomes a distributed transaction problem the moment those entities live in different databases owned by different services. This app's core learning objective (teaching transactions, foreign keys, and Eloquent relationships) becomes dramatically harder to demonstrate cleanly across service boundaries.
- Massive operational overhead (service discovery, inter-service auth, network failure handling, distributed tracing) with zero corresponding benefit at this app's actual scale (a handful of users, described in the spec's own seed-data targets: "10+ users, 5+ projects, 50+ tasks, 100+ comments").
- Directly contradicts the project's own stated "Do Not Over-Engineer" principle, which explicitly lists microservices as something to avoid for this learning project.

## Option B — Single Laravel monolith

Advantages:
- One database means foreign keys, transactions, and Eloquent relationships all work exactly as taught in standard Laravel documentation — no distributed-systems complexity obscuring the actual backend concepts this project exists to teach.
- One deployable unit — `git push`, `php artisan migrate`, done. No orchestration layer needed.
- Laravel's own conventions (routes → middleware → controllers → Eloquent → Blade) are designed around this shape; fighting that shape to force a microservices split would work against the framework, not with it.

Disadvantages:
- All code shares one process/deployment — a bug in one area can't be isolated by deploying only that "service." Acceptable here: at this scale, isolation-by-deployment isn't a real requirement.
- Cannot scale different parts of the app independently (e.g. scale Task-heavy traffic separately from Auth traffic). Not a real constraint at this app's scale — a single Laravel app can be scaled horizontally (multiple instances behind a load balancer) if it ever needed to.

# Decision Made

Option B — a single Laravel monolith.

# Why

Every one of this app's core teaching objectives (request lifecycle, Eloquent relationships, transactions, foreign-key constraints, N+1 queries) is easiest to observe and explain correctly within one codebase talking to one database. A microservices split would add substantial complexity whose only payoff (independent scaling/deployment) doesn't correspond to any actual requirement this project has.

# Consequences

- All of TaskFlow's code lives in `app/`, `routes/`, `resources/views/` of one Laravel project — confirmed by the actual repo structure built across Phases 1–2.7.
- Every relationship documented in `docs/backend-concepts/database-relationships.md` (belongsTo, hasMany, belongsToMany, morphTo) works as a normal foreign-key-backed Eloquent relationship, not a cross-service API call.
- If this app ever needed to scale beyond a single deployable unit, the first step would be horizontal scaling (more instances of the same monolith behind a load balancer, shared database) — not a service split — since that's a much smaller change with the same throughput benefit for read-heavy workloads.

# When We Would Reconsider This Decision

If a genuinely distinct bounded context emerged with different scaling needs, a different team owning it, or a need for independent release cycles unrelated to the rest of the app (e.g. a billing/subscription system bolted onto TaskFlow later) — none of which applies to this learning project as scoped.
