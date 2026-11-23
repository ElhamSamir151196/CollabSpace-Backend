<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Metadata only (no real file on disk). Use ->forProject() or ->forTask().
 *
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'png', 'docx', 'sql', 'jsx']);

        return [
            'original_name' => Str::slug(fake()->words(3, true), '_').'.'.$extension,
            'disk' => 'local',
            'path' => 'attachments/'.Str::uuid().'.'.$extension,
            'mime_type' => 'application/octet-stream',
            'extension' => $extension,
            'size' => fake()->numberBetween(10_000, 5_000_000),
            'uploaded_by' => User::factory(),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn () => [
            'attachable_type' => Project::class,
            'attachable_id' => $project->id,
            'project_id' => $project->id,
        ]);
    }

    public function forTask(Task $task): static
    {
        return $this->state(fn () => [
            'attachable_type' => Task::class,
            'attachable_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }
}
