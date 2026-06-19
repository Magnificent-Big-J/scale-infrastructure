<?php

namespace App\Http\Requests\Support;

use App\Enums\IncidentStatus;
use App\Enums\SupportSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'deployment_id' => ['nullable', 'uuid', 'exists:deployments,id'],
            'reference' => ['required', 'string', 'max:64', Rule::unique('incidents', 'reference')->ignore($this->route('incident'))],
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::in(SupportSeverity::values())],
            'status' => ['required', Rule::in(IncidentStatus::values())],
            'started_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'root_cause' => ['nullable', 'string', 'max:3000'],
            'resolution_summary' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
