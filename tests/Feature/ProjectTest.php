<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_project_index(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
    }

    public function test_project_index_only_shows_own_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownProject = Project::factory()->create(['created_by' => $user->id, 'name' => 'My Project']);
        Project::factory()->create(['created_by' => $otherUser->id, 'name' => 'Someone Elses Project']);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertSee('My Project');
        $response->assertDontSee('Someone Elses Project');
    }

    public function test_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'New Project',
            'description' => 'A test project',
            'status' => ProjectStatus::Planned->value,
            'start_date' => '2026-01-01',
            'due_date' => '2026-06-01',
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'created_by' => $user->id,
            'status' => 'planned',
        ]);

        $project = Project::where('name', 'New Project')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_project_creation_requires_a_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => '',
            'status' => ProjectStatus::Planned->value,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_due_date_cannot_be_before_start_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'Bad Dates',
            'status' => ProjectStatus::Planned->value,
            'start_date' => '2026-06-01',
            'due_date' => '2026-01-01',
        ]);

        $response->assertSessionHasErrors('due_date');
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_owner_can_view_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    public function test_non_owner_cannot_view_the_project(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->get(route('projects.show', $project));

        $response->assertStatus(403);
    }

    public function test_owner_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->put(route('projects.update', $project), [
            'name' => 'Renamed Project',
            'status' => ProjectStatus::Active->value,
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Renamed Project',
            'status' => 'active',
        ]);
    }

    public function test_non_owner_cannot_update_the_project(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'name' => 'Original Name']);

        $response = $this->actingAs($stranger)->put(route('projects.update', $project), [
            'name' => 'Hijacked Name',
            'status' => ProjectStatus::Active->value,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Original Name']);
    }

    public function test_owner_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_non_owner_cannot_delete_the_project(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->delete(route('projects.destroy', $project));

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }
}
