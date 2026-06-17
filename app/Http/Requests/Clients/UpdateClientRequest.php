<?php

namespace App\Http\Requests\Clients;

use App\Enums\ClientStatus;
use App\Enums\ClientTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->getKey();

        return [
            'package_id' => ['nullable', 'uuid', 'exists:packages,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'code' => ['sometimes', 'string', 'max:64', Rule::unique('clients', 'code')->ignore($clientId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tier' => ['sometimes', Rule::in(ClientTier::values())],
            'status' => ['sometimes', Rule::in(ClientStatus::values())],
            'health_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'primary_contact' => ['nullable', 'array'],
            'primary_contact.name' => ['nullable', 'string', 'max:255'],
            'primary_contact.email' => ['nullable', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:64'],
            'primary_contact.role' => ['nullable', 'string', 'max:255'],
        ];
    }
}
