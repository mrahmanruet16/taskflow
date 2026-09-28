<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_member_can_add_a_comment(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('comments.store', $task), [
            'body' => 'This looks good to merge.',
        ]);

        $response->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'body' => 'This looks good to merge.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_viewer_cannot_add_a_comment(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($viewer->id, ['role' => ProjectRole::Viewer->value]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($viewer)->post(route('comments.store', $task), [
            'body' => 'Should not be allowed.',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('comments', ['body' => 'Should not be allowed.']);
    }

    public function test_non_member_cannot_add_a_comment(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->post(route('comments.store', $task), [
            'body' => 'Should not be allowed.',
        ]);

        $response->assertStatus(403);
    }

    public function test_comment_requires_a_body(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('comments.store', $task), ['body' => '']);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_author_can_edit_their_own_comment(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $owner->id, 'body' => 'Original']);

        $response = $this->actingAs($owner)->put(route('comments.update', $comment), ['body' => 'Edited']);

        $response->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Edited']);
    }

    public function test_non_author_cannot_edit_the_comment_even_if_they_are_the_project_owner(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $member->id, 'body' => 'Original']);

        // Even though $owner has the Owner role on the project (which
        // grants them full control over the TASK itself), CommentPolicy
        // deliberately does NOT extend that to other people's comments —
        // this is the concrete test proving that divergence actually
        // holds, not just documented in a comment.
        $response = $this->actingAs($owner)->put(route('comments.update', $comment), ['body' => 'Hijacked']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Original']);
    }

    public function test_author_can_delete_their_own_comment(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $owner->id]);

        $response = $this->actingAs($owner)->delete(route('comments.destroy', $comment));

        $response->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_project_owner_cannot_delete_another_users_comment(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $member->id]);

        $response = $this->actingAs($owner)->delete(route('comments.destroy', $comment));

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_comments_appear_on_the_task_show_page(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);
        Comment::factory()->create(['task_id' => $task->id, 'user_id' => $owner->id, 'body' => 'Visible comment text']);

        $response = $this->actingAs($owner)->get(route('tasks.show', $task));

        $response->assertSee('Visible comment text');
    }
}
