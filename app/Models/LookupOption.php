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

    /**
     * value => label for every option in a list, memoized per request so
     * resources formatting many rows of the same lookup type (e.g. a list of
     * deployments) issue one query total instead of one per row.
     *
     * @return array<string, string>
     */
    public static function labelMapFor(LookupType $type): array
    {
        return self::$labelCache[$type->value] ??= self::query()->ofType($type)->pluck('label', 'value')->all();
    }

    /**
     * value => metadata[$key] for every option in a list. Used for the small
     * set of lists (e.g. client tier, change risk) that carry a display color
     * hint in `metadata` so converting them from a hardcoded enum doesn't lose
     * their badge coloring.
     *
     * @return array<string, mixed>
     */
    public static function metadataMapFor(LookupType $type, string $key): array
    {
        return self::$metadataCache[$type->value][$key] ??= self::query()
            ->ofType($type)
            ->get(['value', 'metadata'])
            ->mapWithKeys(fn (self $option) => [$option->value => $option->metadata[$key] ?? null])
            ->all();
    }

    private static array $labelCache = [];

    private static array $metadataCache = [];
}
