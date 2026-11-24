<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * - Anyone who can see the project / task can see and upload its files.
 * - Chat files follow the conversation: whoever can read the chat can see them.
 * - A project / task file can be deleted by the person who uploaded it, or by
 *   whoever manages the project. Chat files go away with their message.
 */
class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($attachment->isChatFile()) {
            $message = $attachment->attachable;

            return $message instanceof Message && $message->conversation->isAccessibleBy($user);
        }

        return $attachment->project !== null && $user->can('view', $attachment->project);
    }

    public function upload(User $user, Project|Task $target): bool
    {
        return $user->can('view', $target);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($attachment->isChatFile() || $attachment->project === null) {
            return false;
        }

        return ($attachment->isUploadedBy($user) && $user->can('view', $attachment->project))
            || $user->can('update', $attachment->project);
    }
}
