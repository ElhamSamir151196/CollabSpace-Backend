<?php

namespace App\Http\Requests\V1\Reports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * ?project_id=  ?from=2025-07-01  ?to=2025-12-31
 * Default period: this month and the 5 before it. Maximum: 2 years.
 * (Who may see which projects is checked by ReportScopeResolver.)
 */
class ReportFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to']) || ! $this->filled('from')) {
                    return;
                }

                $to = $this->filled('to') ? Carbon::parse($this->string('to')->toString()) : now();

                if (Carbon::parse($this->string('from')->toString())->diffInDays($to) > 731) {
                    $validator->errors()->add('from', __('The period can be at most 2 years.'));
                }
            },
        ];
    }

    public function projectId(): ?int
    {
        return $this->filled('project_id') ? $this->integer('project_id') : null;
    }

    public function from(): ?string
    {
        return $this->filled('from') ? $this->string('from')->toString() : null;
    }

    public function to(): ?string
    {
        return $this->filled('to') ? $this->string('to')->toString() : null;
    }
}
