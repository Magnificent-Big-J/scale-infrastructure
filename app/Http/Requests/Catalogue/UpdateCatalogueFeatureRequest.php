<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\CatalogueStatus;
use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCatalogueFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $featureId = $this->route('catalogueFeature')?->getKey();

        return [
            'product_id' => ['sometimes', 'uuid', 'exists:products,id'],
            'minimum_package_id' => ['nullable', 'uuid', 'exists:packages,id'],
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('catalogue_features', 'code')->ignore($featureId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(CatalogueStatus::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $feature = $this->route('catalogueFeature');
                $productId = $this->input('product_id', $feature?->product_id);
                $packageId = $this->has('minimum_package_id')
                    ? $this->input('minimum_package_id')
                    : $feature?->minimum_package_id;

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
