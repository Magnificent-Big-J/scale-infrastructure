<?php

namespace App\Models;

use App\Enums\LookupType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LookupOption extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'value',
        'label',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => LookupType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function scopeOfType(Builder $query, LookupType|string|null $type): Builder
    {
        return $query->when($type, fn (Builder $query) => $query->where('type', $type instanceof LookupType ? $type->value : $type));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query) use ($term) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('label', 'like', "%{$term}%")
                    ->orWhere('value', 'like', "%{$term}%");
            });
        });
    }
}
