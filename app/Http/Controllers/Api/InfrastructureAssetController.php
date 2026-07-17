<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeploymentServiceInterface;
use App\Contracts\LookupOptionServiceInterface;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreInfrastructureAssetRequest;
use App\Http\Requests\Operations\UpdateInfrastructureAssetRequest;
use App\Http\Resources\InfrastructureAssetResource;
use App\Models\Deployment;
use App\Models\InfrastructureAsset;
use App\Models\LookupOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfrastructureAssetController extends Controller
{
    public function __construct(
        private readonly DeploymentServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $type = $request->string('type')->toString() ?: null;

        $assets = $this->service->paginateInfrastructureAssets($perPage, $search, $type);

        return response()->json([
            'data' => InfrastructureAssetResource::collection($assets->items()),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
            'options' => [
                'types' => $this->lookups->optionsFor(LookupType::InfrastructureAssetType)
                    ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                    ->all(),
                'default_currency' => config('catalogue.default_currency'),
                'deployments' => Deployment::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Deployment $deployment) => ['value' => $deployment->id, 'label' => $deployment->name])
                    ->all(),
            ],
        ]);
    }

    public function store(StoreInfrastructureAssetRequest $request, Deployment $deployment): JsonResponse
    {
        $asset = $this->service->createInfrastructureAsset($deployment, $request->validated());

        return response()->json(new InfrastructureAssetResource($asset), 201);
    }

    public function update(UpdateInfrastructureAssetRequest $request, InfrastructureAsset $infrastructureAsset): JsonResponse
    {
        $asset = $this->service->updateInfrastructureAsset($infrastructureAsset, $request->validated());

        return response()->json(new InfrastructureAssetResource($asset));
    }
}
