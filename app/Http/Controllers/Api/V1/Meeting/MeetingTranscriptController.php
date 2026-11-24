<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\AddTranscriptEntriesAction;
use App\Actions\Meeting\UpdateTranscriptEntryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\StoreTranscriptRequest;
use App\Http\Requests\V1\Meeting\UpdateTranscriptRequest;
use App\Http\Resources\V1\MeetingTranscriptResource;
use App\Models\Meeting;
use App\Models\MeetingTranscript;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The "Meeting Transcripts" panel: /meetings/{meeting}/transcripts/{transcript}.
 * Everyone who can see the meeting reads it; only the organizer writes it.
 */
class MeetingTranscriptController extends Controller
{
    /**
     * ?search= (inside the text or speaker name)  ?language=en
     */
    public function index(Request $request, Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'alpha_dash', 'max:10'],
        ]);

        $search = $request->string('search')->trim()->toString();
        $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';

        $lines = $meeting->transcripts()
            ->with('speaker')
            ->when($request->filled('language'), fn ($query) => $query->where('language', $request->string('language')->lower()->toString()))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereRaw("content LIKE ? ESCAPE '!'", [$escaped])
                ->orWhereRaw("speaker_name LIKE ? ESCAPE '!'", [$escaped])))
            ->orderBy('offset_seconds')
            ->orderBy('position')
            ->get();

        return ApiResponse::success(MeetingTranscriptResource::collection($lines), meta: [
            'languages' => $meeting->transcripts()->distinct()->orderBy('language')->pluck('language')->all(),
        ]);
    }

    public function store(StoreTranscriptRequest $request, Meeting $meeting, #[CurrentUser] User $user, AddTranscriptEntriesAction $action): JsonResponse
    {
        $lines = $action->handle($meeting, $request->entries(), $user);

        return ApiResponse::created(MeetingTranscriptResource::collection($lines->load('speaker')), __('Transcript saved.'));
    }

    public function update(UpdateTranscriptRequest $request, Meeting $meeting, MeetingTranscript $transcript, UpdateTranscriptEntryAction $action): JsonResponse
    {
        $line = $action->handle($transcript, $request->validated());

        return ApiResponse::success(new MeetingTranscriptResource($line->load('speaker')), __('Transcript line updated.'));
    }

    public function destroy(Meeting $meeting, MeetingTranscript $transcript): JsonResponse
    {
        Gate::authorize('manageContent', $meeting);

        $transcript->delete();

        return ApiResponse::message(__('Transcript line deleted.'));
    }
}
