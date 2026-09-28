<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_members_list(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('projects.members.index', $project));

        $response->assertStatus(200);
        $response->assertSee($owner->name);
    }

    public function test_non_member_cannot_view_members_list(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->get(route('projects.members.index', $project));

        $response->assertStatus(403);
    }

    public function test_owner_can_add_a_member(): void
    {
        $owner = User::factory()->create();
        $newMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('projects.members.store', $project), [
            'email' => $newMember->email,
            'role' => ProjectRole::Member->value,
        ]);

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $newMember->id,
            'role' => 'member',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
    }

    public function test_member_cannot_add_another_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $newMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);

        $response = $this->actingAs($member)->post(route('projects.members.store', $project), [
            'email' => $newMember->email,
            'role' => ProjectRole::Viewer->value,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $newMember->id]);
    }

    public function test_cannot_add_a_user_who_is_already_a_member(): void
    {
        $owner = User::factory()->create();
        $alreadyMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($alreadyMember->id, ['role' => ProjectRole::Viewer->value]);

        $response = $this->actingAs($owner)->post(route('projects.members.store', $project), [
            'email' => $alreadyMember->email,
            'role' => ProjectRole::Member->value,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_owner_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);

        $response = $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $member]));

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $member->id]);
    }

    public function test_cannot_remove_the_last_owner(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->delete(route('projects.members.destroy', [$project, $owner]));

        $response->assertSessionHasErrors('member');
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $owner->id]);
    }

    public function test_manager_role_can_update_project_but_member_role_cannot(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'name' => 'Original']);
        $project->members()->attach($manager->id, ['role' => ProjectRole::Manager->value]);
        $project->members()->attach($member->id, ['role' => ProjectRole::Member->value]);

        $managerResponse = $this->actingAs($manager)->put(route('projects.update', $project), [
            'name' => 'Renamed by Manager',
            'status' => $project->status->value,
        ]);
        $managerResponse->assertRedirect(route('projects.show', $project));

        $memberResponse = $this->actingAs($member)->put(route('projects.update', $project), [
            'name' => 'Renamed by Member',
            'status' => $project->status->value,
        ]);
        $memberResponse->assertStatus(403);
    }

    public function test_only_owner_not_manager_can_delete_project(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);
        $project->members()->attach($manager->id, ['role' => ProjectRole::Manager->value]);

        $response = $this->actingAs($manager)->delete(route('projects.destroy', $project));

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_creating_a_project_creates_membership_and_activity_log_atomically(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/projects', [
            'name' => 'Atomic Test Project',
            'status' => 'planned',
        ]);

        $project = Project::where('name', 'Atomic Test Project')->firstOrFail();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);
    }
}
