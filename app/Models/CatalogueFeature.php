<?php

namespace App\Models;

use App\Enums\CatalogueStatus;
use App\Models\Concerns\HasCatalogueFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogueFeature extends Model
{
    use HasCatalogueFilters;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'minimum_package_id',
        'code',
        'name',
        'description',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => CatalogueStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function minimumPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'minimum_package_id');
    }

    public function scopeForProduct(Builder $query, ?Product $product): Builder
    {
        return $query->when($product, fn (Builder $query) => $query->where('product_id', $product->getKey()));
    }
}
