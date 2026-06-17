<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\ProductServiceInterface;
use App\Enums\CatalogueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogue\StoreProductRequest;
use App\Http\Requests\Catalogue\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        $products = $this->service->paginate($perPage, $search, $status);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'options' => [
                'statuses' => CatalogueStatus::options(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated());

        return response()->json(new ProductResource($product), 201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource($this->service->update($product, $request->validated()));
    }

    public function destroy(Product $product): Response
    {
        $this->service->archive($product);

        return response()->noContent();
    }
}
