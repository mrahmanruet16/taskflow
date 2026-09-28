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
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();

            // cascadeOnDelete here (unlike projects.created_by's
            // restrictOnDelete): a membership row has no meaning once
            // either side of it is gone — deleting a project should delete
            // its membership rows, and deleting a user should remove them
            // from projects they belonged to. This is the opposite
            // ownership-safety trade-off from the projects table on
            // purpose: created_by is "who is responsible for this,"
            // membership is just "is this pairing still valid."
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');

            $table->timestamps();

            // A user can only have ONE role per project — this is what
            // makes "attach as owner" safe to call without first checking
            // whether a row already exists; a duplicate attach attempt
            // fails loudly at the database level instead of silently
            // creating two conflicting membership rows for the same pair.
            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
