<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\CatalogueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'unique:support_tiers,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'included_hours' => ['nullable', 'integer', 'min:0'],
            'response_sla_hours' => ['nullable', 'integer', 'min:0'],
            'service_review' => ['nullable', 'string', 'max:255'],
            'best_for' => ['nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(CatalogueStatus::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
