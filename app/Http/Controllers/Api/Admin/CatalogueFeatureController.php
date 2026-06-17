<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\CatalogueFeatureServiceInterface;
use App\Enums\CatalogueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogue\StoreCatalogueFeatureRequest;
use App\Http\Requests\Catalogue\UpdateCatalogueFeatureRequest;
use App\Http\Resources\CatalogueFeatureResource;
use App\Models\CatalogueFeature;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CatalogueFeatureController extends Controller
{
    public function __construct(
        private readonly CatalogueFeatureServiceInterface $service
    ) {}

    public function index(Request $request, ?Product $product = null): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        if (! $product && ($productId = $request->string('product_id')->toString())) {
            $product = Product::query()->find($productId);
        }

        $features = $this->service->paginate($perPage, $search, $status, $product);

        return response()->json([
            'data' => CatalogueFeatureResource::collection($features->items()),
            'meta' => [
                'current_page' => $features->currentPage(),
                'last_page' => $features->lastPage(),
                'per_page' => $features->perPage(),
                'total' => $features->total(),
            ],
            'options' => [
                'statuses' => CatalogueStatus::options(),
                'products' => Product::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Product $item) => ['value' => $item->id, 'label' => $item->name])
                    ->all(),
                'packages' => Package::query()
                    ->orderBy('product_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'product_id', 'name'])
                    ->map(fn (Package $item) => [
                        'value' => $item->id,
                        'label' => $item->name,
                        'product_id' => $item->product_id,
                    ])
                    ->all(),
            ],
        ]);
    }

    public function store(StoreCatalogueFeatureRequest $request, ?Product $product = null): JsonResponse
    {
        $feature = $this->service->create($request->validated(), $product);

        return response()->json(new CatalogueFeatureResource($feature), 201);
    }

    public function update(UpdateCatalogueFeatureRequest $request, CatalogueFeature $catalogueFeature): CatalogueFeatureResource
    {
        return new CatalogueFeatureResource($this->service->update($catalogueFeature, $request->validated()));
    }

    public function destroy(CatalogueFeature $catalogueFeature): Response
    {
        $this->service->archive($catalogueFeature);

        return response()->noContent();
    }
}
