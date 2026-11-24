<?php

namespace Database\Factories;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Use ->direct($a, $b), ->forProject($project) or ->forTeam($project, $team).
 *
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ConversationType::Project,
            'project_id' => Project::factory(),
            'name' => 'Public Channel',
        ];
    }

    public function direct(User $first, User $second): static
    {
        return $this->state(fn () => [
            'type' => ConversationType::Direct,
            'project_id' => null,
            'name' => null,
            'direct_key' => Conversation::directKey($first->id, $second->id),
        ])->afterCreating(fn (Conversation $conversation) => $conversation->participants()->attach([$first->id, $second->id]));
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn () => [
            'type' => ConversationType::Project,
            'project_id' => $project->id,
            'team_id' => null,
            'name' => 'Public Channel',
        ]);
    }

    public function forTeam(Project $project, Team $team): static
    {
        return $this->state(fn () => [
            'type' => ConversationType::Team,
            'project_id' => $project->id,
            'team_id' => $team->id,
            'name' => "{$team->name} Team Channel",
        ]);
    }
}
