<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // cascadeOnDelete: a comment has no meaning outside its task —
            // same reasoning as tasks.project_id.
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            // nullOnDelete (matches activity_logs.user_id, not
            // projects/tasks' restrictOnDelete on created_by): a comment's
            // content is worth keeping even if the author's account is
            // later deleted — "This comment's author no longer exists"
            // is an acceptable degraded state; silently deleting
            // discussion history, or blocking account deletion because
            // someone once left a comment, is not.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('body');

            $table->timestamps();

            // Every comment list is scoped to one task (the task show
            // page) — no other query pattern exists for comments in this
            // app, so no other index is justified.
            $table->index('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
