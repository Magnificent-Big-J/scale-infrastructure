<?php

namespace App\Http\Requests\Operations;

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRisk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChangeRequestRequest extends FormRequest
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
            'reference' => ['required', 'string', 'max:64', 'unique:change_requests,reference'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'risk' => ['required', Rule::in(ChangeRisk::values())],
            'status' => ['nullable', Rule::in(ChangeRequestStatus::values())],
            'scheduled_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
