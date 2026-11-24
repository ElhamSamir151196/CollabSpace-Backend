<?php

use App\Enums\NotificationKind;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AddedToProjectNotification;
use App\Notifications\AddedToTeamNotification;
use Laravel\Sanctum\Sanctum;

/**
 * Sends two notifications to the user (one project, one team).
 */
function giveNotifications(User $user): void
{
    $user->notify(new AddedToProjectNotification(Project::factory()->create(['name' => 'Alpha'])));
    test()->travel(1)->seconds();
    $user->notify(new AddedToTeamNotification(Team::factory()->create(['name' => 'Backend'])));
}

it('lists my notifications, newest first, with the unread count', function () {
    $me = User::factory()->create();
    giveNotifications($me);
    giveNotifications(User::factory()->create());
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.unread_count', 2)
        ->assertJsonPath('data.0.kind', 'added_to_team')
        ->assertJsonPath('data.0.title', NotificationKind::AddedToTeam->label())
        ->assertJsonPath('data.0.read_at', null)
        ->assertJsonStructure(['data' => [['id', 'kind', 'title', 'message', 'target', 'actor', 'read_at', 'created_at']]]);

    $this->getJson('/api/v1/notifications?kind=added_to_project')->assertJsonCount(1, 'data');
});

it('marks one or all notifications as read', function () {
    $me = User::factory()->create();
    giveNotifications($me);
    Sanctum::actingAs($me);
    $id = $me->notifications()->first()->id;

    $this->patchJson("/api/v1/notifications/{$id}/read")
        ->assertOk()
        ->assertJsonPath('data.read_at', fn ($value) => $value !== null);

    $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 1);
    $this->getJson('/api/v1/notifications?unread=1')->assertJsonCount(1, 'data');

    $this->patchJson('/api/v1/notifications/read-all')->assertOk();
    $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 0);
});

it('deletes my notification and hides other people\'s', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    giveNotifications($me);
    giveNotifications($other);
    $theirs = $other->notifications()->first()->id;
    Sanctum::actingAs($me);

    $this->patchJson("/api/v1/notifications/{$theirs}/read")->assertNotFound();
    $this->deleteJson("/api/v1/notifications/{$theirs}")->assertNotFound();

    $this->deleteJson('/api/v1/notifications/'.$me->notifications()->first()->id)->assertOk();
    expect($me->notifications()->count())->toBe(1);
});

it('is saved and pushed live with the same shape', function () {
    $user = User::factory()->create();
    $notification = new AddedToProjectNotification(Project::factory()->create(['name' => 'Alpha']));

    // The broadcast goes to the default private channel "App.Models.User.{id}" (see routes/channels.php).
    expect($notification->via($user))->toBe(['database', 'broadcast'])
        ->and($notification->databaseType($user))->toBe('added_to_project');

    $payload = $notification->toBroadcast($user)->data;
    expect(array_keys($payload))->toBe(['id', 'kind', 'title', 'message', 'target', 'actor', 'read_at', 'created_at']);
});
