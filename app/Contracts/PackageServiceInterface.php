<?php

namespace App\Contracts;

use App\Models\Package;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PackageServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null, ?Product $product = null): LengthAwarePaginator;

    public function create(array $data, ?Product $product = null): Package;

    public function update(Package $package, array $data): Package;

    public function archive(Package $package): void;
}
