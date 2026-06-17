<?php

namespace App\Http\Requests\Operations;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'package_id' => ['nullable', 'uuid', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['required', Rule::in(DeploymentEnvironment::values())],
            'domain' => ['nullable', 'string', 'max:255'],
            'app_url' => ['nullable', 'url', 'max:255'],
            'current_version' => ['nullable', 'string', 'max:64'],
            'go_live_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(DeploymentStatus::values())],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
