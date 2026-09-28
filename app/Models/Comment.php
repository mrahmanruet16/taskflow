<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'user_id', 'body'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Nullable — a comment's author account may have been deleted (see
     * the migration's nullOnDelete). Blade must null-check this:
     * `$comment->user?->name ?? 'Deleted user'`.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
