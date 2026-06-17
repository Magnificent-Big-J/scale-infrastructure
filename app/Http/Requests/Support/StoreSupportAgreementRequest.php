<?php

namespace App\Http\Requests\Support;

use App\Enums\SupportAgreementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'support_tier_id' => ['nullable', 'uuid', 'exists:support_tiers,id'],
            'code' => ['required', 'string', 'max:64', 'unique:support_agreements,code'],
            'name' => ['required', 'string', 'max:255'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'included_hours' => ['nullable', 'integer', 'min:0'],
            'response_sla_hours' => ['nullable', 'integer', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::in(SupportAgreementStatus::values())],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
