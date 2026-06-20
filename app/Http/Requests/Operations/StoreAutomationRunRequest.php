<?php

namespace App\Http\Requests\Operations;

use App\Enums\AutomationRunStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAutomationRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'provisioning_template_id' => ['nullable', 'uuid', 'exists:provisioning_templates,id'],
            'change_request_id' => ['required', 'uuid', 'exists:change_requests,id'],
            'deployment_id' => ['nullable', 'uuid', 'exists:deployments,id'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'reference' => ['required', 'string', 'max:64'],
            'status' => ['required', Rule::in(AutomationRunStatus::values())],
            'output_summary' => ['nullable', 'string', 'max:5000'],
            'finished_at' => ['nullable', 'date'],
        ];
    }
}
