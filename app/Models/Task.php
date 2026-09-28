<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['project_id', 'assigned_to', 'created_by', 'title', 'description', 'status', 'priority', 'due_date'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Nullable — a task can be unassigned. Blade/controller code must
     * null-check this (`$task->assignee?->name`), not assume it's always
     * present.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    /**
     * True whether the given task is overdue: has a due_date in the past
     * and isn't already completed/cancelled — used by the dashboard's
     * "Overdue Tasks" count and this doesn't belong as a raw inline
     * condition repeated in multiple places (dashboard query, task list
     * badge, etc).
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->status, [TaskStatus::Completed, TaskStatus::Cancelled], true);
    }
}
