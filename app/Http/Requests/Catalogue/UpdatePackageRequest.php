<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\BillingInterval;
use App\Enums\CatalogueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $packageId = $this->route('package')?->getKey();

        return [
            'product_id' => ['sometimes', 'uuid', 'exists:products,id'],
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('packages', 'code')->ignore($packageId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_interval' => ['sometimes', Rule::in(BillingInterval::values())],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(CatalogueStatus::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
