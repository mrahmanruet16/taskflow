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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // cascadeOnDelete: a task has no meaning outside its project —
            // unlike projects.created_by (restrict) or project_user
            // (cascade on the membership pairing), a task belongs entirely
            // to one project, so deleting the project should delete its
            // tasks. This mirrors real Jira/Trello behavior: you can't
            // "orphan" a task by deleting its parent project.
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // nullable + nullOnDelete: a task can exist unassigned (spec:
            // "Assign task" is a distinct action from "Create task"), and
            // if the assignee's account is later deleted, the task should
            // revert to unassigned rather than being deleted itself or
            // blocking account deletion.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // restrict (matches projects.created_by): a task's creator is
            // a permanent historical fact, not something that should
            // silently vanish if that account is later deleted.
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->date('due_date')->nullable();

            $table->timestamps();

            // Every task list is scoped to a project (the project detail
            // page's task table) — without this, that query full-scans
            // tasks as the table grows across all projects.
            $table->index('project_id');

            // The spec's required filtering — /tasks?status=&priority=&
            // assignee= — queries directly against these three columns.
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
