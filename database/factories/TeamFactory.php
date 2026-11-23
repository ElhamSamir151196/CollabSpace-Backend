<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['UI/UX', 'Frontend', 'Backend', 'QA', 'DevOps', 'Marketing'])
                .' '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
        ];
    }

    /**
     * Adds $count members; the first one is the leader.
     */
    public function withMembers(int $count = 3): static
    {
        return $this->afterCreating(function (Team $team) use ($count) {
            User::factory()->count($count)->create()->each(
                fn (User $user, int $index) => $team->members()->attach($user->id, [
                    'position' => fake()->jobTitle(),
                    'is_leader' => $index === 0,
                ]),
            );
        });
    }
}
