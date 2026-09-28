<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Any authenticated user may reach the task list/index — the query
     * itself is scoped to tasks in projects they're a member of (see
     * TaskController::index), same pattern as ProjectPolicy::viewAny.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any project member (any role, including Viewer) may view a task —
     * same visibility rule as the project itself.
     */
    public function view(User $user, Task $task): bool
    {
        return $task->project->hasMember($user);
    }

    /**
     * Create takes a Project (not a Task — none exists yet), via
     * Gate::authorize('create', [Task::class, $project]). Viewer is
     * explicitly excluded: a Viewer can see everything but shouldn't be
     * able to add new work items.
     */
    public function create(User $user, Project $project): bool
    {
        return in_array($project->roleOf($user), [ProjectRole::Owner, ProjectRole::Manager, ProjectRole::Member], true);
    }

    /**
     * Owner/Manager can update any task in their project. The assignee
     * can ALSO update their own task (e.g. change its status as they work
     * on it) even without a management role — this is the concrete
     * expression of the spec's "Change status" action being available to
     * whoever the task is actually assigned to, not just project leads.
     */
    public function update(User $user, Task $task): bool
    {
        return $task->project->roleOf($user)?->canManageProject() === true
            || $task->assigned_to === $user->id;
    }

    /**
     * Deletion, unlike update, is NOT extended to the assignee — only
     * Owner/Manager can delete a task. An assignee finishing/cancelling
     * their own work should change its status, not remove the record.
     */
    public function delete(User $user, Task $task): bool
    {
        return $task->project->roleOf($user)?->canManageProject() === true;
    }
}
