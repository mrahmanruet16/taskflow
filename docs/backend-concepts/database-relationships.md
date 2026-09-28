# Database Relationships (Eloquent)

## 1. What is it?

Eloquent relationships are methods on a model that describe how it connects to other models/tables — `belongsTo`, `hasMany`, `belongsToMany`, `morphTo`/`morphMany` — and let you traverse those connections with plain PHP (`$project->owner`, `$project->members`) instead of hand-writing SQL joins every time.

## 2. What problem does it solve?

Without relationships, every "get the owner of this project" or "get all members of this project" would mean writing a raw `JOIN` query by hand, every single place it's needed. Relationships define the join logic once, on the model, and reuse it everywhere via a readable method call.

## 3. How does it work? — the four relationship types used in this app

### `belongsTo` — `Project::owner()`
```php
public function owner(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}
```
The `projects` table holds the foreign key (`created_by`). `$project->owner` runs roughly `SELECT * FROM users WHERE id = ?` using the project's `created_by` value. This is a "many projects point to one user" relationship — the FK lives on the "many" side.

### `hasMany` — `User::projectsCreated()`
```php
public function projectsCreated(): HasMany
{
    return $this->hasMany(Project::class, 'created_by');
}
```
The inverse of `belongsTo`: `$user->projectsCreated` runs `SELECT * FROM projects WHERE created_by = ?`.

### `belongsToMany` — `Project::members()` / `User::projects()` (the many-to-many)
```php
// Project.php
public function members(): BelongsToMany
{
    return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
}

// User.php
public function projects(): BelongsToMany
{
    return $this->belongsToMany(Project::class)->withPivot('role')->withTimestamps();
}
```
A project can have many members; a user can belong to many projects. Neither `projects` nor `users` can hold this directly (a single foreign key column can only point to *one* row) — a many-to-many relationship needs a **pivot table** in between. Laravel infers the pivot table name (`project_user` — the two model names, singular, alphabetically sorted, joined with `_`) unless told otherwise. `$project->members` runs approximately:
```sql
SELECT users.*, project_user.role, project_user.created_at, project_user.updated_at
FROM users
INNER JOIN project_user ON project_user.user_id = users.id
WHERE project_user.project_id = ?
```
`withPivot('role')` is what makes `project_user.role` show up on each returned `User` as `$member->pivot->role` — without it, the role would silently be missing from the query results even though it's stored in the table. `attach()`/`detach()` insert/delete pivot rows (`$project->members()->attach($user->id, ['role' => 'owner'])`); Eloquent never lets you `save()` a pivot row directly through the relationship the way you would a normal model.

### `morphTo` / `morphMany` — `ActivityLog::subject()` / `Project::activities()`
```php
// ActivityLog.php
public function subject(): MorphTo
{
    return $this->morphTo();
}

// Project.php
public function activities(): MorphMany
{
    return $this->morphMany(ActivityLog::class, 'subject')->latest();
}
```
`activity_logs` needs to reference *different kinds* of models (a Project today; a Task or Comment once those phases exist) from one table, without a separate `project_id`/`task_id`/`comment_id` column for every possible type. The polymorphic pattern stores two columns instead — `subject_type` (the model's class name, e.g. `App\Models\Project`) and `subject_id` (its primary key) — and `morphTo()` uses both together to know which table to actually join against at query time.

## 4. Where is it used in this project?

- `app/Models/Project.php` — `owner()`, `members()`, `activities()`, plus helper methods `hasMember()`/`roleOf()` built on top of `members()`
- `app/Models/User.php` — `projectsCreated()`, `projects()`, `activities()`
- `app/Models/ActivityLog.php` — `user()`, `subject()`
- `database/migrations/..._create_project_user_table.php` — the pivot table itself, with a `role` column and a `unique(['project_id', 'user_id'])` constraint (a user can only have one role per project)

## 5. What happens without it?

Without `withPivot('role')` on `members()`: `$project->members->first()->pivot->role` would throw or return `null` even though the database row genuinely has a role stored — a subtle bug where the *data* is correct but the *relationship definition* silently drops a column from the results, easy to miss until a Blade template tries to render a role and gets nothing.

Without the `unique(['project_id', 'user_id'])` database constraint: calling `attach()` twice for the same user+project pair would silently create two rows instead of failing — a user could end up with two conflicting roles on the same project, and `roleOf()`'s `->first()` would non-deterministically return whichever row the database happened to return first.

## 6. Alternatives

- **Separate `task_activity_logs` / `comment_activity_logs` tables** instead of one polymorphic `activity_logs` table — avoids the polymorphic pattern's slight query-planner overhead (an extra `WHERE subject_type = ?` on every query) but means three near-identical tables and three near-identical models instead of one, and no single "show me everything this user did across the whole app" query without a `UNION`.
- **JSON column for pivot data** instead of a real pivot table with a typed `role` column — avoids a migration for the pivot's extra columns, but loses the database's own type checking, indexing, and the ability to query/filter by role efficiently (`WHERE role = 'owner'` on a real column vs. a JSON path expression).

## 7. Trade-offs

`belongsToMany` queries always involve a `JOIN`, which is marginally more expensive than a plain `belongsTo`/`hasMany` lookup — negligible at this app's scale, worth knowing about at very large scale. Polymorphic relationships (`morphTo`) can't use a traditional foreign key constraint at the database level the same way a normal `belongsTo` can (there's no single table for `subject_id` to reference, since it varies), so referential integrity for `subject_id` is enforced by convention/application code, not the database schema — a deliberate trade-off in exchange for one shared log table.

## 8. Production considerations

`$project->members` and `$project->activities` should always be eager-loaded (`Project::with(['members', 'activities'])`) when a page needs both, exactly like `owner` — see `docs/backend-concepts/eloquent.md` (added once Tasks exist for a fuller N+1 demonstration). `ProjectController::show()` already does this (`$project->load(['members', 'activities.user'])`).

## 9. How do I verify it?

```bash
php artisan test --filter=ProjectMemberTest
```
Or manually: create a project, visit `/projects/{id}/members`, add a colleague by email with a role, confirm they appear with the correct role badge; remove them and confirm they disappear. Database-level: `SELECT * FROM project_user WHERE project_id = ?` should show exactly the rows the UI reflects.

## 10. Questions for me

1. Why does `project_user` need its OWN `id` primary key column (via `$table->id()`) when `belongsToMany` relationships often work fine with just a composite key of `(project_id, user_id)`? What would you lose by removing it?
2. `Project::activities()` calls `->latest()` on the relationship definition itself, not at the call site. What's the practical effect of putting `->latest()` there instead of writing `$project->activities()->latest()->get()` every time you need it?
3. If you called `$project->members()->attach($user->id, ['role' => 'owner'])` twice in a row for the same user, what would actually happen, and where exactly (database or application code) does that behavior get decided?
4. `ActivityLog::subject_type` stores a full PHP class name (`App\Models\Project`) by default. What would happen to existing activity log rows if that class were later renamed to `App\Models\ProjectRecord`? Is this a real risk in this app, and what's the standard Laravel mechanism (hint: "morph map") to avoid it?
