<?php

namespace App\Http\Resources;

use App\Enums\InfrastructureAssetType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfrastructureAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type instanceof InfrastructureAssetType ? $this->type : null;

        return [
            'id' => $this->id,
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'client_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->client?->name),
            'name' => $this->name,
            'type' => $type?->value,
            'type_label' => $type?->label(),
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
