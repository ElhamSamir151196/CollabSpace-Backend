<?php

namespace App\Actions\Meeting;

use App\Enums\MeetingNoteType;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\User;

/**
 * Adds a note / comment, or edits the text of an existing one.
 */
class SaveMeetingNoteAction
{
    public function create(Meeting $meeting, MeetingNoteType $type, string $body, User $author): MeetingNote
    {
        return $meeting->notes()->create([
            'user_id' => $author->id,
            'type' => $type,
            'body' => trim($body),
        ]);
    }

    public function update(MeetingNote $note, string $body): MeetingNote
    {
        $note->update(['body' => trim($body), 'edited_at' => now()]);

        return $note;
    }
}
