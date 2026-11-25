<?php

namespace App\Services\Ai\Providers;

use App\DTOs\Ai\AiResponse;
use App\DTOs\Ai\ChatMessage;
use App\Exceptions\AiUnavailableException;
use App\Interfaces\Ai\AiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini through the REST API (generateContent).
 * The API key is sent in the "x-goog-api-key" header (never in the URL,
 * so it does not end up in logs).
 */
class GeminiProvider implements AiProvider
{
    /**
     * @param  array{api_key?: string|null, model?: string, base_url?: string, timeout?: int, thinking_level?: string|null}  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'gemini-3.5-flash-lite');
    }

    public function generate(array $messages, ?array $jsonSchema = null): AiResponse
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey === '') {
            Log::warning('Gemini is selected but GEMINI_API_KEY is missing.');

            throw new AiUnavailableException;
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->timeout((int) ($this->config['timeout'] ?? 60))
                ->retry(2, 500, fn ($exception) => $this->isRetryable($exception), throw: false)
                ->post($this->endpoint(), $this->payload($messages, $jsonSchema));
        } catch (ConnectionException $e) {
            Log::warning('Gemini connection failed.', ['error' => $e->getMessage()]);

            throw new AiUnavailableException;
        }

        if ($response->failed()) {
            Log::warning('Gemini request failed.', ['status' => $response->status(), 'body' => $response->json('error.message')]);

            throw new AiUnavailableException;
        }

        return $this->toResponse($response, $jsonSchema !== null);
    }

    private function endpoint(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/').'/models/'.$this->model().':generateContent';
    }

    /**
     * @param  list<ChatMessage>  $messages
     * @param  array<string, mixed>|null  $jsonSchema
     * @return array<string, mixed>
     */
    private function payload(array $messages, ?array $jsonSchema): array
    {
        $system = collect($messages)->filter(fn (ChatMessage $m) => $m->role === 'system')->pluck('content')->implode("\n\n");

        $contents = collect($messages)
            ->reject(fn (ChatMessage $m) => $m->role === 'system')
            ->map(fn (ChatMessage $m) => [
                // Gemini calls the assistant "model".
                'role' => $m->role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m->content]],
            ])
            ->values()
            ->all();

        $payload = [
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.4],
        ];

        // Gemini 3 models "think" before answering; "low" keeps answers fast (seconds instead of ~40s).
        if (filled($this->config['thinking_level'] ?? null)) {
            $payload['generationConfig']['thinkingConfig'] = ['thinkingLevel' => (string) $this->config['thinking_level']];
        }

        if ($system !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        if ($jsonSchema !== null) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
            $payload['generationConfig']['responseSchema'] = $this->geminiSchema($jsonSchema);
        }

        return $payload;
    }

    /**
     * Gemini expects upper-case types ("OBJECT", "STRING" ...).
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function geminiSchema(array $schema): array
    {
        foreach ($schema as $key => $value) {
            if ($key === 'type' && is_string($value)) {
                $schema[$key] = strtoupper($value);
            } elseif (is_array($value)) {
                $schema[$key] = $this->geminiSchema($value);
            }
        }

        return $schema;
    }

    private function toResponse(Response $response, bool $structured): AiResponse
    {
        /** @var list<array{text?: string}> $parts */
        $parts = $response->json('candidates.0.content.parts', []);
        $text = trim(collect($parts)->pluck('text')->implode(''));

        if ($text === '') {
            Log::warning('Gemini returned an empty answer.', ['finish_reason' => $response->json('candidates.0.finishReason')]);

            throw new AiUnavailableException;
        }

        $decoded = null;

        if ($structured) {
            $decoded = json_decode($this->stripCodeFence($text), true);

            if (! is_array($decoded)) {
                Log::warning('Gemini returned invalid JSON.');

                throw new AiUnavailableException;
            }
        }

        return new AiResponse(
            content: $text,
            structured: $decoded,
            provider: $this->name(),
            model: $this->model(),
            inputTokens: (int) $response->json('usageMetadata.promptTokenCount', 0),
            outputTokens: (int) $response->json('usageMetadata.candidatesTokenCount', 0),
        );
    }

    private function stripCodeFence(string $text): string
    {
        return (string) preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
    }

    private function isRetryable(mixed $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
    }
}
