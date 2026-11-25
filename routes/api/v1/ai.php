<?php

use App\Http\Controllers\Api\V1\Admin\AiSettingsController;
use App\Http\Controllers\Api\V1\Ai\AiConversationController;
use App\Http\Controllers\Api\V1\Ai\AiStatusController;
use App\Http\Controllers\Api\V1\Ai\AskAiController;
use App\Http\Controllers\Api\V1\Ai\SendAiMessageController;
use App\Http\Controllers\Api\V1\Meeting\MeetingSummaryController;
use App\Http\Controllers\Api\V1\Project\ProjectInsightsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI
|--------------------------------------------------------------------------
|
| /api/v1/ai/conversations/{conversation}   Names: v1.ai.conversations.*
| /api/v1/ai/ask                            Name:  v1.ai.ask
| /api/v1/meetings/{meeting}/summary        Names: v1.meetings.summary.*
| /api/v1/projects/{project}/ai-insights    Name:  v1.projects.ai-insights
| /api/v1/admin/ai-settings                 Names: v1.admin.ai-settings.*
| Every call that reaches the AI is limited by "throttle:ai" (20 / minute per user).
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('ai/status', AiStatusController::class)->name('ai.status');

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('conversations', [AiConversationController::class, 'index'])->name('conversations.index');
        Route::get('conversations/{conversation}', [AiConversationController::class, 'show'])->name('conversations.show');
        Route::delete('conversations/{conversation}', [AiConversationController::class, 'destroy'])->name('conversations.destroy');

        Route::middleware('throttle:ai')->group(function () {
            Route::post('conversations', [AiConversationController::class, 'store'])->name('conversations.store');
            Route::post('conversations/{conversation}/messages', SendAiMessageController::class)->name('conversations.messages.store');
            Route::post('ask', AskAiController::class)->name('ask');
        });
    });

    Route::get('meetings/{meeting}/summary', [MeetingSummaryController::class, 'show'])->name('meetings.summary.show');
    Route::post('meetings/{meeting}/summary', [MeetingSummaryController::class, 'store'])
        ->middleware('throttle:ai')
        ->name('meetings.summary.store');

    Route::get('projects/{project}/ai-insights', ProjectInsightsController::class)
        ->middleware('throttle:ai')
        ->name('projects.ai-insights');

    Route::get('admin/ai-settings', [AiSettingsController::class, 'show'])->name('admin.ai-settings.show');
    Route::put('admin/ai-settings', [AiSettingsController::class, 'update'])->name('admin.ai-settings.update');
});
