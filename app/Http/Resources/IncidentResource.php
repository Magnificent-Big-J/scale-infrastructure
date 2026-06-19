<?php

namespace App\Http\Resources;

use App\Enums\IncidentStatus;
use App\Enums\SupportSeverity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof IncidentStatus ? $this->status : null;
        $severity = $this->severity instanceof SupportSeverity ? $this->severity : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'reference' => $this->reference,
            'title' => $this->title,
            'severity' => $severity?->value,
            'severity_label' => $severity?->label(),
            'severity_color' => $severity?->color(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'started_at' => $this->started_at,
            'resolved_at' => $this->resolved_at,
            'root_cause' => $this->root_cause,
            'resolution_summary' => $this->resolution_summary,
        ];
    }
}
