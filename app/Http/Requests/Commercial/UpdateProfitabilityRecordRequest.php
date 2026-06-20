<?php

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfitabilityRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'period' => [
                'required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
                Rule::unique('profitability_records')
                    ->where(fn ($query) => $query->where('client_id', $this->input('client_id')))
                    ->ignore($this->route('profitabilityRecord')),
            ],
            'revenue' => ['required', 'numeric', 'min:0'],
            'hosting_cost' => ['nullable', 'numeric', 'min:0'],
            'labour_cost' => ['nullable', 'numeric', 'min:0'],
            'monitoring_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
