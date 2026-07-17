<?php

namespace App\Http\Resources;

use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'amount' => $this->amount,
            'method' => $this->method,
            'method_label' => $this->method ? (LookupOption::labelMapFor(LookupType::PaymentMethod)[$this->method] ?? $this->method) : null,
            'reference' => $this->reference,
            'paid_on' => $this->paid_on?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}
