<?php

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'deployment_id' => ['required', 'uuid', 'exists:deployments,id'],
            'change_request_id' => ['nullable', 'uuid', 'exists:change_requests,id'],
            'version' => [
                'required', 'string', 'max:64',
                Rule::unique('releases')
                    ->where(fn ($query) => $query->where('deployment_id', $this->input('deployment_id')))
                    ->ignore($this->route('release')),
            ],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
