<?php

namespace App\Models;

use App\Enums\CatalogueStatus;
use App\Models\Concerns\HasCatalogueFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasCatalogueFilters;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'code',
        'name',
        'description',
        'billing_interval',
        'price_min',
        'price_max',
        'currency',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => CatalogueStatus::class,
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForProduct(Builder $query, ?Product $product): Builder
    {
        return $query->when($product, fn (Builder $query) => $query->where('product_id', $product->getKey()));
    }
}
