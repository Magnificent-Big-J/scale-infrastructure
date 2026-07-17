<?php

namespace App\Http\Resources;

use App\Enums\DeploymentStatus;
use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof DeploymentStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'package_id' => $this->package_id,
            'package_name' => $this->whenLoaded('package', fn () => $this->package?->name),
            'name' => $this->name,
            'environment' => $this->environment,
            'environment_label' => $this->environment ? (LookupOption::labelMapFor(LookupType::DeploymentEnvironment)[$this->environment] ?? $this->environment) : null,
            'domain' => $this->domain,
            'app_url' => $this->app_url,
            'current_version' => $this->current_version,
            'go_live_date' => $this->go_live_date?->toDateString(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'intake_token' => $this->intake_token,
            'notes' => $this->notes,
            'infrastructure_assets_count' => $this->whenCounted('infrastructureAssets'),
            'monitoring_checks_count' => $this->whenCounted('monitoringChecks'),
            'releases_count' => $this->whenCounted('releases'),
            'infrastructure_assets' => InfrastructureAssetResource::collection($this->whenLoaded('infrastructureAssets')),
            'monitoring_checks' => MonitoringCheckResource::collection($this->whenLoaded('monitoringChecks')),
            'releases' => ReleaseResource::collection($this->whenLoaded('releases')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
