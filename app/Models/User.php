<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Projects this user created. Distinct from projects() below —
     * creating a project and being a *member* of one are different facts;
     * the creator is always also added as a member with the Owner role
     * (see ProjectController::store), but the two relations answer
     * different questions and can diverge (e.g. an owner could
     * theoretically be removed as a member while created_by stays fixed).
     */
    public function projectsCreated(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Projects this user is a MEMBER of (via project_user), regardless of
     * who created them. This is the relation that will back "which
     * projects can I see" once ProjectPolicy expands past its current
     * interim "creator only" rule.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * `tasksCreated`, `assignedTasks`, `comments` are added in their
     * respective phases (Tasks, Comments).
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
