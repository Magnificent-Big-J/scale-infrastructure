<?php

namespace App\Http\Resources;

use App\Enums\CatalogueStatus;
use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof CatalogueStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'billing_interval' => $this->billing_interval,
            'billing_interval_label' => $this->billing_interval ? (LookupOption::labelMapFor(LookupType::BillingInterval)[$this->billing_interval] ?? $this->billing_interval) : null,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
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
