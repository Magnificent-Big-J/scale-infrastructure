<?php

namespace App\Http\Resources;

use App\Enums\ContractStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ContractStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn () => $this->client?->name),
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'package_id' => $this->package_id,
            'package_name' => $this->whenLoaded('package', fn () => $this->package?->name),
            'code' => $this->code,
            'name' => $this->name,
            'total_value' => $this->total_value,
            'monthly_value' => $this->monthly_value,
            'starts_on' => $this->starts_on?->toDateString(),
            'renewal_date' => $this->renewal_date?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'notes' => $this->notes,
            'billing_records_count' => $this->whenCounted('billingRecords'),
            'invoices_count' => $this->whenCounted('invoices'),
            'billing_records' => BillingRecordResource::collection($this->whenLoaded('billingRecords')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
        ];
    }
}
