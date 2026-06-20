<?php

namespace App\Http\Resources;

use App\Enums\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof InvoiceStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'contract_id' => $this->contract_id,
            'contract_name' => $this->whenLoaded('contract', fn () => $this->contract?->name),
            'number' => $this->number,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'amount' => $this->amount,
            'amount_paid' => $this->amount_paid,
            'outstanding' => $this->outstanding,
            'issued_on' => $this->issued_on?->toDateString(),
            'due_on' => $this->due_on?->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'external_reference' => $this->external_reference,
            'notes' => $this->notes,
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
