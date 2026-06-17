<?php

namespace App\Services;

use App\Contracts\ProductServiceInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService implements ProductServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        return Product::query()
            ->withCount('packages')
            ->when($search, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->when($status, fn ($query, $value) => $query->where('status', $value))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->create($data);

            $this->log($product, 'created', 'Created product');

            return $product->loadCount('packages');
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->fill($data);
            $product->save();

            $this->log($product, 'updated', 'Updated product', ['changes' => array_keys($product->getChanges())]);

            return $product->refresh()->loadCount('packages');
        });
    }

    public function archive(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->delete();

            $this->log($product, 'archived', 'Archived product');
        });
    }

    private function log(Product $product, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('products')
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
