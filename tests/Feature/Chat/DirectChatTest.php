<?php

use App\Models\Conversation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('opens one direct chat per pair of people', function () {
    $me = User::factory()->create();
    $sara = User::factory()->create(['name' => 'Sara Hassan']);
    Sanctum::actingAs($me);

    $first = $this->postJson('/api/v1/conversations/direct', ['user_id' => $sara->id])
        ->assertOk()
        ->assertJsonPath('data.type.value', 'direct')
        ->assertJsonPath('data.name', 'Sara Hassan')
        ->assertJsonPath('data.with.id', $sara->id)
        ->json('data.id');

    Sanctum::actingAs($sara);
    $second = $this->postJson('/api/v1/conversations/direct', ['user_id' => $me->id])->assertOk()->json('data.id');

    expect($second)->toBe($first)
        ->and(Conversation::query()->count())->toBe(1);
});

it('does not open a chat with yourself or with a deactivated user', function () {
    $me = User::factory()->create();
    $inactive = User::factory()->inactive()->create();
    Sanctum::actingAs($me);

    $this->postJson('/api/v1/conversations/direct', ['user_id' => $me->id])->assertStatus(422);
    $this->postJson('/api/v1/conversations/direct', ['user_id' => $inactive->id])->assertStatus(422);
});

it('shows a direct chat only to its two people', function () {
    [$a, $b, $c] = User::factory()->count(3)->create()->all();
    $chat = Conversation::factory()->direct($a, $b)->create();

    Sanctum::actingAs($c);
    $this->getJson("/api/v1/conversations/{$chat->id}")->assertForbidden();

    Sanctum::actingAs($a);
    $this->getJson('/api/v1/conversations?type=direct')->assertJsonCount(1, 'data');
});
