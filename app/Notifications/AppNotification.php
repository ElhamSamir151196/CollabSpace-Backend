<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Base of every in-app notification.
 *  - Saved in the "notifications" table and pushed live on the user's
 *    private channel (App.Models.User.{id}).
 *  - Queued, and only sent after the database transaction commits.
 *  - Same data shape for every kind:
 *    { kind, title, message, target: { type, id, ... }, actor: { id, name, avatar_url } | null }
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract public function kind(): NotificationKind;

    /**
     * One sentence shown in the notifications list.
     */
    abstract protected function message(): string;

    /**
     * What the notification opens: ['type' => 'task', 'id' => 5, 'project_slug' => 'alpha'].
     *
     * @return array<string, mixed>
     */
    abstract protected function target(): array;

    /**
     * Who caused it (null for system reminders).
     */
    protected function actor(): ?User
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Stored in notifications.type instead of the class name.
     */
    public function databaseType(object $notifiable): string
    {
        return $this->kind()->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actor = $this->actor();

        return [
            'kind' => $this->kind()->value,
            'title' => $this->kind()->label(),
            'message' => $this->message(),
            'target' => $this->target(),
            'actor' => $actor !== null ? [
                'id' => $actor->id,
                'name' => $actor->name,
                'avatar_url' => $actor->avatarUrl(),
            ] : null,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            ...$this->toArray($notifiable),
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
