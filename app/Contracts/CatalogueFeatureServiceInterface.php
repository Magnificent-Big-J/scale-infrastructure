<?php

namespace App\Contracts;

use App\Models\CatalogueFeature;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CatalogueFeatureServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null, ?Product $product = null): LengthAwarePaginator;

    public function create(array $data, ?Product $product = null): CatalogueFeature;

    public function update(CatalogueFeature $feature, array $data): CatalogueFeature;

    public function archive(CatalogueFeature $feature): void;
}
