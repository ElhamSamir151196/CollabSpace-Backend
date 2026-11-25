<?php

namespace App\Http\Resources\V1;

use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiMessage
 */
class AiMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'content' => $this->content,
            'model' => $this->model,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
