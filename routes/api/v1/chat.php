<?php

use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\ConversationMessageController;
use App\Http\Controllers\Api\V1\Chat\ListMessageRepliesController;
use App\Http\Controllers\Api\V1\Chat\MarkConversationReadController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\Chat\StartDirectConversationController;
use App\Http\Controllers\Api\V1\Chat\UnreadCountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat
|--------------------------------------------------------------------------
|
| /api/v1/conversations                          Names: v1.conversations.*
| /api/v1/conversations/{conversation}/messages  Names: v1.conversations.messages.*
| /api/v1/messages/{message}                     Names: v1.messages.*
| Real-time channels are defined in routes/channels.php.
| Access: ConversationPolicy, MessagePolicy.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Static paths before {conversation}.
    Route::get('conversations/unread-count', UnreadCountController::class)->name('conversations.unread-count');
    Route::post('conversations/direct', StartDirectConversationController::class)->name('conversations.direct');

    Route::apiResource('conversations', ConversationController::class)->only(['index', 'show']);
    Route::post('conversations/{conversation}/read', MarkConversationReadController::class)->name('conversations.read');

    Route::apiResource('conversations.messages', ConversationMessageController::class)->only(['index', 'store']);

    Route::apiResource('messages', MessageController::class)->only(['update', 'destroy']);
    Route::get('messages/{message}/replies', ListMessageRepliesController::class)->name('messages.replies');
});
