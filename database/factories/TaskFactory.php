<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Use ->for($project) and ->forTeam($team): the team should be one of the project's teams.
 *
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'team_id' => Team::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'status' => TaskStatus::Pending,
            'priority' => fake()->randomElement(Priority::cases()),
            'progress' => 0,
            'start_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addWeeks(2)->toDateString(),
            'position' => 0,
        ];
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn () => ['team_id' => $team->id]);
    }

    public function withStatus(TaskStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'progress' => $status === TaskStatus::Completed ? 100 : 0,
            'completed_at' => $status === TaskStatus::Completed ? now() : null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::InProgress,
            'start_date' => now()->subMonth()->toDateString(),
            'due_date' => now()->subWeek()->toDateString(),
        ]);
    }

    /**
     * @param  list<User>  $users
     */
    public function assignedTo(array $users): static
    {
        return $this->afterCreating(fn (Task $task) => $task->assignees()->attach(
            array_map(fn (User $user) => $user->id, $users),
        ));
    }
}
