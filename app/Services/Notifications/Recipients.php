<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Turns a list of user ids into the users to notify:
 * active ones only, without the person who caused the event.
 */
class Recipients
{
    /**
     * @param  iterable<int|string>  $userIds
     * @return Collection<int, User>
     */
    public function of(iterable $userIds, ?User $except = null): Collection
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->unique();

        if ($except !== null) {
            $ids = $ids->reject(fn (int $id) => $id === $except->id);
        }

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return User::query()->whereKey($ids->all())->where('is_active', true)->get();
    }
}
