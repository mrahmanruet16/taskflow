<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any authenticated user may see the project list — the list itself
     * (ProjectController::index) is scoped to the user's memberships via
     * the query, not via this gate. viewAny controls "can this user reach
     * the index page at all", not "which rows do they see".
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any member (any role, including Viewer) may view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->hasMember($user);
    }

    /**
     * Any authenticated user may create a project (they become its Owner
     * — see the transaction in ProjectController::store()).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only Owner/Manager may rename/reschedule/change status — a Member
     * or Viewer can be part of a project without controlling its
     * metadata. Delegates the actual role-comparison logic to
     * ProjectRole::canManageProject() so this rule and the "can this role
     * manage a project" question stay defined in one place.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->roleOf($user)?->canManageProject() ?? false;
    }

    /**
     * Deletion is intentionally MORE restrictive than update: only the
     * Owner (not a Manager) may delete a project. A Manager can edit
     * details and manage members, but destroying the project entirely is
     * reserved for whoever holds ultimate responsibility for it.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->roleOf($user) === ProjectRole::Owner;
    }

    /**
     * Members list is visible to any member — same visibility as the
     * project itself.
     */
    public function viewMembers(User $user, Project $project): bool
    {
        return $project->hasMember($user);
    }

    /**
     * Only Owner/Manager may add or remove members — a Member/Viewer
     * should not be able to grant themselves or others more access.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $project->roleOf($user)?->canManageProject() ?? false;
    }
}
