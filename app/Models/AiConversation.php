<?php

namespace App\Models;

use Database\Factories\AiConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A private chat between one user and the AI.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $messages_count
 * @property-read User $user
 * @property-read Collection<int, AiMessage> $messages
 * @property-read AiMessage|null $latestMessage
 */
#[Fillable(['user_id', 'title', 'last_message_at'])]
class AiConversation extends Model
{
    /** @use HasFactory<AiConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AiMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    /**
     * @return HasOne<AiMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(AiMessage::class)->latestOfMany();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
