<?php

namespace App\Http\Resources;

use App\Enums\AutomationRunStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof AutomationRunStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'provisioning_template_id' => $this->provisioning_template_id,
            'template_name' => $this->whenLoaded('provisioningTemplate', fn () => $this->provisioningTemplate?->name),
            'deployment_id' => $this->deployment_id,
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'change_request_id' => $this->change_request_id,
            'change_request_reference' => $this->whenLoaded('changeRequest', fn () => $this->changeRequest?->reference),
            'reference' => $this->reference,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'output_summary' => $this->output_summary,
            'triggered_by_name' => $this->whenLoaded('triggeredBy', fn () => $this->triggeredBy?->name),
        ];
    }
}
