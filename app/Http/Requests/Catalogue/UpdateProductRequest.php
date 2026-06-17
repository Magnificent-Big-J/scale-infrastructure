<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\CatalogueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->getKey();

        return [
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('products', 'code')->ignore($productId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(CatalogueStatus::values())],
        ];
    }
}
