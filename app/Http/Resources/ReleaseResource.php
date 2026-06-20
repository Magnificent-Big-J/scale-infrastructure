<?php

namespace App\Http\Resources;

use App\Enums\ReleaseStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ReleaseStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'client_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->client?->name),
            'change_request_id' => $this->change_request_id,
            'change_request_reference' => $this->whenLoaded('changeRequest', fn () => $this->changeRequest?->reference),
            'version' => $this->version,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'notes' => $this->notes,
            'approved_by_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'deployed_by_name' => $this->whenLoaded('deployer', fn () => $this->deployer?->name),
            'deployed_at' => $this->deployed_at?->toIso8601String(),
            'rolled_back_at' => $this->rolled_back_at?->toIso8601String(),
            'rollback_notes' => $this->rollback_notes,
        ];
    }
}
