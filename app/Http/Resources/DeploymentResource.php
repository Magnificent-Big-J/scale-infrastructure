<?php

namespace App\Http\Resources;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $environment = $this->environment instanceof DeploymentEnvironment ? $this->environment : null;
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
            'environment' => $environment?->value,
            'environment_label' => $environment?->label(),
            'domain' => $this->domain,
            'app_url' => $this->app_url,
            'current_version' => $this->current_version,
            'go_live_date' => $this->go_live_date?->toDateString(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
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
