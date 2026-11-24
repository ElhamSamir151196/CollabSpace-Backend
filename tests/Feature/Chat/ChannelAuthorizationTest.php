<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * Switches broadcasting to a Reverb connection with dummy keys (nothing is
 * sent anywhere) and reloads routes/channels.php, so the real channel
 * authorization endpoint can be called: POST /api/broadcasting/auth.
 */
beforeEach(function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);

    app(BroadcastManager::class)->forgetDrivers();
    require base_path('routes/channels.php');
});

function authorizeChannel(string $channel): TestResponse
{
    return test()->postJson('/api/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => $channel,
    ]);
}

it('authorizes a conversation channel for its members only', function () {
    [$a, $b, $stranger] = User::factory()->count(3)->create()->all();
    $chat = Conversation::factory()->direct($a, $b)->create();

    Sanctum::actingAs($a);
    authorizeChannel("private-conversation.{$chat->id}")->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($stranger);
    authorizeChannel("private-conversation.{$chat->id}")->assertForbidden();
});

it('authorizes only your own personal channel', function () {
    [$me, $other] = User::factory()->count(2)->create()->all();
    Sanctum::actingAs($me);

    authorizeChannel("private-App.Models.User.{$me->id}")->assertOk();
    authorizeChannel("private-App.Models.User.{$other->id}")->assertForbidden();
});

it('returns the user info for the online presence channel', function () {
    $me = User::factory()->create(['name' => 'Sara Hassan']);
    Sanctum::actingAs($me);

    $data = json_decode(authorizeChannel('presence-online')->assertOk()->json('channel_data'), true);

    expect($data['user_info'])->toMatchArray(['id' => $me->id, 'name' => 'Sara Hassan']);
});

it('requires a token', function () {
    authorizeChannel('presence-online')->assertUnauthorized();
});
