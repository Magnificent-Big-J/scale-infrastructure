<?php

namespace App\Http\Requests\Commercial;

use App\Enums\LookupType;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', LookupType::PaymentMethod->existsRule()],
            'reference' => ['nullable', 'string', 'max:128'],
            'paid_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
