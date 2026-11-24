<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\SaveMeetingNoteAction;
use App\Enums\MeetingNoteType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\StoreMeetingNoteRequest;
use App\Http\Requests\V1\Meeting\UpdateMeetingNoteRequest;
use App\Http\Resources\V1\MeetingNoteResource;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The "Note" and "Comments" tabs: /meetings/{meeting}/notes/{note}?type=note|comment
 */
class MeetingNoteController extends Controller
{
    public function index(Request $request, Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        $request->validate(['type' => ['nullable', Rule::enum(MeetingNoteType::class)]]);

        $notes = $meeting->notes()
            ->with('author')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->orderBy('id')
            ->get()
            ->each(fn (MeetingNote $note) => $note->setRelation('meeting', $meeting));

        return ApiResponse::success(MeetingNoteResource::collection($notes));
    }

    public function store(StoreMeetingNoteRequest $request, Meeting $meeting, #[CurrentUser] User $user, SaveMeetingNoteAction $action): JsonResponse
    {
        $note = $action->create(
            $meeting,
            $request->enum('type', MeetingNoteType::class) ?? MeetingNoteType::Note,
            $request->string('body')->toString(),
            $user,
        );

        return ApiResponse::created(new MeetingNoteResource($note->setRelation('meeting', $meeting)->load('author')), __('Saved.'));
    }

    public function update(UpdateMeetingNoteRequest $request, Meeting $meeting, MeetingNote $note, SaveMeetingNoteAction $action): JsonResponse
    {
        $note = $action->update($note, $request->string('body')->toString());

        return ApiResponse::success(new MeetingNoteResource($note->load('author')), __('Updated.'));
    }

    public function destroy(Meeting $meeting, MeetingNote $note): JsonResponse
    {
        Gate::authorize('delete', $note);

        $note->delete();

        return ApiResponse::message(__('Deleted.'));
    }
}
