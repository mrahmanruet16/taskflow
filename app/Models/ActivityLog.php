<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'subject_type', 'subject_id', 'description'])]
class ActivityLog extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The arbitrary model this log entry is about (a Project today; a
     * Task or Comment once those phases exist). MorphTo resolves which
     * model to load using the subject_type column's stored class name.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
