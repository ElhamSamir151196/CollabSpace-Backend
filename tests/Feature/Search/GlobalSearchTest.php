<?php

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Meeting;
use App\Models\Message;
use App\Models\Project;
use App\Models\SearchHistory;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('searches every section and only returns what the user can see', function () {
    [$project, $team, [$me]] = projectWithTeam();
    $project->update(['name' => 'Design Portal']);
    $hidden = Project::factory()->create(['name' => 'Design Secret']);

    Task::factory()->for($project)->forTeam($team)->create(['title' => 'Design system updates']);
    Task::factory()->for($hidden)->create(['title' => 'Design hidden task']);
    Meeting::factory()->for($project)->create(['title' => 'Design Review']);
    Attachment::factory()->forProject($project)->create(['original_name' => 'design_spec.pdf']);
    $channel = Conversation::factory()->forProject($project)->create();
    Message::factory()->for($channel)->create(['body' => 'The new design looks great']);
    Team::factory()->create(['name' => 'Design Team']);
    User::factory()->create(['name' => 'Sara Designer', 'job_title' => 'UI Designer']);
    Sanctum::actingAs($me);

    $response = $this->getJson('/api/v1/search?q=design')->assertOk();

    expect(collect($response->json('data.projects'))->pluck('title')->all())->toBe(['Design Portal'])
        ->and(collect($response->json('data.tasks'))->pluck('title')->all())->toBe(['Design system updates'])
        ->and($response->json('data.meetings.0.title'))->toBe('Design Review')
        ->and($response->json('data.files.0.title'))->toBe('design_spec.pdf')
        ->and($response->json('data.messages.0.meta.conversation_id'))->toBe($channel->id)
        ->and($response->json('data.teams.0.title'))->toBe('Design Team')
        // Factory users can have "Designer" job titles too, so only check Sara is there.
        ->and(collect($response->json('data.people'))->pluck('title'))->toContain('Sara Designer')
        ->and($response->json('data.projects.0.meta.slug'))->toBe($project->slug)
        ->and($response->json('meta.total'))->toBeGreaterThanOrEqual(7);

    $response->assertJsonMissing(['title' => 'Design Secret'])
        ->assertJsonMissing(['title' => 'Design hidden task']);
});

it('limits the search to some sections', function () {
    [, , [$me]] = projectWithTeam();
    Sanctum::actingAs($me);

    $response = $this->getJson('/api/v1/search?q=ab&types=projects,people')->assertOk();

    expect(array_keys($response->json('data')))->toBe(['projects', 'people']);

    $this->getJson('/api/v1/search?q=ab&types=passwords')->assertStatus(422);
});

it('needs at least two characters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/search?q=a')->assertStatus(422)->assertJsonValidationErrors('q');
});

it('treats % and _ as normal characters', function () {
    [$project, , [$me]] = projectWithTeam();
    $project->update(['name' => '100% Done']);
    Project::factory()->create(['name' => 'Other']);
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/search?q=0%25&types=projects')->assertJsonCount(1, 'data.projects');
    $this->getJson('/api/v1/search?q=__&types=projects')->assertJsonCount(0, 'data.projects');
});

it('keeps the last ten unique searches, newest first', function () {
    $me = User::factory()->create();
    Sanctum::actingAs($me);

    foreach (range(1, 12) as $i) {
        $this->travel(1)->seconds();
        $this->getJson("/api/v1/search?q=query{$i}")->assertOk();
    }

    $this->travel(1)->seconds();
    $this->getJson('/api/v1/search?q=query5')->assertOk();
    $this->getJson('/api/v1/search?q=typing&save=0')->assertOk();

    $history = $this->getJson('/api/v1/search/history')->assertOk()->json('data');

    expect($history)->toHaveCount(10)
        ->and($history[0]['query'])->toBe('query5')
        ->and(collect($history)->pluck('query'))->not->toContain('typing')
        ->and(collect($history)->pluck('query'))->not->toContain('query1');
});

it('removes one recent search or clears them all', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $mine = SearchHistory::query()->create(['user_id' => $me->id, 'query' => 'alpha']);
    SearchHistory::query()->create(['user_id' => $me->id, 'query' => 'beta']);
    $theirs = SearchHistory::query()->create(['user_id' => $other->id, 'query' => 'secret']);
    Sanctum::actingAs($me);

    $this->deleteJson("/api/v1/search/history/{$theirs->id}")->assertNotFound();
    $this->deleteJson("/api/v1/search/history/{$mine->id}")->assertOk();
    $this->getJson('/api/v1/search/history')->assertJsonCount(1, 'data');

    $this->deleteJson('/api/v1/search/history')->assertOk();
    $this->getJson('/api/v1/search/history')->assertJsonCount(0, 'data');
    expect(SearchHistory::query()->where('user_id', $other->id)->count())->toBe(1);
});
