<?php

namespace App\Http\Resources;

use App\Enums\ClientStatus;
use App\Enums\ClientTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ClientStatus ? $this->status : null;
        $tier = $this->tier instanceof ClientTier ? $this->tier : null;

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
            'tier' => $tier?->value,
            'tier_label' => $tier?->label(),
            'tier_color' => $tier?->color(),
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
