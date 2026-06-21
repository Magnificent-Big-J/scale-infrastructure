<?php

namespace App\Http\Requests\Support;

use App\Enums\LookupType;
use App\Enums\SupportSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntakeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'severity' => ['sometimes', Rule::in(SupportSeverity::values())],
            'category' => ['nullable', 'string', LookupType::TicketCategory->existsRule()],
        ];
    }
}
