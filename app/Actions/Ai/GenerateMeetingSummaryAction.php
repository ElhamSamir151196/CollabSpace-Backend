<?php

namespace App\Actions\Ai;

use App\DTOs\Ai\ChatMessage;
use App\Enums\AiModule;
use App\Exceptions\BusinessException;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\MeetingSummary;
use App\Models\MeetingTranscript;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPrompts;
use Illuminate\Support\Str;

/**
 * "AI Generate Summary": summary, key points, decisions, action items,
 * risks and next steps, from the meeting's transcript, description and notes.
 * Generating again replaces the previous summary.
 */
class GenerateMeetingSummaryAction
{
    /** Keeps very long transcripts within the model's limits. */
    private const MAX_MATERIAL_CHARS = 60000;

    public function __construct(private readonly AiGateway $ai) {}

    public function handle(Meeting $meeting, User $user): MeetingSummary
    {
        $this->ai->ensureEnabled(AiModule::Meetings);

        $material = $this->material($meeting);

        if ($material === null) {
            throw new BusinessException(__('Add a transcript, notes or a description to the meeting before generating a summary.'));
        }

        $response = $this->ai->generate(
            $user,
            'meeting_summary',
            [AiPrompts::meetingSummary(), ChatMessage::user("<meeting_material>\n{$material}\n</meeting_material>")],
            AiPrompts::meetingSummarySchema(),
            AiModule::Meetings,
        );

        $data = $response->structured ?? [];

        return MeetingSummary::query()->updateOrCreate(['meeting_id' => $meeting->id], [
            'summary' => (string) ($data['summary'] ?? ''),
            'key_points' => $this->strings($data['key_points'] ?? []),
            'decisions' => $this->strings($data['decisions'] ?? []),
            'action_items' => collect(is_array($data['action_items'] ?? null) ? $data['action_items'] : [])
                ->filter(fn ($item) => is_array($item) && isset($item['task']))
                ->map(fn (array $item) => ['task' => (string) $item['task'], 'owner' => isset($item['owner']) ? (string) $item['owner'] : null])
                ->values()
                ->all(),
            'risks' => $this->strings($data['risks'] ?? []),
            'next_steps' => $this->strings($data['next_steps'] ?? []),
            'provider' => $response->provider,
            'model' => $response->model,
            'generated_by' => $user->id,
        ]);
    }

    /**
     * Title, time, participants, description, notes and the transcript as plain text.
     * Null when there is nothing to summarize.
     */
    private function material(Meeting $meeting): ?string
    {
        $meeting->loadMissing(['project', 'participants']);
        $transcript = $meeting->transcripts()->orderBy('offset_seconds')->orderBy('position')->get();
        $notes = $meeting->notes()->with('author')->orderBy('id')->get();

        if ($transcript->isEmpty() && $notes->isEmpty() && blank($meeting->description)) {
            return null;
        }

        $lines = [
            "Meeting: {$meeting->title}",
            "Project: {$meeting->project->name}",
            'Date: '.$meeting->start_at->format('Y-m-d H:i'),
            'Participants: '.$meeting->participants->pluck('name')->implode(', '),
        ];

        if (filled($meeting->description)) {
            $lines[] = "\nAgenda / goal:\n{$meeting->description}";
        }

        if ($notes->isNotEmpty()) {
            $lines[] = "\nNotes and comments:";
            $lines = [...$lines, ...$notes->map(fn (MeetingNote $note) => '- '.($note->author->name ?? 'Someone').": {$note->body}")->all()];
        }

        if ($transcript->isNotEmpty()) {
            $lines[] = "\nTranscript:";
            $lines = [...$lines, ...$transcript->map(fn (MeetingTranscript $line) => "[{$line->timestamp()}] {$line->speaker_name}: {$line->content}")->all()];
        }

        return Str::limit(implode("\n", $lines), self::MAX_MATERIAL_CHARS);
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->values()
            ->all();
    }
}
