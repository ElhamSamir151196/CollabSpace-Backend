<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Small user card — for lists, "Add Guests", mentions, avatars on tasks.
 * No email / phone, so it is safe to show to any member.
 *
 * @mixin User
 */
class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatarUrl(),
            'job_title' => $this->job_title,
            'availability_status' => $this->availability_status?->toArray(),
        ];
    }
}
