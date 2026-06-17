<?php

namespace App\Services;

use App\Contracts\PackageServiceInterface;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PackageService implements PackageServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null, ?Product $product = null): LengthAwarePaginator
    {
        return Package::query()
            ->with('product')
            ->when($product, fn ($query, $value) => $query->where('product_id', $value->getKey()))
            ->when($search, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->when($status, fn ($query, $value) => $query->where('status', $value))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data, ?Product $product = null): Package
    {
        return DB::transaction(function () use ($data, $product) {
            if ($product) {
                $data['product_id'] = $product->getKey();
            }

            $package = Package::query()->create($data);

            $this->log($package, 'created', 'Created package');

            return $package->load('product');
        });
    }

    public function update(Package $package, array $data): Package
    {
        return DB::transaction(function () use ($package, $data) {
            $package->fill($data);
            $package->save();

            $this->log($package, 'updated', 'Updated package', ['changes' => array_keys($package->getChanges())]);

            return $package->refresh()->load('product');
        });
    }

    public function archive(Package $package): void
    {
        DB::transaction(function () use ($package) {
            $package->delete();

            $this->log($package, 'archived', 'Archived package');
        });
    }

    private function log(Package $package, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('packages')
            ->performedOn($package)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
