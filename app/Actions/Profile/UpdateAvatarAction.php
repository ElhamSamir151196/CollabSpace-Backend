<?php

namespace App\Actions\Profile;

use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateAvatarAction
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /**
     * Stores the new image with a random name and deletes the old one.
     * Pass null to remove the avatar.
     */
    public function handle(User $user, ?UploadedFile $avatar): User
    {
        $disk = Storage::disk((string) config('collabspace.avatars.disk'));
        $oldPath = $user->avatar_path;

        $newPath = $avatar?->store((string) config('collabspace.avatars.directory'), [
            'disk' => config('collabspace.avatars.disk'),
        ]);

        $user = $this->users->update($user, ['avatar_path' => $newPath ?: null]);

        if ($oldPath !== null) {
            $disk->delete($oldPath);
        }

        return $user;
    }
}
