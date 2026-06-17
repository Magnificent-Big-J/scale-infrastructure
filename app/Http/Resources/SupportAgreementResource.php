<?php

namespace App\Http\Resources;

use App\Enums\SupportAgreementStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportAgreementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof SupportAgreementStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'support_tier_id' => $this->support_tier_id,
            'support_tier_name' => $this->whenLoaded('supportTier', fn () => $this->supportTier?->name),
            'code' => $this->code,
            'name' => $this->name,
            'monthly_fee' => $this->monthly_fee,
            'included_hours' => $this->included_hours,
            'response_sla_hours' => $this->response_sla_hours,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'notes' => $this->notes,
            'tickets_count' => $this->whenCounted('tickets'),
        ];
    }
}
