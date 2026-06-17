<?php

namespace App\Http\Requests\Operations;

use App\Enums\MonitoringCheckStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoringCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'check_type' => ['required', 'string', 'max:64'],
            'target' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(MonitoringCheckStatus::values())],
            'last_checked_at' => ['nullable', 'date'],
            'last_success_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
