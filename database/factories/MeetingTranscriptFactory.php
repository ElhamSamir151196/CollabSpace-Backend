<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingTranscript;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingTranscript>
 */
class MeetingTranscriptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'speaker_name' => fake()->name(),
            'offset_seconds' => fake()->numberBetween(0, 1800),
            'content' => fake()->sentence(12),
            'language' => 'en',
            'position' => 0,
        ];
    }
}
