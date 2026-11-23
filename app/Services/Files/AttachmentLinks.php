<?php

namespace App\Services\Files;

use App\Models\Attachment;
use Illuminate\Support\Facades\URL;

/**
 * Short-lived signed links for downloading / previewing a file.
 * They work without the Bearer token (so <img src> and <a href> work),
 * but only until they expire and only for the exact file.
 * They are only handed out in responses to users allowed to see the file.
 */
class AttachmentLinks
{
    public function download(Attachment $attachment): string
    {
        return $this->signed($attachment, inline: false);
    }

    public function preview(Attachment $attachment): ?string
    {
        return $attachment->type()->isPreviewable($attachment->extension)
            ? $this->signed($attachment, inline: true)
            : null;
    }

    private function signed(Attachment $attachment, bool $inline): string
    {
        $parameters = ['file' => $attachment->id];

        if ($inline) {
            $parameters['inline'] = 1;
        }

        return URL::temporarySignedRoute(
            'v1.files.download',
            now()->addMinutes((int) config('collabspace.files.link_ttl_minutes')),
            $parameters,
        );
    }
}
