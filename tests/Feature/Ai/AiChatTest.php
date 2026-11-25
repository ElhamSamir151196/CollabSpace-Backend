<?php

use App\Enums\UserRole;
use App\Models\AiConversation;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiSettings;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

it('creates a chat and answers the first message', function () {
    $user = actingAsRole(UserRole::TeamMember);

    $this->postJson('/api/v1/ai/conversations', ['message' => 'What are my open tasks?'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'What are my open tasks?')
        ->assertJsonCount(2, 'data.messages')
        ->assertJsonPath('data.messages.0.role', 'user')
        ->assertJsonPath('data.messages.1.role', 'assistant');

    expect(AiConversation::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('creates an empty chat without calling the AI', function () {
    actingAsRole(UserRole::TeamMember);

    $this->postJson('/api/v1/ai/conversations')
        ->assertCreated()
        ->assertJsonPath('data.title', 'New chat')
        ->assertJsonCount(0, 'data.messages');
});

it('sends a message and returns the question and the answer', function () {
    $user = actingAsRole(UserRole::TeamMember);
    $conversation = AiConversation::factory()->for($user)->create(['title' => 'New chat']);

    $this->postJson("/api/v1/ai/conversations/{$conversation->id}/messages", ['content' => 'Summarize my week'])
        ->assertCreated()
        ->assertJsonPath('data.question.content', 'Summarize my week')
        ->assertJsonPath('data.answer.role', 'assistant')
        ->assertJsonPath('data.answer.model', 'fake-model');

    expect($conversation->refresh()->title)->toBe('Summarize my week')
        ->and($conversation->messages()->count())->toBe(2);
});

it('lists only my chats, newest first, with a preview', function () {
    $user = actingAsRole(UserRole::TeamMember);
    $old = AiConversation::factory()->for($user)->create(['last_message_at' => now()->subDay()]);
    $new = AiConversation::factory()->for($user)->create(['last_message_at' => now()]);
    $new->messages()->create(['role' => 'user', 'content' => 'Hello there']);
    AiConversation::factory()->create();

    $this->getJson('/api/v1/ai/conversations')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $new->id)
        ->assertJsonPath('data.0.last_message.content', 'Hello there')
        ->assertJsonPath('data.1.id', $old->id);
});

it('keeps chats private to their owner', function () {
    $conversation = AiConversation::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson("/api/v1/ai/conversations/{$conversation->id}")->assertForbidden();
    $this->postJson("/api/v1/ai/conversations/{$conversation->id}/messages", ['content' => 'hi'])->assertForbidden();
    $this->deleteJson("/api/v1/ai/conversations/{$conversation->id}")->assertForbidden();
});

it('shows and deletes my chat', function () {
    $user = actingAsRole(UserRole::TeamMember);
    $conversation = AiConversation::factory()->for($user)->create();
    $conversation->messages()->create(['role' => 'user', 'content' => 'First']);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'Second']);

    $this->getJson("/api/v1/ai/conversations/{$conversation->id}")
        ->assertOk()
        ->assertJsonPath('data.messages.0.content', 'First')
        ->assertJsonPath('data.messages.1.content', 'Second');

    $this->deleteJson("/api/v1/ai/conversations/{$conversation->id}")->assertOk();

    expect(AiConversation::query()->find($conversation->id))->toBeNull();
});

it('returns 503 and keeps the chat clean when AI is turned off', function () {
    $user = actingAsRole(UserRole::TeamMember);
    $conversation = AiConversation::factory()->for($user)->create();
    app(AiSettings::class)->update(['enabled' => false], User::factory()->admin()->create());

    $this->postJson("/api/v1/ai/conversations/{$conversation->id}/messages", ['content' => 'hi'])
        ->assertStatus(503)
        ->assertJsonPath('success', false);

    expect($conversation->messages()->count())->toBe(0);
});

it('removes the question when the provider fails', function () {
    config(['ai.default' => 'gemini', 'ai.providers.gemini.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['error' => ['message' => 'quota']], 400)]);

    $user = actingAsRole(UserRole::TeamMember);
    $conversation = AiConversation::factory()->for($user)->create();

    $this->postJson("/api/v1/ai/conversations/{$conversation->id}/messages", ['content' => 'hi'])->assertStatus(503);

    expect($conversation->messages()->count())->toBe(0);
});

it('answers a one-shot Ask AI question, optionally about one project', function () {
    [$project, , [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    $this->postJson('/api/v1/ai/ask', ['question' => 'Who is late?'])
        ->assertOk()
        ->assertJsonPath('data.model', 'fake-model');

    $this->postJson('/api/v1/ai/ask', ['question' => 'How is it going?', 'project_id' => $project->id])->assertOk();

    $hidden = Project::factory()->create();
    $this->postJson('/api/v1/ai/ask', ['question' => 'Tell me', 'project_id' => $hidden->id])->assertForbidden();
});

it('only sends data the user is allowed to see', function () {
    config(['ai.default' => 'gemini', 'ai.providers.gemini.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]])]);

    [$mine, , [$member]] = projectWithTeam();
    $mine->update(['name' => 'Visible Phoenix']);
    Project::factory()->create(['name' => 'Secret Apollo']);
    Sanctum::actingAs($member);

    $this->postJson('/api/v1/ai/ask', ['question' => 'List my projects'])->assertOk();

    Http::assertSent(function ($request) {
        $body = json_encode($request->data());

        return str_contains($body, 'Visible Phoenix') && ! str_contains($body, 'Secret Apollo');
    });
});

it('reports the AI status without secrets', function () {
    config(['ai.providers.gemini.api_key' => 'super-secret']);
    actingAsRole(UserRole::TeamMember);

    $response = $this->getJson('/api/v1/ai/status')
        ->assertOk()
        ->assertJsonPath('data.enabled', true);

    expect($response->getContent())->not->toContain('super-secret');
});
