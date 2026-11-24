<?php

namespace App\Actions\Meeting;

use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Services\Meeting\MeetingCoverStorage;
use Illuminate\Http\UploadedFile;

class UpdateMeetingCoverAction
{
    public function __construct(
        private readonly MeetingRepositoryInterface $meetings,
        private readonly MeetingCoverStorage $covers,
    ) {}

    /**
     * Replaces the cover (or removes it when $image is null) and deletes the old file.
     */
    public function handle(Meeting $meeting, ?UploadedFile $image): Meeting
    {
        $oldPath = $meeting->cover_path;
        $newPath = $image !== null ? $this->covers->store($image) : null;

        $meeting = $this->meetings->update($meeting, ['cover_path' => $newPath]);

        $this->covers->delete($oldPath);

        return $meeting;
    }
}
