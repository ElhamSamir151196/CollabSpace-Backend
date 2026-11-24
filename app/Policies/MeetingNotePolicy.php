<?php

namespace App\Policies;

use App\Models\MeetingNote;
use App\Models\User;

/**
 * Notes / comments: the author edits; the author or the meeting's managers delete.
 */
class MeetingNotePolicy
{
    public function update(User $user, MeetingNote $note): bool
    {
        return $note->isAuthoredBy($user) && $user->can('view', $note->meeting);
    }

    public function delete(User $user, MeetingNote $note): bool
    {
        return ($note->isAuthoredBy($user) && $user->can('view', $note->meeting))
            || $user->can('update', $note->meeting);
    }
}
