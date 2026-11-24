<?php

namespace Database\Factories;

use App\Enums\MeetingNoteType;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingNote>
 */
class MeetingNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'user_id' => User::factory(),
            'type' => MeetingNoteType::Note,
            'body' => fake()->sentence(),
        ];
    }

    public function comment(): static
    {
        return $this->state(fn () => ['type' => MeetingNoteType::Comment]);
    }
}
