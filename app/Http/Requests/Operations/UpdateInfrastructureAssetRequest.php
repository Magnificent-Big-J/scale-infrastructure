<?php

namespace App\Http\Requests\Operations;

use App\Enums\LookupType;
use Illuminate\Foundation\Http\FormRequest;

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
            'type' => ['required', 'string', LookupType::InfrastructureAssetType->existsRule()],
            'provider' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            // sometimes: a viewer without profitability.view never receives
            // the current monthly_cost (InfrastructureAssetResource redacts
            // it), so the field must be omittable rather than defaulting to
            // null on every save - otherwise their next edit silently wipes
            // a cost figure they were never shown in the first place.
            'monthly_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'public_ip' => ['nullable', 'string', 'max:64'],
            'private_ip' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
