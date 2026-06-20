<?php

namespace App\Http\Resources;

use App\Enums\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $method = $this->method instanceof PaymentMethod ? $this->method : null;

        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'amount' => $this->amount,
            'method' => $method?->value,
            'method_label' => $method?->label(),
            'reference' => $this->reference,
            'paid_on' => $this->paid_on?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}
