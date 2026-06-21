<?php

namespace App\Http\Requests\Lookup;

use App\Enums\LookupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLookupOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(LookupType::values())],
            'value' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('lookup_options', 'value')->where('type', $this->input('type')),
            ],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.regex' => 'The value may only contain lowercase letters, numbers, hyphens, and underscores.',
        ];
    }
}
