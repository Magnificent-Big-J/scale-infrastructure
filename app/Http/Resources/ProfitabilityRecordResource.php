<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitabilityRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'period' => $this->period,
            'revenue' => $this->revenue,
            'hosting_cost' => $this->hosting_cost,
            'labour_cost' => $this->labour_cost,
            'monitoring_cost' => $this->monitoring_cost,
            'other_cost' => $this->other_cost,
            'total_cost' => number_format($this->totalCost(), 2, '.', ''),
            'profit' => $this->profit,
            'margin' => $this->margin,
            'notes' => $this->notes,
        ];
    }
}
