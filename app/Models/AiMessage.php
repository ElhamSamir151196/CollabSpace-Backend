<?php

namespace App\Models;

use App\Enums\AiMessageRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_conversation_id
 * @property AiMessageRole $role
 * @property string $content
 * @property string|null $provider
 * @property string|null $model
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AiConversation $conversation
 */
#[Fillable(['ai_conversation_id', 'role', 'content', 'provider', 'model', 'input_tokens', 'output_tokens'])]
class AiMessage extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'provider' => null,
        'model' => null,
        'input_tokens' => null,
        'output_tokens' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AiMessageRole::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AiConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
