<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SupportOperationsServiceInterface;
use App\Enums\IncidentStatus;
use App\Enums\SupportSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreIncidentRequest;
use App\Http\Requests\Support\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(private readonly SupportOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $incidents = $this->service->paginateIncidents(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('severity')->toString() ?: null,
        );

        return response()->json([
            'data' => IncidentResource::collection($incidents->items()),
            'meta' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'per_page' => $incidents->perPage(),
                'total' => $incidents->total(),
            ],
            'options' => [
                'statuses' => IncidentStatus::options(),
                'severities' => SupportSeverity::options(),
                'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
                'deployments' => Deployment::query()->orderBy('name')->get(['id', 'name'])->map(fn (Deployment $deployment) => ['value' => $deployment->id, 'label' => $deployment->name])->all(),
            ],
        ]);
    }

    public function store(StoreIncidentRequest $request): JsonResponse
    {
        return response()->json(new IncidentResource($this->service->createIncident($request->validated())), 201);
    }

    public function update(UpdateIncidentRequest $request, Incident $incident): JsonResponse
    {
        return response()->json(new IncidentResource($this->service->updateIncident($incident, $request->validated())));
    }
}
