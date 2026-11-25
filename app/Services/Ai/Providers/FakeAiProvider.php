<?php

namespace App\Services\Ai\Providers;

use App\DTOs\Ai\AiResponse;
use App\DTOs\Ai\ChatMessage;
use App\Interfaces\Ai\AiProvider;
use Illuminate\Support\Str;

/**
 * Answers instantly without calling any API: for development (before the
 * real key is set) and for tests. Answers are predictable on purpose.
 */
class FakeAiProvider implements AiProvider
{
    /**
     * @param  array{model?: string}  $config
     */
    public function __construct(private readonly array $config = []) {}

    public function name(): string
    {
        return 'fake';
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'fake-model');
    }

    public function generate(array $messages, ?array $jsonSchema = null): AiResponse
    {
        $question = (string) collect($messages)->filter(fn (ChatMessage $m) => $m->role === 'user')->pluck('content')->last();

        $structured = $jsonSchema !== null ? $this->sampleFor($jsonSchema) : null;
        $content = $structured !== null
            ? (string) json_encode($structured)
            : 'This is a sample answer from the development AI (no API key is set yet). You asked: "'.Str::limit($question, 120).'"';

        $input = (int) ceil(mb_strlen(collect($messages)->pluck('content')->implode(' ')) / 4);

        return new AiResponse($content, $structured, $this->name(), $this->model(), $input, (int) ceil(mb_strlen($content) / 4));
    }

    /**
     * Builds an example value that matches the schema.
     *
     * @param  array<string, mixed>  $schema
     */
    private function sampleFor(array $schema, string $name = 'value'): mixed
    {
        return match ($schema['type'] ?? 'string') {
            'object' => collect((array) ($schema['properties'] ?? []))
                ->map(fn ($property, $key) => $this->sampleFor((array) $property, (string) $key))
                ->all(),
            'array' => [$this->sampleFor((array) ($schema['items'] ?? []), Str::singular($name))],
            'integer', 'number' => 1,
            'boolean' => true,
            default => 'Sample '.str_replace('_', ' ', $name),
        };
    }
}
