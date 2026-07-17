<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\LookupOptionServiceInterface;
use App\Contracts\PackageServiceInterface;
use App\Enums\CatalogueStatus;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogue\StorePackageRequest;
use App\Http\Requests\Catalogue\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\LookupOption;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request, ?Product $product = null): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        if (! $product && ($productId = $request->string('product_id')->toString())) {
            $product = Product::query()->find($productId);
        }

        $packages = $this->service->paginate($perPage, $search, $status, $product);

        return response()->json([
            'data' => PackageResource::collection($packages->items()),
            'meta' => [
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'per_page' => $packages->perPage(),
                'total' => $packages->total(),
            ],
            'options' => [
                'statuses' => CatalogueStatus::options(),
                'billing_intervals' => $this->lookups->optionsFor(LookupType::BillingInterval)
                    ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                    ->all(),
                'default_currency' => config('catalogue.default_currency'),
                'products' => Product::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Product $item) => ['value' => $item->id, 'label' => $item->name])
                    ->all(),
            ],
        ]);
    }

    public function store(StorePackageRequest $request, ?Product $product = null): JsonResponse
    {
        $package = $this->service->create($request->validated(), $product);

        return response()->json(new PackageResource($package), 201);
    }

    public function update(UpdatePackageRequest $request, Package $package): PackageResource
    {
        return new PackageResource($this->service->update($package, $request->validated()));
    }

    public function destroy(Package $package): Response
    {
        $this->service->archive($package);

        return response()->noContent();
    }
}
