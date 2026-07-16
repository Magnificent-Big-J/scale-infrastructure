<?php

namespace App\Http\Requests\Operations;

use App\Enums\InfrastructureAssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInfrastructureAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'deployment_id' => ['sometimes', 'uuid', 'exists:deployments,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(InfrastructureAssetType::values())],
            'provider' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'monthly_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'public_ip' => ['nullable', 'string', 'max:64'],
            'private_ip' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
