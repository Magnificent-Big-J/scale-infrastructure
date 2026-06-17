<?php

namespace App\Http\Resources;

use App\Enums\CatalogueStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogueFeatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof CatalogueStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'minimum_package_id' => $this->minimum_package_id,
            'minimum_package_name' => $this->whenLoaded('minimumPackage', fn () => $this->minimumPackage?->name),
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
