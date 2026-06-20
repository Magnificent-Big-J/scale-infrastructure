<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvisioningTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'provider' => $this->provider,
            'summary' => $this->summary,
            'steps' => $this->steps ?? [],
            'is_active' => $this->is_active,
            'automation_runs_count' => $this->whenCounted('automationRuns'),
        ];
    }
}
