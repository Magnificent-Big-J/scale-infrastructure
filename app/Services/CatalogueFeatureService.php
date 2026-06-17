<?php

namespace App\Services;

use App\Contracts\CatalogueFeatureServiceInterface;
use App\Models\CatalogueFeature;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CatalogueFeatureService implements CatalogueFeatureServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $status = null, ?Product $product = null): LengthAwarePaginator
    {
        return CatalogueFeature::query()
            ->with(['product', 'minimumPackage'])
            ->forProduct($product)
            ->search($search)
            ->withStatus($status)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data, ?Product $product = null): CatalogueFeature
    {
        return DB::transaction(function () use ($data, $product) {
            if ($product) {
                $data['product_id'] = $product->getKey();
            }

            $feature = CatalogueFeature::query()->create($data);

            $this->log($feature, 'created', 'Created catalogue feature');

            return $feature->load(['product', 'minimumPackage']);
        });
    }

    public function update(CatalogueFeature $feature, array $data): CatalogueFeature
    {
        return DB::transaction(function () use ($feature, $data) {
            $feature->fill($data);
            $feature->save();

            $this->log($feature, 'updated', 'Updated catalogue feature', ['changes' => array_keys($feature->getChanges())]);

            return $feature->refresh()->load(['product', 'minimumPackage']);
        });
    }

    public function archive(CatalogueFeature $feature): void
    {
        DB::transaction(function () use ($feature) {
            $feature->delete();

            $this->log($feature, 'archived', 'Archived catalogue feature');
        });
    }

    private function log(CatalogueFeature $feature, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity($feature->getTable())
            ->performedOn($feature)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
