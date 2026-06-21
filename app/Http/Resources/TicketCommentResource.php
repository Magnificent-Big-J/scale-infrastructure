<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TicketCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authorName = $this->whenLoaded('author', fn () => $this->author?->name);

        return [
            'id' => $this->id,
            'support_ticket_id' => $this->support_ticket_id,
            'body' => $this->body,
            'is_internal' => $this->is_internal,
            'author_name' => $authorName,
            'author_initials' => $authorName ? Str::of($authorName)->explode(' ')->filter()->take(2)->map(fn ($part) => Str::substr($part, 0, 1))->implode('') : null,
            'created_at' => $this->created_at,
        ];
    }
}
