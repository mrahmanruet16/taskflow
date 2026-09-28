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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('planned');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();

            // restrict (not cascade): deleting a user should not silently
            // delete every project they ever created. If user deletion is
            // ever implemented, project ownership must be explicitly
            // reassigned or the projects explicitly archived first — the
            // database enforces that this can't be skipped by accident.
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            // Every dashboard/list query filters "projects I created" or
            // "projects I'm a member of" (the latter added when project_user
            // exists) — created_by is queried on nearly every page load of
            // this app, so it earns an index rather than relying on a full
            // table scan as project count grows.
            $table->index('created_by');

            // The dashboard/project-list will filter/group by status
            // (spec: "Pending Tasks / Completed Tasks" style aggregations,
            // and later /projects?status=active-style filtering) — indexed
            // for the same reason as created_by.
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
