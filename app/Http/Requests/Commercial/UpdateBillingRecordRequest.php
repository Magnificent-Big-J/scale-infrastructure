<?php

namespace App\Http\Requests\Commercial;

use App\Enums\BillingCadence;
use App\Enums\LookupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'contract_id' => ['nullable', 'uuid', 'exists:contracts,id'],
            'type' => ['required', 'string', LookupType::BillingRecordType->existsRule()],
            'cadence' => ['required', Rule::in(BillingCadence::values())],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
