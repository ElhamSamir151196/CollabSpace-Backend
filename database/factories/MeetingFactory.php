<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(2)->setTime(10, 0);

        return [
            'project_id' => Project::factory(),
            'title' => fake()->randomElement(['Design Review', 'Sprint Planning Meeting', 'UX Feedback Session', 'API Design Discussion']),
            'description' => 'The goal of this meeting is to align on next steps.',
            'start_at' => $start,
            'end_at' => (clone $start)->addMinutes(30),
            'meeting_link' => 'https://meet.example.com/'.fake()->lexify('???-????-???'),
            'status' => MeetingStatus::Scheduled,
        ];
    }

    public function at(string $start, int $minutes = 30): static
    {
        return $this->state(fn () => [
            'start_at' => $start,
            'end_at' => now()->parse($start)->addMinutes($minutes),
        ]);
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(20),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => MeetingStatus::Cancelled, 'cancelled_at' => now()]);
    }

    /**
     * @param  list<User>  $users
     */
    public function withParticipants(array $users): static
    {
        return $this->afterCreating(fn (Meeting $meeting) => $meeting->participants()->attach(
            collect($users)->mapWithKeys(fn (User $user) => [
                $user->id => ['attendance_status' => AttendanceStatus::Invited->value],
            ])->all(),
        ));
    }
}
