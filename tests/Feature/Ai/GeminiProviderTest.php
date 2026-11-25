<?php

use App\DTOs\Ai\ChatMessage;
use App\Exceptions\AiUnavailableException;
use App\Services\Ai\Providers\GeminiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function gemini(array $config = []): GeminiProvider
{
    return new GeminiProvider([
        'api_key' => 'test-key',
        'model' => 'gemini-2.5-flash',
        'base_url' => 'https://gemini.test/v1beta',
        'timeout' => 5,
        ...$config,
    ]);
}

it('sends the key in a header and maps roles and the system prompt', function () {
    Http::fake(['gemini.test/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => 'Hello!']]]]],
        'usageMetadata' => ['promptTokenCount' => 12, 'candidatesTokenCount' => 3],
    ])]);

    $response = gemini()->generate([
        ChatMessage::system('Be helpful.'),
        ChatMessage::user('Hi'),
        ChatMessage::assistant('Hello'),
        ChatMessage::user('How are you?'),
    ]);

    expect($response->content)->toBe('Hello!')
        ->and($response->provider)->toBe('gemini')
        ->and($response->inputTokens)->toBe(12)
        ->and($response->outputTokens)->toBe(3);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://gemini.test/v1beta/models/gemini-2.5-flash:generateContent'
        && $request->hasHeader('x-goog-api-key', 'test-key')
        && ! str_contains($request->url(), 'test-key')
        && $request['systemInstruction']['parts'][0]['text'] === 'Be helpful.'
        && array_column($request['contents'], 'role') === ['user', 'model', 'user']
        && ! isset($request['generationConfig']['thinkingConfig']));
});

it('sends the thinking level when it is set', function () {
    Http::fake(['gemini.test/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'OK']]]]]])]);

    gemini(['thinking_level' => 'low'])->generate([ChatMessage::user('x')]);

    Http::assertSent(fn (Request $request) => $request['generationConfig']['thinkingConfig'] === ['thinkingLevel' => 'low']);
});

it('asks for JSON with an upper-case schema and decodes it', function () {
    Http::fake(['gemini.test/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => "```json\n{\"summary\":\"Done\"}\n```"]]]]],
    ])]);

    $response = gemini()->generate([ChatMessage::user('x')], [
        'type' => 'object',
        'properties' => ['summary' => ['type' => 'string']],
    ]);

    expect($response->structured)->toBe(['summary' => 'Done']);

    Http::assertSent(fn (Request $request) => $request['generationConfig']['responseMimeType'] === 'application/json'
        && $request['generationConfig']['responseSchema']['type'] === 'OBJECT'
        && $request['generationConfig']['responseSchema']['properties']['summary']['type'] === 'STRING');
});

it('fails safely without a key, on errors, empty answers and bad JSON', function (array $config, array $body, int $status, ?array $schema) {
    Http::fake(['gemini.test/*' => Http::response($body, $status)]);

    gemini($config)->generate([ChatMessage::user('x')], $schema);
})->with([
    'missing key' => [['api_key' => null], [], 200, null],
    'api error' => [[], ['error' => ['message' => 'bad']], 400, null],
    'empty answer' => [[], ['candidates' => [['finishReason' => 'SAFETY']]], 200, null],
    'invalid json' => [[], ['candidates' => [['content' => ['parts' => [['text' => 'not json']]]]]], 200, ['type' => 'object']],
])->throws(AiUnavailableException::class);
