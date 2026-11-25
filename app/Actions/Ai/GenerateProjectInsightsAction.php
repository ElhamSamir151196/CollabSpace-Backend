<?php

namespace App\Actions\Ai;

use App\DTOs\Ai\ChatMessage;
use App\Enums\AiModule;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPrompts;
use Illuminate\Support\Facades\Cache;

/**
 * "AI Insights" on the project overview: summary, health, highlights,
 * risks and recommendations. Cached per project (config ai.insights_cache_minutes);
 * $refresh forces a new analysis.
 */
class GenerateProjectInsightsAction
{
    public function __construct(
        private readonly AiGateway $ai,
        private readonly AiContextBuilder $context,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Project $project, User $user, bool $refresh = false): array
    {
        $this->ai->ensureEnabled(AiModule::Projects);

        $key = "ai.insights.project.{$project->id}";

        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes((int) config('ai.insights_cache_minutes')), function () use ($project, $user) {
            $response = $this->ai->generate(
                $user,
                'project_insights',
                [
                    AiPrompts::projectInsights(),
                    AiPrompts::workspaceData($this->context->forProject($project)),
                    ChatMessage::user('Analyse this project and give your insights.'),
                ],
                AiPrompts::projectInsightsSchema(),
                AiModule::Projects,
            );

            $data = $response->structured ?? [];
            $list = fn (string $key) => collect(is_array($data[$key] ?? null) ? $data[$key] : [])
                ->filter(fn ($value) => is_scalar($value))->map(fn ($value) => (string) $value)->values()->all();

            return [
                'summary' => (string) ($data['summary'] ?? ''),
                'health' => in_array($data['health'] ?? null, ['on_track', 'at_risk', 'off_track'], true) ? $data['health'] : 'at_risk',
                'highlights' => $list('highlights'),
                'risks' => $list('risks'),
                'recommendations' => $list('recommendations'),
                'provider' => $response->provider,
                'model' => $response->model,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }
}
