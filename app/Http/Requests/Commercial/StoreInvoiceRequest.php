<?php

namespace App\Http\Requests\Commercial;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'contract_id' => ['nullable', 'uuid', 'exists:contracts,id'],
            'number' => ['required', 'string', 'max:64', 'unique:invoices,number'],
            'status' => ['required', Rule::in(InvoiceStatus::values())],
            'amount' => ['required', 'numeric', 'min:0'],
            'issued_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'external_reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
