<?php

namespace App\Http\Resources;

use App\Enums\CatalogueStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof CatalogueStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'monthly_fee' => $this->monthly_fee,
            'included_hours' => $this->included_hours,
            'response_sla_hours' => $this->response_sla_hours,
            'response_sla_label' => $this->response_sla_hours ? "{$this->response_sla_hours} hours" : null,
            'service_review' => $this->service_review,
            'best_for' => $this->best_for,
            'currency' => $this->currency,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
