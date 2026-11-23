<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['SaaS', 'Mobile App', 'Website', null]),
            'status' => ProjectStatus::InProgress,
            'priority' => fake()->randomElement(Priority::cases()),
            // Started already, deadline always in the future (use overdue() for late projects).
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'deadline' => fake()->dateTimeBetween('+1 month', '+4 months'),
            'manager_id' => User::factory()->withRole(UserRole::ProjectManager),
        ];
    }

    public function managedBy(User $manager): static
    {
        return $this->state(fn () => ['manager_id' => $manager->id]);
    }

    public function withStatus(ProjectStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'completed_at' => $status === ProjectStatus::Completed ? now() : null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::InProgress,
            'start_date' => now()->subMonths(2),
            'deadline' => now()->subWeek(),
        ]);
    }

    /**
     * @param  list<Team>  $teams
     */
    public function withTeams(array $teams): static
    {
        return $this->afterCreating(fn (Project $project) => $project->teams()->attach(
            array_map(fn (Team $team) => $team->id, $teams),
        ));
    }

    /**
     * @param  list<User>  $guests
     */
    public function withGuests(array $guests): static
    {
        return $this->afterCreating(fn (Project $project) => $project->guests()->attach(
            array_map(fn (User $user) => $user->id, $guests),
        ));
    }
}
