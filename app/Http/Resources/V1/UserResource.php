<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full user — for the user's own profile, /auth/me and admin screens.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatarUrl(),
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'years_of_experience' => $this->years_of_experience,
            'availability_status' => $this->availability_status?->toArray(),
            'role' => $this->whenLoaded('roles', fn () => $this->role()?->toArray()),
            'is_active' => $this->is_active,
            'has_password' => $this->hasPassword(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
