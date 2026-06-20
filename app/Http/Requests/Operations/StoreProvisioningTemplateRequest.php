<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

class StoreProvisioningTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'unique:provisioning_templates,code'],
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:128'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'steps' => ['nullable', 'array'],
            'steps.*' => ['string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
