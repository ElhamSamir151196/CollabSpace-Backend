<?php

namespace App\Services\Meeting;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Meeting cover images on the public disk, under random names.
 */
class MeetingCoverStorage
{
    public function store(UploadedFile $image): string
    {
        $path = $image->store(
            (string) config('collabspace.meetings.cover_directory'),
            ['disk' => config('collabspace.meetings.cover_disk')],
        );

        if ($path === false) {
            throw new RuntimeException('The cover image could not be saved.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk((string) config('collabspace.meetings.cover_disk'))->delete($path);
        }
    }
}
