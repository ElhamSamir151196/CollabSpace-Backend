<?php

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * A project with two teams and their channels:
 *  - $frontendMember is only in Frontend, $backendMember only in Backend.
 *
 * @return array<string, mixed>
 */
function chatWorld(): array
{
    $pm = User::factory()->projectManager()->create();
    [$project, $frontend, [$frontendMember]] = projectWithTeam($pm, 1);
    $backend = Team::factory()->create(['name' => 'Backend']);
    $backendMember = User::factory()->create();
    $backend->members()->attach($backendMember->id);
    $project->teams()->attach($backend->id);

    return [
        'pm' => $pm,
        'project' => $project,
        'frontendMember' => $frontendMember,
        'backendMember' => $backendMember,
        'public' => Conversation::factory()->forProject($project)->create(),
        'frontendChannel' => Conversation::factory()->forTeam($project, $frontend)->create(),
        'backendChannel' => Conversation::factory()->forTeam($project, $backend)->create(),
    ];
}

function inboxIds(): array
{
    return collect(test()->getJson('/api/v1/conversations')->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
}

it('shows project members the public channel and only their own team channel', function () {
    $w = chatWorld();

    Sanctum::actingAs($w['frontendMember']);
    expect(inboxIds())->toBe([$w['public']->id, $w['frontendChannel']->id]);

    $this->getJson("/api/v1/conversations/{$w['backendChannel']->id}")->assertForbidden();
    $this->getJson("/api/v1/conversations/{$w['backendChannel']->id}/messages")->assertForbidden();
    $this->postJson("/api/v1/conversations/{$w['backendChannel']->id}/messages", ['body' => 'hi'])->assertForbidden();
});

it('lets the project manager into every channel of the project', function () {
    $w = chatWorld();
    Sanctum::actingAs($w['pm']);

    expect(inboxIds())->toBe([$w['public']->id, $w['frontendChannel']->id, $w['backendChannel']->id]);
});

it('locks a team channel once the team is removed from the project', function () {
    $w = chatWorld();
    $w['project']->teams()->detach($w['frontendChannel']->team_id);
    Sanctum::actingAs($w['frontendMember']);

    $this->getJson("/api/v1/conversations/{$w['frontendChannel']->id}")->assertForbidden();
});

it('hides every channel from people outside the project', function () {
    chatWorld();
    Sanctum::actingAs(User::factory()->create());

    expect(inboxIds())->toBe([]);
});

it('lets admins read all channels but not other people\'s direct chats', function () {
    $w = chatWorld();
    $direct = Conversation::factory()->direct($w['frontendMember'], $w['backendMember'])->create();
    actingAsRole(UserRole::Admin);

    expect(inboxIds())->toBe([$w['public']->id, $w['frontendChannel']->id, $w['backendChannel']->id]);
    $this->getJson("/api/v1/conversations/{$direct->id}")->assertForbidden();
});

it('orders the list by latest message and shows the last message and unread count', function () {
    $w = chatWorld();
    Message::factory()->for($w['public'])->by($w['pm'])->create(['body' => 'Older']);
    $w['public']->update(['last_message_at' => now()->subHour()]);
    Message::factory()->for($w['frontendChannel'])->by($w['pm'])->count(2)->create(['body' => 'We need to improve performance']);
    $w['frontendChannel']->update(['last_message_at' => now()]);
    Sanctum::actingAs($w['frontendMember']);

    $this->getJson('/api/v1/conversations')
        ->assertOk()
        ->assertJsonPath('data.0.id', $w['frontendChannel']->id)
        ->assertJsonPath('data.0.unread_count', 2)
        ->assertJsonPath('data.0.last_message.body', 'We need to improve performance')
        ->assertJsonPath('data.0.project.slug', $w['project']->slug)
        ->assertJsonPath('data.1.id', $w['public']->id);

    $this->getJson('/api/v1/conversations/unread-count')->assertJsonPath('data.unread_count', 3);
    $this->getJson('/api/v1/conversations?type=team')->assertJsonCount(1, 'data');
});
