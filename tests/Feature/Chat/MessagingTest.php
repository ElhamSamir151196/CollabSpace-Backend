<?php

use App\Enums\UserRole;
use App\Events\Chat\ConversationRead;
use App\Events\Chat\MessageDeleted;
use App\Events\Chat\MessageSent;
use App\Events\Chat\MessageUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * @return array{0: Conversation, 1: User, 2: User, 3: User} public channel, manager, member A, member B
 */
function projectChannel(): array
{
    $pm = User::factory()->projectManager()->create();
    [$project, , [$a, $b]] = projectWithTeam($pm);

    return [Conversation::factory()->forProject($project)->create(), $pm, $a, $b];
}

it('sends a message and pushes it live to the conversation', function () {
    Event::fake([MessageSent::class]);
    [$channel, , $me] = projectChannel();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => 'Yes, we can do this and edit screen'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Yes, we can do this and edit screen')
        ->assertJsonPath('data.author.id', $me->id)
        ->assertJsonPath('data.can.update', true);

    expect($channel->fresh()->last_message_at)->not->toBeNull();

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($channel) {
        return collect($event->broadcastOn())->pluck('name')->all() === ["private-conversation.{$channel->id}"];
    });
});

it('also pushes direct messages to both people\'s personal channels', function () {
    Event::fake([MessageSent::class]);
    [$me, $other] = User::factory()->count(2)->create()->all();
    $chat = Conversation::factory()->direct($me, $other)->create();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/conversations/{$chat->id}/messages", ['body' => 'Ok, I wait you'])->assertCreated();

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($chat, $me, $other) {
        $channels = collect($event->broadcastOn())->pluck('name')->sort()->values()->all();

        return $channels === collect([
            "private-conversation.{$chat->id}",
            "private-App.Models.User.{$me->id}",
            "private-App.Models.User.{$other->id}",
        ])->sort()->values()->all();
    });
});

it('sends files with or without text', function () {
    Storage::fake('local');
    [$channel, , $me] = projectChannel();
    Sanctum::actingAs($me);

    $this->post("/api/v1/conversations/{$channel->id}/messages", [
        'files' => [UploadedFile::fake()->image('screen.png')],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.body', null)
        ->assertJsonPath('data.attachments.0.name', 'screen.png')
        ->assertJsonPath('data.attachments.0.source.type', 'message');

    $this->postJson("/api/v1/conversations/{$channel->id}/messages", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('body');
});

it('lets people in a direct chat open its files, and nobody else', function () {
    Storage::fake('local');
    [$me, $other, $stranger] = User::factory()->count(3)->create()->all();
    $chat = Conversation::factory()->direct($me, $other)->create();
    Sanctum::actingAs($me);

    $fileId = $this->post("/api/v1/conversations/{$chat->id}/messages", [
        'files' => [UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf')],
    ], ['Accept' => 'application/json'])->assertCreated()->json('data.attachments.0.id');

    Sanctum::actingAs($other);
    $this->getJson("/api/v1/files/{$fileId}")->assertOk();

    Sanctum::actingAs($stranger);
    $this->getJson("/api/v1/files/{$fileId}")->assertForbidden();
});

it('validates mentions: only people who can read the chat', function () {
    [$channel, $pm, $me, $teammate] = projectChannel();
    $outsider = User::factory()->create();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => '@Sara look', 'mention_ids' => [$teammate->id, $pm->id]])
        ->assertCreated()
        ->assertJsonCount(2, 'data.mentions');

    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => '@x', 'mention_ids' => [$outsider->id]])
        ->assertStatus(422)
        ->assertJsonValidationErrors('mention_ids');
});

it('keeps thread replies out of the timeline and lists them in the thread', function () {
    [$channel, , $me, $other] = projectChannel();
    Sanctum::actingAs($me);
    $parentId = $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => 'I edit it and now you can see it'])->json('data.id');

    Sanctum::actingAs($other);
    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => 'Looks great', 'parent_id' => $parentId])->assertCreated();
    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => 'Next one'])->assertCreated();

    $this->getJson("/api/v1/conversations/{$channel->id}/messages")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.body', 'Next one')
        ->assertJsonPath('data.1.replies_count', 1);

    $this->getJson("/api/v1/messages/{$parentId}/replies")
        ->assertOk()
        ->assertJsonPath('meta.parent.id', $parentId)
        ->assertJsonPath('data.0.body', 'Looks great');
});

it('only allows replies to top-level messages of the same chat', function () {
    [$channel, , $me] = projectChannel();
    [$otherChannel] = projectChannel();
    $foreign = Message::factory()->for($otherChannel)->create();
    $parent = Message::factory()->for($channel)->by($me)->create();
    $reply = Message::factory()->replyTo($parent)->by($me)->create();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => 'x', 'parent_id' => $foreign->id])
        ->assertJsonValidationErrors('parent_id');
    $this->postJson("/api/v1/conversations/{$channel->id}/messages", ['body' => 'x', 'parent_id' => $reply->id])
        ->assertJsonValidationErrors('parent_id');
});

it('pages the timeline with a cursor, newest first', function () {
    [$channel, , $me] = projectChannel();
    Message::factory()->for($channel)->by($me)->count(5)->create();
    Sanctum::actingAs($me);

    $first = $this->getJson("/api/v1/conversations/{$channel->id}/messages?per_page=3")->assertOk();
    $cursor = $first->json('meta.pagination.next_cursor');

    expect($first->json('data'))->toHaveCount(3)->and($cursor)->not->toBeNull();

    $this->getJson("/api/v1/conversations/{$channel->id}/messages?per_page=3&cursor={$cursor}")
        ->assertJsonCount(2, 'data');
});

it('lets only the author edit a message', function () {
    Event::fake([MessageUpdated::class]);
    [$channel, , $me, $other] = projectChannel();
    $message = Message::factory()->for($channel)->by($me)->create(['body' => 'Typo']);

    Sanctum::actingAs($other);
    $this->patchJson("/api/v1/messages/{$message->id}", ['body' => 'Hacked'])->assertForbidden();

    Sanctum::actingAs($me);
    $this->patchJson("/api/v1/messages/{$message->id}", ['body' => 'Fixed'])
        ->assertOk()
        ->assertJsonPath('data.body', 'Fixed')
        ->assertJsonPath('data.is_edited', true);

    Event::assertDispatched(MessageUpdated::class);
});

it('lets the author and the project manager delete channel messages', function () {
    Event::fake([MessageDeleted::class]);
    [$channel, $pm, $me, $other] = projectChannel();
    $mine = Message::factory()->for($channel)->by($me)->create();
    $theirs = Message::factory()->for($channel)->by($other)->create();

    Sanctum::actingAs($me);
    $this->deleteJson("/api/v1/messages/{$theirs->id}")->assertForbidden();
    $this->deleteJson("/api/v1/messages/{$mine->id}")->assertOk();

    Sanctum::actingAs($pm);
    $this->deleteJson("/api/v1/messages/{$theirs->id}")->assertOk();

    $this->assertSoftDeleted($mine);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'chat', 'event' => 'moderated']);
    Event::assertDispatchedTimes(MessageDeleted::class, 2);
});

it('does not let admins delete messages in other people\'s direct chats', function () {
    [$a, $b] = User::factory()->count(2)->create()->all();
    $chat = Conversation::factory()->direct($a, $b)->create();
    $message = Message::factory()->for($chat)->by($a)->create();
    actingAsRole(UserRole::Admin);

    $this->deleteJson("/api/v1/messages/{$message->id}")->assertForbidden();
});

it('marks a conversation as read and sends a read receipt', function () {
    Event::fake([ConversationRead::class]);
    [$me, $other] = User::factory()->count(2)->create()->all();
    $chat = Conversation::factory()->direct($me, $other)->create();
    $messages = Message::factory()->for($chat)->by($other)->count(3)->create();
    Sanctum::actingAs($me);

    $this->getJson("/api/v1/conversations/{$chat->id}")->assertJsonPath('data.unread_count', 3);

    $this->postJson("/api/v1/conversations/{$chat->id}/read", ['message_id' => $messages[1]->id])->assertOk();
    $this->getJson("/api/v1/conversations/{$chat->id}")->assertJsonPath('data.unread_count', 1);

    $this->postJson("/api/v1/conversations/{$chat->id}/read")->assertOk();
    $this->getJson("/api/v1/conversations/{$chat->id}")
        ->assertJsonPath('data.unread_count', 0)
        ->assertJsonFragment(['user_id' => $me->id, 'last_read_message_id' => $messages[2]->id]);

    // Reading an older message never moves the marker back.
    $this->postJson("/api/v1/conversations/{$chat->id}/read", ['message_id' => $messages[0]->id])->assertOk();
    $this->getJson("/api/v1/conversations/{$chat->id}")->assertJsonPath('data.unread_count', 0);

    Event::assertDispatched(ConversationRead::class, fn ($event) => $event->userId === $me->id);
});

it('does not count my own messages as unread', function () {
    [$me, $other] = User::factory()->count(2)->create()->all();
    $chat = Conversation::factory()->direct($me, $other)->create();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/conversations/{$chat->id}/messages", ['body' => 'hello'])->assertCreated();

    $this->getJson("/api/v1/conversations/{$chat->id}")->assertJsonPath('data.unread_count', 0);
});

it('records when a user was last seen', function () {
    $me = User::factory()->create(['last_seen_at' => null]);
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/conversations')->assertOk();

    expect($me->fresh()->last_seen_at)->not->toBeNull();
});
