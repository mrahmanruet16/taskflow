<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo password for every named demo account below. Documented here
     * and in docs/SETUP.md — never a value real user accounts would use,
     * exists solely so this seed data is browsable without needing to
     * register first.
     */
    private const DEMO_PASSWORD = 'password';

    /**
     * Seed the application's database with the volume the spec requires
     * (10+ users, 5+ projects, 50+ tasks, 100+ comments) plus named demo
     * accounts covering all four project roles, so logging in as any one
     * of them immediately shows a populated, role-appropriate view.
     */
    public function run(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.test', 'password' => self::DEMO_PASSWORD]);
        $manager = User::factory()->create(['name' => 'Manager User', 'email' => 'manager@example.test', 'password' => self::DEMO_PASSWORD]);
        $member = User::factory()->create(['name' => 'Member User', 'email' => 'member@example.test', 'password' => self::DEMO_PASSWORD]);
        $viewer = User::factory()->create(['name' => 'Viewer User', 'email' => 'viewer@example.test', 'password' => self::DEMO_PASSWORD]);

        // 10 additional random users so the demo accounts aren't the only
        // possible assignees/commenters — a project with only 4 possible
        // members wouldn't exercise assignment/filtering realistically.
        $otherUsers = User::factory()->count(10)->create();

        // 6 projects (spec requires 5+). Admin owns most of them, so
        // logging in as admin@example.test immediately shows a populated
        // dashboard — the account most people will demo with first.
        $projects = collect(range(1, 6))->map(function (int $i) use ($admin, $manager, $member, $viewer, $otherUsers) {
            $creator = $i === 1 ? $manager : $admin; // one project owned by someone other than admin, for variety
            $project = Project::factory()->create(['created_by' => $creator->id]);

            // ProjectFactory's afterCreating hook already attached $creator
            // as Owner. Attach the other three named demo accounts with
            // their intended roles (skipping whichever one IS the
            // creator, already attached) so every demo account is a
            // member of every seeded project and can be used to explore
            // role-based authorization firsthand.
            foreach ([$admin, $manager, $member, $viewer] as $user) {
                if ($user->id === $creator->id) {
                    continue;
                }
                $role = match (true) {
                    $user->id === $manager->id => ProjectRole::Manager,
                    $user->id === $viewer->id => ProjectRole::Viewer,
                    default => ProjectRole::Member,
                };
                $project->members()->attach($user->id, ['role' => $role->value]);
            }

            // A couple of the random users too, as plain Members, so
            // task assignment/filtering has more than 4 possible people.
            foreach ($otherUsers->random(2) as $extra) {
                $project->members()->attach($extra->id, ['role' => ProjectRole::Member->value]);
            }

            ActivityLog::create([
                'user_id' => $creator->id,
                'subject_type' => Project::class,
                'subject_id' => $project->id,
                'description' => $creator->name.' created project "'.$project->name.'".',
            ]);

            return $project;
        });

        // ~10 tasks per project → 60+ total (spec requires 50+).
        foreach ($projects as $project) {
            $members = $project->members;

            foreach (range(1, 10) as $n) {
                $assignee = $members->random();
                $isOverdue = $n <= 2; // first 2 tasks per project are overdue, so the Dashboard's Overdue count is never zero across the whole seed set

                $task = Task::factory()->create([
                    'project_id' => $project->id,
                    'created_by' => $project->owner->id,
                    'assigned_to' => $assignee->id,
                    'status' => $isOverdue ? TaskStatus::Todo : fake()->randomElement(TaskStatus::cases()),
                    'due_date' => $isOverdue ? now()->subDays(fake()->numberBetween(1, 14)) : fake()->optional()->dateTimeBetween('now', '+2 months'),
                ]);

                ActivityLog::create([
                    'user_id' => $project->owner->id,
                    'subject_type' => Task::class,
                    'subject_id' => $task->id,
                    'description' => $project->owner->name.' created task "'.$task->title.'".',
                ]);

                // ~2 comments per task → 120+ total (spec requires 100+),
                // authored by a random member of the SAME project (a
                // comment from someone with no access to the project
                // would be a data-integrity issue this seeder shouldn't
                // introduce, even though nothing in the schema prevents
                // it directly — see docs/backend-concepts/validation.md
                // on why this specific check lives in application code,
                // not a database constraint).
                foreach (range(1, 2) as $__) {
                    Comment::factory()->create([
                        'task_id' => $task->id,
                        'user_id' => $members->random()->id,
                    ]);
                }
            }
        }
    }
}
