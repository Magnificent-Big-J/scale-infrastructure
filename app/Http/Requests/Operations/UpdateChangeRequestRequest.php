<?php

namespace App\Http\Requests\Operations;

use App\Enums\LookupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'deployment_id' => ['nullable', 'uuid', 'exists:deployments,id'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'reference' => ['required', 'string', 'max:64', Rule::unique('change_requests', 'reference')->ignore($this->route('changeRequest'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'risk' => ['required', 'string', LookupType::ChangeRisk->existsRule()],
            'scheduled_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
