<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\BillingInterval;
use App\Enums\CatalogueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:64', 'unique:packages,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_interval' => ['required', Rule::in(BillingInterval::values())],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(CatalogueStatus::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];

        // product_id is required only when the package is not created under a nested product route.
        if (! $this->route('product')) {
            $rules['product_id'] = ['required', 'uuid', 'exists:products,id'];
        }

        return $rules;
    }
}
