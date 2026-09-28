<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_task_index(): void
    {
        $response = $this->get('/tasks');

        $response->assertRedirect('/login');
    }

    public function test_project_member_can_create_a_task(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('tasks.store', $project), [
            'title' => 'Write ADR',
            'description' => 'Document the decision',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'due_date' => '2026-12-01',
        ]);

        $task = Task::where('title', 'Write ADR')->firstOrFail();
        $response->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Write ADR',
            'project_id' => $project->id,
            'created_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_viewer_cannot_create_a_task(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($viewer->id, ['role' => ProjectRole::Viewer->value]);

        $response = $this->actingAs($viewer)->post(route('tasks.store', $project), [
            'title' => 'Should not exist',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Low->value,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['title' => 'Should not exist']);
    }

    public function test_non_member_cannot_create_a_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->post(route('tasks.store', $project), [
            'title' => 'Should not exist',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Low->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_task_cannot_be_assigned_to_a_non_member(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('tasks.store', $project), [
            'title' => 'Bad assignment',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::Low->value,
            'assigned_to' => $outsider->id,
        ]);

        $response->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseMissing('tasks', ['title' => 'Bad assignment']);
    }

    public function test_project_member_can_view_a_task(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('tasks.show', $task));

        $response->assertStatus(200);
        $response->assertSee($task->title);
    }

    public function test_non_member_cannot_view_a_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->get(route('tasks.show', $task));

        $response->assertStatus(403);
    }

    public function test_assignee_can_change_task_status_without_a_management_role(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $member->id,
            'status' => TaskStatus::Todo,
        ]);

        $response = $this->actingAs($member)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'priority' => $task->priority->value,
            'status' => TaskStatus::Completed->value,
        ]);

        $response->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'completed']);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $task->id,
        ]);
    }

    public function test_member_who_is_not_the_assignee_cannot_update_the_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $otherMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);
        $project->members()->attach($otherMember->id, ['role' => ProjectRole::Member->value]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $member->id,
        ]);

        $response = $this->actingAs($otherMember)->put(route('tasks.update', $task), [
            'title' => 'Hijacked',
            'priority' => $task->priority->value,
            'status' => $task->status->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_only_manager_or_owner_can_delete_a_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'assigned_to' => $member->id,
        ]);

        $memberAttempt = $this->actingAs($member)->delete(route('tasks.destroy', $task));
        $memberAttempt->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);

        $ownerAttempt = $this->actingAs($owner)->delete(route('tasks.destroy', $task));
        $ownerAttempt->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_index_filters_by_status(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'title' => 'Todo Task', 'status' => TaskStatus::Todo]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'title' => 'Done Task', 'status' => TaskStatus::Completed]);

        $response = $this->actingAs($owner)->get('/tasks?status=completed');

        $response->assertSee('Done Task');
        $response->assertDontSee('Todo Task');
    }

    public function test_task_index_only_shows_tasks_from_member_projects(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $myProject = Project::factory()->create(['created_by' => $owner->id]);
        $otherProject = Project::factory()->create(['created_by' => $stranger->id]);
        Task::factory()->create(['project_id' => $myProject->id, 'created_by' => $owner->id, 'title' => 'My Task']);
        Task::factory()->create(['project_id' => $otherProject->id, 'created_by' => $stranger->id, 'title' => 'Their Task']);

        $response = $this->actingAs($owner)->get('/tasks');

        $response->assertSee('My Task');
        $response->assertDontSee('Their Task');
    }
}
