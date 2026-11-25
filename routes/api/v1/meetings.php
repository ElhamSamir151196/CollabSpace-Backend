<?php

use App\Http\Controllers\Api\V1\Meeting\CancelMeetingController;
use App\Http\Controllers\Api\V1\Meeting\DeleteMeetingCoverController;
use App\Http\Controllers\Api\V1\Meeting\JoinMeetingController;
use App\Http\Controllers\Api\V1\Meeting\MeetingController;
use App\Http\Controllers\Api\V1\Meeting\MeetingNoteController;
use App\Http\Controllers\Api\V1\Meeting\MeetingParticipantController;
use App\Http\Controllers\Api\V1\Meeting\MeetingTranscriptController;
use App\Http\Controllers\Api\V1\Meeting\RescheduleMeetingController;
use App\Http\Controllers\Api\V1\Meeting\RespondToMeetingController;
use App\Http\Controllers\Api\V1\Meeting\UpdateMeetingCoverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Meetings
|--------------------------------------------------------------------------
|
| /api/v1/meetings/{meeting}                              Names: v1.meetings.*
| /api/v1/meetings/{meeting}/participants/{participant}   Names: v1.meetings.participants.*
| /api/v1/meetings/{meeting}/transcripts/{transcript}     Names: v1.meetings.transcripts.*
| /api/v1/meetings/{meeting}/notes/{note}                 Names: v1.meetings.notes.*
| Access: MeetingPolicy, MeetingNotePolicy.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('meetings', MeetingController::class);

    Route::patch('meetings/{meeting}/reschedule', RescheduleMeetingController::class)->name('meetings.reschedule');
    Route::patch('meetings/{meeting}/cancel', CancelMeetingController::class)->name('meetings.cancel');
    Route::patch('meetings/{meeting}/attendance', RespondToMeetingController::class)->name('meetings.attendance');
    Route::post('meetings/{meeting}/join', JoinMeetingController::class)->name('meetings.join');
    Route::post('meetings/{meeting}/cover', UpdateMeetingCoverController::class)->middleware('throttle:uploads')->name('meetings.cover.update');
    Route::delete('meetings/{meeting}/cover', DeleteMeetingCoverController::class)->name('meetings.cover.destroy');

    Route::apiResource('meetings.participants', MeetingParticipantController::class)
        ->only(['index', 'store', 'destroy'])
        ->scoped(['meeting' => 'id', 'participant' => 'id']);

    Route::apiResource('meetings.transcripts', MeetingTranscriptController::class)
        ->except('show')
        ->scoped(['meeting' => 'id', 'transcript' => 'id']);

    Route::apiResource('meetings.notes', MeetingNoteController::class)
        ->except('show')
        ->scoped(['meeting' => 'id', 'note' => 'id']);
});
