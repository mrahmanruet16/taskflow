<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any authenticated user may see the project list — the list itself
     * (ProjectController::index) is scoped to the user's own projects via
     * the query, not via this gate. viewAny controls "can this user reach
     * the index page at all", not "which rows do they see".
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Interim rule until Project Membership exists: only the creator can
     * view a project. Once project_user exists, this becomes "creator OR
     * any row in project_user for this user+project" — see ADR 006 and the
     * TODO left in this method for the future session that implements it.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }

    /**
     * Any authenticated user may create a project (they become its owner).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Interim rule, same reasoning as view(): only the creator/owner can
     * update. Once roles exist (owner/manager/member/viewer), this expands
     * to "owner or manager", not just "creator".
     */
    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }

    /**
     * Deletion is intentionally more restrictive than update in most real
     * systems (a manager might edit a project but not delete it) — here,
     * until roles exist, both collapse to "creator only", but this method
     * is kept separate from update() so the Membership phase can diverge
     * the rules without touching update()'s logic.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->created_by;
    }
}
