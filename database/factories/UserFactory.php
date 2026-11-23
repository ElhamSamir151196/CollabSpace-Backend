<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->numerify('+20 1## ### ####'),
            'job_title' => fake()->randomElement(['UI/UX Designer', 'Frontend Developer', 'Backend Developer', 'QA Engineer', 'Marketing Specialist']),
            'years_of_experience' => fake()->numberBetween(0, 15),
            'availability_status' => AvailabilityStatus::Available,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Account created with Google only (no password yet).
     */
    public function googleOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'google_id' => (string) fake()->unique()->randomNumber(9),
        ]);
    }

    public function withRole(UserRole $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles([$role->value]));
    }

    public function admin(): static
    {
        return $this->withRole(UserRole::Admin);
    }

    public function projectManager(): static
    {
        return $this->withRole(UserRole::ProjectManager);
    }

    public function teamMember(): static
    {
        return $this->withRole(UserRole::TeamMember);
    }
}
