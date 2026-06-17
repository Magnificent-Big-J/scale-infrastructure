<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleDemoRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_key' => $this->page_key,
            'label' => $this->label,
            'headline' => $this->headline,
            'summary' => $this->summary,
            'status' => $this->status,
            'metrics' => $this->metrics ?? [],
            'sort_order' => $this->sort_order,
        ];
    }
}
