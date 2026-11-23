<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * - Anyone who can see the project / task can see and upload its files.
 * - A file can be deleted by the person who uploaded it, or by whoever
 *   manages the project (its manager or an admin).
 */
class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $attachment->project !== null && $user->can('view', $attachment->project);
    }

    public function upload(User $user, Project|Task $target): bool
    {
        return $user->can('view', $target);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($attachment->project === null) {
            return false;
        }

        return ($attachment->isUploadedBy($user) && $user->can('view', $attachment->project))
            || $user->can('update', $attachment->project);
    }
}
