<?php

namespace App\Http\Requests\Commercial;

use App\Enums\ContractStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'package_id' => ['nullable', 'uuid', 'exists:packages,id'],
            'code' => ['required', 'string', 'max:64', 'unique:contracts,code'],
            'name' => ['required', 'string', 'max:255'],
            'total_value' => ['nullable', 'numeric', 'min:0'],
            'monthly_value' => ['nullable', 'numeric', 'min:0'],
            'starts_on' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::in(ContractStatus::values())],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
