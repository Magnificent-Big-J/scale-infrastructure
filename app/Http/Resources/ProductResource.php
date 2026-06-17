<?php

namespace App\Http\Resources;

use App\Enums\CatalogueStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof CatalogueStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'packages_count' => $this->whenCounted('packages'),
            'packages' => PackageResource::collection($this->whenLoaded('packages')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
