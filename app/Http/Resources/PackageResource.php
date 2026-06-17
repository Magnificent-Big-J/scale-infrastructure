<?php

namespace App\Http\Resources;

use App\Enums\BillingInterval;
use App\Enums\CatalogueStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof CatalogueStatus ? $this->status : null;
        $interval = $this->billing_interval instanceof BillingInterval ? $this->billing_interval : null;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'billing_interval' => $interval?->value,
            'billing_interval_label' => $interval?->label(),
            'price' => $this->price,
            'currency' => $this->currency,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
