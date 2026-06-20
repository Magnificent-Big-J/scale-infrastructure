<?php

namespace App\Http\Resources;

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRisk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ChangeRequestStatus ? $this->status : null;
        $risk = $this->risk instanceof ChangeRisk ? $this->risk : null;

        return [
            'id' => $this->id,
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'risk' => $risk?->value,
            'risk_label' => $risk?->label(),
            'risk_color' => $risk?->color(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'approved_by_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'scheduled_for' => $this->scheduled_for?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}
