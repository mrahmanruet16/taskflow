<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_correct_aggregate_counts(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id, 'status' => TaskStatus::Todo, 'due_date' => null]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id, 'status' => TaskStatus::InProgress, 'due_date' => null]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id, 'status' => TaskStatus::Completed, 'due_date' => null]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id, 'status' => TaskStatus::Cancelled, 'due_date' => null]);
        // Overdue: due yesterday, not completed/cancelled.
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id, 'status' => TaskStatus::Todo, 'due_date' => now()->subDay()]);
        // NOT overdue despite a past due_date, because it's already completed.
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id, 'status' => TaskStatus::Completed, 'due_date' => now()->subDay()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $data = $response->viewData(null);
        // 6 tasks total, 2 completed, 3 pending (todo/todo/in_progress —
        // cancelled excluded from "pending"), 1 overdue (the completed
        // one with a past due_date must NOT count as overdue).
        $this->assertSame(1, $data['projectsCount']);
        $this->assertSame(6, $data['tasksCount']);
        $this->assertSame(2, $data['completedCount']);
        $this->assertSame(3, $data['pendingCount']);
        $this->assertSame(1, $data['overdueCount']);
    }

    public function test_dashboard_only_counts_tasks_from_member_projects(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $myProject = Project::factory()->create(['created_by' => $user->id]);
        $otherProject = Project::factory()->create(['created_by' => $stranger->id]);

        Task::factory()->create(['project_id' => $myProject->id, 'created_by' => $user->id]);
        Task::factory()->count(5)->create(['project_id' => $otherProject->id, 'created_by' => $stranger->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $data = $response->viewData(null);
        $this->assertSame(1, $data['projectsCount']);
        $this->assertSame(1, $data['tasksCount']);
    }
}
