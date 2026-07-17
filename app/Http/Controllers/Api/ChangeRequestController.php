<?php

namespace App\Http\Controllers\Api;

use App\Contracts\LookupOptionServiceInterface;
use App\Contracts\ReleaseOperationsServiceInterface;
use App\Enums\ChangeRequestStatus;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreChangeRequestRequest;
use App\Http\Requests\Operations\UpdateChangeRequestRequest;
use App\Http\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\LookupOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller
{
    public function __construct(
        private readonly ReleaseOperationsServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $changeRequests = $this->service->paginateChangeRequests(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return response()->json([
            'data' => ChangeRequestResource::collection($changeRequests->items()),
            'meta' => [
                'current_page' => $changeRequests->currentPage(),
                'last_page' => $changeRequests->lastPage(),
                'per_page' => $changeRequests->perPage(),
                'total' => $changeRequests->total(),
            ],
            'options' => [
                'statuses' => ChangeRequestStatus::options(),
                'risks' => $this->lookups->optionsFor(LookupType::ChangeRisk)
                    ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                    ->all(),
                'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
                'deployments' => Deployment::query()->orderBy('name')->get(['id', 'name'])->map(fn (Deployment $deployment) => ['value' => $deployment->id, 'label' => $deployment->name])->all(),
            ],
        ]);
    }

    public function store(StoreChangeRequestRequest $request): JsonResponse
    {
        return response()->json(new ChangeRequestResource($this->service->createChangeRequest($request->validated())), 201);
    }

    public function update(UpdateChangeRequestRequest $request, ChangeRequest $changeRequest): JsonResponse
    {
        return response()->json(new ChangeRequestResource($this->service->updateChangeRequest($changeRequest, $request->validated())));
    }

    public function approve(ChangeRequest $changeRequest): JsonResponse
    {
        return response()->json(new ChangeRequestResource($this->service->decideChangeRequest($changeRequest, true)));
    }

    public function reject(ChangeRequest $changeRequest): JsonResponse
    {
        return response()->json(new ChangeRequestResource($this->service->decideChangeRequest($changeRequest, false)));
    }
}
