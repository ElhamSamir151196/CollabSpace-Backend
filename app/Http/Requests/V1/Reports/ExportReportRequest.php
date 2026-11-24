<?php

namespace App\Http\Requests\V1\Reports;

use App\Enums\ReportType;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * "Submit a detailed report": { type, note?, from?, to?, project_id?, user_id? }.
 *  - user reports: user_id (default: yourself) — needs "viewUserReport";
 *  - other types: admins and project managers ("viewReports").
 * Which projects are included is checked by ReportScopeResolver (403 otherwise).
 */
class ExportReportRequest extends ReportFiltersRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->input('type') === ReportType::User->value) {
            $subject = $this->subject();

            // Unknown user: let validation answer with 422.
            return $subject === null || $user->can('viewUserReport', $subject);
        }

        return $user->can('viewReports');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'type' => ['required', Rule::enum(ReportType::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }

    public function type(): ReportType
    {
        return ReportType::from($this->string('type')->toString());
    }

    /**
     * Whose report a "user" report is (yourself by default).
     */
    public function subject(): ?User
    {
        if (! $this->filled('user_id')) {
            return $this->user();
        }

        return User::query()->find($this->integer('user_id'));
    }

    public function note(): ?string
    {
        $note = trim($this->string('note')->toString());

        return $note !== '' ? $note : null;
    }
}
