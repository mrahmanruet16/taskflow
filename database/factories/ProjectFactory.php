<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 months', '+1 month');

        return [
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => $start,
            'due_date' => fake()->dateTimeBetween($start, '+4 months'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Every real project gets its creator attached as Owner via the
     * transaction in ProjectController::store() (ADR 008) — a project
     * with no owner membership row is an invalid state the app never
     * actually produces. Factory-created projects mirror that invariant
     * here so tests exercise realistic data, not an app-can-never-reach
     * edge case.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Project $project) {
            if (! $project->hasMember($project->owner)) {
                $project->members()->attach($project->created_by, ['role' => ProjectRole::Owner->value]);
            }
        });
    }
}
