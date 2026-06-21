<?php

namespace App\Http\Resources;

use App\Enums\OpportunityStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stage = $this->stage instanceof OpportunityStage ? $this->stage : null;
        $clientName = $this->whenLoaded('client', fn () => $this->client?->name);

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $clientName,
            'prospect_name' => $this->prospect_name,
            'display_name' => $clientName ?: $this->prospect_name,
            'owner_id' => $this->owner_id,
            'owner_name' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'contract_id' => $this->contract_id,
            'contract_name' => $this->whenLoaded('contract', fn () => $this->contract?->name),
            'title' => $this->title,
            'description' => $this->description,
            'stage' => $stage?->value,
            'stage_label' => $stage?->label(),
            'stage_color' => $stage?->color(),
            'value' => $this->value,
            'probability' => $this->probability,
            'source' => $this->source,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'won_at' => $this->won_at,
            'lost_at' => $this->lost_at,
            'lost_reason' => $this->lost_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
