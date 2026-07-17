<?php

namespace App\Http\Resources;

use App\Enums\ClientStatus;
use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ClientStatus ? $this->status : null;

        return [
            'id' => $this->id,
            'package_id' => $this->package_id,
            'package_name' => $this->whenLoaded('package', fn () => $this->package?->name),
            'product_name' => $this->whenLoaded('package', fn () => $this->package?->product?->name),
            'owner_user_id' => $this->owner_user_id,
            'owner_name' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'code' => $this->code,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tier' => $this->tier,
            'tier_label' => $this->tier ? (LookupOption::labelMapFor(LookupType::ClientTier)[$this->tier] ?? $this->tier) : null,
            'tier_color' => $this->tier ? (LookupOption::metadataMapFor(LookupType::ClientTier, 'color')[$this->tier] ?? null) : null,
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color(),
            'health_score' => $this->health_score,
            'notes' => $this->notes,
            'contacts_count' => $this->whenCounted('contacts'),
            'primary_contact' => new ContactResource($this->whenLoaded('primaryContact')),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
