<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'causer_name' => $this->whenLoaded('causer', fn () => $this->causer?->name),
            'subject_type' => $this->subject_type ? Str::headline(class_basename($this->subject_type)) : null,
            'subject_id' => $this->subject_id,
            'properties' => $this->properties,
            'created_at' => $this->created_at,
        ];
    }
}
