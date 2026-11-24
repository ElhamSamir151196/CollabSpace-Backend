<?php

namespace App\Actions\Meeting;

use App\DTOs\Meeting\TranscriptEntryData;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\MeetingTranscript;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Adds transcript lines in bulk (the organizer pastes / types the transcript).
 * When a line points to a user, their name is used as the speaker name.
 */
class AddTranscriptEntriesAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    /**
     * @param  list<TranscriptEntryData>  $entries
     * @return Collection<int, MeetingTranscript>
     */
    public function handle(Meeting $meeting, array $entries, User $by): Collection
    {
        return DB::transaction(function () use ($meeting, $entries, $by): Collection {
            $position = $this->meetings->nextTranscriptPosition($meeting);
            $speakers = User::query()
                ->whereKey(array_filter(array_map(fn (TranscriptEntryData $entry) => $entry->speakerId, $entries)))
                ->pluck('name', 'id');

            $created = new Collection;

            foreach ($entries as $entry) {
                $created->push($meeting->transcripts()->create([
                    'speaker_id' => $entry->speakerId,
                    'speaker_name' => $entry->speakerName ?? (string) $speakers->get($entry->speakerId, __('Unknown')),
                    'offset_seconds' => $entry->offsetSeconds,
                    'content' => $entry->content,
                    'language' => $entry->language,
                    'position' => $position++,
                    'created_by' => $by->id,
                ]));
            }

            return $created;
        });
    }
}
