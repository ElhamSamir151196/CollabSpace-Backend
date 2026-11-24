<?php

namespace App\Http\Requests\V1\Meeting;

use App\DTOs\Meeting\TranscriptEntryData;
use App\Models\Meeting;
use App\Rules\ProjectMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * { entries: [{ speaker_id?, speaker_name?, offset: "00:40" | 40, content, language? }] }
 */
class StoreTranscriptRequest extends FormRequest
{
    /** "40", "00:40" or "1:02:05". */
    public const OFFSET_PATTERN = '/^(\d+|\d{1,2}:\d{2}(:\d{2})?)$/';

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manageContent', $this->meeting());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1', 'max:500'],
            'entries.*.speaker_id' => ['nullable', 'integer', new ProjectMember($this->meeting()->project)],
            'entries.*.speaker_name' => ['required_without:entries.*.speaker_id', 'nullable', 'string', 'max:150'],
            'entries.*.offset' => ['required', 'regex:'.self::OFFSET_PATTERN],
            'entries.*.content' => ['required', 'string', 'max:5000'],
            'entries.*.language' => ['nullable', 'string', 'alpha_dash', 'max:10'],
        ];
    }

    /**
     * @return list<TranscriptEntryData>
     */
    public function entries(): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->validated('entries');

        // validated() rebuilds the array from the rules, so the keys can come back
        // out of order (e.g. 0, 2, 1) when lines have different fields. Keep the sent order.
        ksort($rows);

        return array_values(array_map(TranscriptEntryData::fromArray(...), $rows));
    }

    public function meeting(): Meeting
    {
        /** @var Meeting */
        return $this->route('meeting');
    }
}
