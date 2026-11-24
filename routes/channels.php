<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Real-time channels (Laravel Reverb)
|--------------------------------------------------------------------------
|
| The React app authorizes with: POST /api/broadcasting/auth
| using the same "Authorization: Bearer <token>" header as the API.
|
*/

// Personal channel: direct-chat updates (and notifications later).
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// One chat: new / edited / deleted messages and read receipts.
Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation) {
    return $user->is_active && $conversation->isAccessibleBy($user);
});

// Presence channel for online indicators: everyone signed in joins it.
Broadcast::channel('online', function (User $user) {
    return $user->is_active
        ? ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatarUrl()]
        : false;
});
