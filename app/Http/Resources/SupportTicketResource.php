<?php

namespace App\Http\Resources;

use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof SupportTicketStatus ? $this->status : null;
        $severity = $this->severity instanceof SupportSeverity ? $this->severity : null;

        return [
            'id' => $this->id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'deployment_name' => $this->whenLoaded('deployment', fn () => $this->deployment?->name),
            'agreement_name' => $this->whenLoaded('supportAgreement', fn () => $this->supportAgreement?->name),
            'assigned_user_name' => $this->whenLoaded('assignedUser', fn () => $this->assignedUser?->name),
            'reference' => $this->reference,
            'subject' => $this->subject,
            'category' => $this->category,
            'severity' => $severity?->value,
            'severity_label' => $severity?->label(),
            'severity_color' => $severity?->color(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'hours_logged' => $this->hours_logged,
            'opened_at' => $this->opened_at,
            'resolved_at' => $this->resolved_at,
            'summary' => $this->summary,
        ];
    }
}
