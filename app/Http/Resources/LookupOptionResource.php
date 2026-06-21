<?php

namespace App\Http\Resources;

use App\Enums\LookupType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LookupOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type instanceof LookupType ? $this->type : null;

        return [
            'id' => $this->id,
            'type' => $type?->value,
            'type_label' => $type?->label(),
            'value' => $this->value,
            'label' => $this->label,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
