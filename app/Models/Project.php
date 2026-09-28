<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'description', 'status', 'start_date', 'due_date', 'created_by'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    /**
     * The user who created this project (a fixed, permanent fact —
     * separate from members(), which can gain/lose people over time).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * belongsToMany resolves through the project_user pivot table
     * (Laravel infers "project_user" from the two model names, sorted
     * alphabetically — no explicit table name needed here). withPivot
     * exposes the pivot row's `role` column as $user->pivot->role on
     * each member returned; without it, the role would be silently
     * dropped from the query results even though it's stored.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    /**
     * Convenience check used by ProjectPolicy — "does this user have ANY
     * membership row for this project" (role-specific checks build on
     * top of this, see hasRole()).
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Returns the user's role on this project, or null if they aren't a
     * member at all. A single indexed query (project_id+user_id is
     * covered by the project_user unique constraint) — not N+1-prone
     * because Policy checks operate on one already-loaded Project
     * instance, not a collection.
     */
    public function roleOf(User $user): ?ProjectRole
    {
        $pivot = $this->members()->where('user_id', $user->id)->first()?->pivot;

        return $pivot ? ProjectRole::from($pivot->role) : null;
    }
}
