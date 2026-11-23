<?php

namespace App\Http\Resources\V1;

use App\DTOs\Auth\AuthResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response of register / login / google / me:
 * the user, the permission names (so React can show/hide buttons)
 * and the token when a new one was issued.
 *
 * @property AuthResult $resource
 */
class AuthSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource->user->loadMissing('roles');

        return [
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
            'token' => $this->when($this->resource->token !== null, fn () => $this->resource->token?->toArray()),
        ];
    }
}
