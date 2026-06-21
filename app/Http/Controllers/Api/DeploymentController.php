<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeploymentServiceInterface;
use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreDeploymentRequest;
use App\Http\Requests\Operations\UpdateDeploymentRequest;
use App\Http\Resources\DeploymentResource;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeploymentController extends Controller
{
    public function __construct(
        private readonly DeploymentServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $environment = $request->string('environment')->toString() ?: null;
        $clientId = $request->string('client_id')->toString() ?: null;

        $deployments = $this->service->paginateDeployments($perPage, $search, $status, $environment, $clientId);

        return response()->json([
            'data' => DeploymentResource::collection($deployments->items()),
            'meta' => [
                'current_page' => $deployments->currentPage(),
                'last_page' => $deployments->lastPage(),
                'per_page' => $deployments->perPage(),
                'total' => $deployments->total(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function show(Deployment $deployment): JsonResponse
    {
        $deployment->load(['client', 'product', 'package', 'infrastructureAssets', 'monitoringChecks', 'releases'])
            ->loadCount(['infrastructureAssets', 'monitoringChecks', 'releases']);

        return response()->json(['data' => new DeploymentResource($deployment)]);
    }

    public function generateIntakeToken(Deployment $deployment): JsonResponse
    {
        $token = $deployment->regenerateIntakeToken();

        return response()->json(['data' => ['intake_token' => $token]]);
    }

    public function revokeIntakeToken(Deployment $deployment): JsonResponse
    {
        $deployment->forceFill(['intake_token' => null])->save();

        return response()->json(['data' => ['intake_token' => null]]);
    }

    public function store(StoreDeploymentRequest $request): JsonResponse
    {
        $deployment = $this->service->createDeployment($request->validated());

        return response()->json(new DeploymentResource($deployment), 201);
    }

    public function update(UpdateDeploymentRequest $request, Deployment $deployment): DeploymentResource
    {
        return new DeploymentResource($this->service->updateDeployment($deployment, $request->validated()));
    }

    public function destroy(Deployment $deployment): Response
    {
        $this->service->archiveDeployment($deployment);

        return response()->noContent();
    }

    private function options(): array
    {
        return [
            'statuses' => DeploymentStatus::options(),
            'environments' => DeploymentEnvironment::options(),
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])
                ->all(),
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Product $product) => ['value' => $product->id, 'label' => $product->name])
                ->all(),
            'packages' => Package::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'product_id', 'name'])
                ->map(fn (Package $package) => [
                    'value' => $package->id,
                    'label' => $package->name,
                    'product_id' => $package->product_id,
                ])
                ->all(),
        ];
    }
}
