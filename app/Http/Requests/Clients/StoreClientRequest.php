<?php

namespace App\Http\Requests\Clients;

use App\Enums\ClientStatus;
use App\Enums\LookupType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['nullable', 'uuid', 'exists:packages,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'code' => ['required', 'string', 'max:64', 'unique:clients,code'],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tier' => ['required', 'string', LookupType::ClientTier->existsRule()],
            'status' => ['required', Rule::in(ClientStatus::values())],
            'health_score' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'primary_contact' => ['nullable', 'array'],
            'primary_contact.name' => ['nullable', 'string', 'max:255'],
            'primary_contact.email' => ['nullable', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:64'],
            'primary_contact.role' => ['nullable', 'string', 'max:255'],
        ];
    }
}
