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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // nullOnDelete (not cascade, not restrict): an activity log is
            // an audit trail — "John changed Task #15's status" should
            // remain readable/queryable even if John's account is later
            // deleted. Losing the *name* of who did it if the account is
            // gone is an acceptable trade-off; silently deleting the
            // historical record, or blocking user deletion entirely just
            // because they once did something loggable, is not.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Polymorphic subject: one activity_logs table serves Project,
            // Task, and Comment events (per spec) instead of a separate
            // log table per model — subject_type stores the model class,
            // subject_id its primary key. This is the standard Eloquent
            // "morph" pattern for "this log entry is about some other
            // arbitrary model." nullableMorphs() already creates the
            // (subject_type, subject_id) index needed for "activity for
            // this project" queries — no separate index() call needed.
            $table->nullableMorphs('subject');

            $table->text('description');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
