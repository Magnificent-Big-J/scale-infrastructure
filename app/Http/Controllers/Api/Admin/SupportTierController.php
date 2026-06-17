<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\SupportTierServiceInterface;
use App\Enums\CatalogueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogue\StoreSupportTierRequest;
use App\Http\Requests\Catalogue\UpdateSupportTierRequest;
use App\Http\Resources\SupportTierResource;
use App\Models\SupportTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SupportTierController extends Controller
{
    public function __construct(
        private readonly SupportTierServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        $supportTiers = $this->service->paginate($perPage, $search, $status);

        return response()->json([
            'data' => SupportTierResource::collection($supportTiers->items()),
            'meta' => [
                'current_page' => $supportTiers->currentPage(),
                'last_page' => $supportTiers->lastPage(),
                'per_page' => $supportTiers->perPage(),
                'total' => $supportTiers->total(),
            ],
            'options' => [
                'statuses' => CatalogueStatus::options(),
                'default_currency' => config('catalogue.default_currency'),
            ],
        ]);
    }

    public function store(StoreSupportTierRequest $request): JsonResponse
    {
        $supportTier = $this->service->create($request->validated());

        return response()->json(new SupportTierResource($supportTier), 201);
    }

    public function update(UpdateSupportTierRequest $request, SupportTier $supportTier): SupportTierResource
    {
        return new SupportTierResource($this->service->update($supportTier, $request->validated()));
    }

    public function destroy(SupportTier $supportTier): Response
    {
        $this->service->archive($supportTier);

        return response()->noContent();
    }
}
