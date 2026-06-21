<?php

namespace App\Http\Requests\Commercial;

use App\Enums\LookupType;
use App\Enums\OpportunityStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'required_without:prospect_name', 'uuid', 'exists:clients,id'],
            'prospect_name' => ['nullable', 'required_without:client_id', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'stage' => ['sometimes', Rule::in(OpportunityStage::values())],
            'value' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'source' => ['nullable', 'string', LookupType::OpportunitySource->existsRule()],
            'expected_close_date' => ['nullable', 'date'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
