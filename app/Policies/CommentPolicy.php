<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    /**
     * Create takes a Task (not a Comment — none exists yet), via
     * Gate::authorize('create', [Comment::class, $task]). Viewer is
     * excluded, same reasoning as TaskPolicy::create — a read-only role
     * shouldn't be able to add content either.
     */
    public function create(User $user, Task $task): bool
    {
        return in_array($task->project->roleOf($user), [ProjectRole::Owner, ProjectRole::Manager, ProjectRole::Member], true);
    }

    /**
     * Deliberately DIFFERENT from TaskPolicy::update — the spec is
     * explicit that users edit "their own" comment, with no Owner/Manager
     * override. A project manager can reassign or reprioritize a task,
     * but editing someone else's words is treated differently: comment
     * ownership follows the individual, not the project hierarchy.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * Same author-only rule as update() — matches the spec's exact
     * wording ("delete their own comment"), not extended to
     * Owner/Manager the way TaskPolicy::delete is.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }
}
