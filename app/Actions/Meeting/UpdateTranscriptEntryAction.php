<?php

namespace App\Actions\Meeting;

use App\DTOs\Meeting\TranscriptEntryData;
use App\Models\MeetingTranscript;
use App\Models\User;

class UpdateTranscriptEntryAction
{
    /**
     * @param  array<string, mixed>  $input  only the fields that were sent
     */
    public function handle(MeetingTranscript $entry, array $input): MeetingTranscript
    {
        $attributes = [];

        if (array_key_exists('speaker_id', $input)) {
            $attributes['speaker_id'] = $input['speaker_id'] !== null ? (int) $input['speaker_id'] : null;

            if ($attributes['speaker_id'] !== null && ! array_key_exists('speaker_name', $input)) {
                $attributes['speaker_name'] = User::query()->whereKey($attributes['speaker_id'])->value('name');
            }
        }

        if (array_key_exists('speaker_name', $input)) {
            $attributes['speaker_name'] = trim((string) $input['speaker_name']);
        }

        if (array_key_exists('offset', $input)) {
            $attributes['offset_seconds'] = TranscriptEntryData::parseOffset($input['offset']);
        }

        if (array_key_exists('content', $input)) {
            $attributes['content'] = trim((string) $input['content']);
        }

        if (array_key_exists('language', $input)) {
            $attributes['language'] = strtolower((string) $input['language']);
        }

        if ($attributes !== []) {
            $entry->update($attributes);
        }

        return $entry;
    }
}
