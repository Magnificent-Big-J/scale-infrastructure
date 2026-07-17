<?php

namespace App\Http\Resources;

use App\Enums\BillingCadence;
use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cadence = $this->cadence instanceof BillingCadence ? $this->cadence : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'contract_id' => $this->contract_id,
            'contract_name' => $this->whenLoaded('contract', fn () => $this->contract?->name),
            'type' => $this->type,
            'type_label' => $this->type ? (LookupOption::labelMapFor(LookupType::BillingRecordType)[$this->type] ?? $this->type) : null,
            'cadence' => $cadence?->value,
            'cadence_label' => $cadence?->label(),
            'description' => $this->description,
            'amount' => $this->amount,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
