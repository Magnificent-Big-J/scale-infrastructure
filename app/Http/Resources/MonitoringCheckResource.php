<?php

namespace App\Http\Resources;

use App\Enums\MonitoringCheckStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof MonitoringCheckStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'client_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->client?->name),
            'name' => $this->name,
            'check_type' => $this->check_type,
            'target' => $this->target,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'last_checked_at' => $this->last_checked_at,
            'last_success_at' => $this->last_success_at,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
