<?php

namespace App\Http\Resources;

use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfrastructureAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'client_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->client?->name),
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->type ? (LookupOption::labelMapFor(LookupType::InfrastructureAssetType)[$this->type] ?? $this->type) : null,
            'provider' => $this->provider,
            'region' => $this->region,
            'size' => $this->size,
            'monthly_cost' => $this->monthly_cost,
            'currency' => $this->currency,
            'public_ip' => $this->public_ip,
            'private_ip' => $this->private_ip,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
