<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\CatalogueStatus;
use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCatalogueFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'minimum_package_id' => ['nullable', 'uuid', 'exists:packages,id'],
            'code' => ['required', 'string', 'max:64', 'unique:catalogue_features,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(CatalogueStatus::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];

        if (! $this->route('product')) {
            $rules['product_id'] = ['required', 'uuid', 'exists:products,id'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $productId = $this->route('product')?->getKey() ?? $this->input('product_id');
                $packageId = $this->input('minimum_package_id');

                if (! $productId || ! $packageId) {
                    return;
                }

                $package = Package::query()->find($packageId);

                if ($package && $package->product_id !== $productId) {
                    $validator->errors()->add('minimum_package_id', 'The minimum package must belong to the selected product.');
                }
            },
        ];
    }
}
