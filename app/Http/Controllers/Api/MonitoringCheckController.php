<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeploymentServiceInterface;
use App\Enums\MonitoringCheckStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreMonitoringCheckRequest;
use App\Http\Resources\MonitoringCheckResource;
use App\Models\Deployment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringCheckController extends Controller
{
    public function __construct(
        private readonly DeploymentServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        $checks = $this->service->paginateMonitoringChecks($perPage, $search, $status);

        return response()->json([
            'data' => MonitoringCheckResource::collection($checks->items()),
            'meta' => [
                'current_page' => $checks->currentPage(),
                'last_page' => $checks->lastPage(),
                'per_page' => $checks->perPage(),
                'total' => $checks->total(),
            ],
            'options' => [
                'statuses' => MonitoringCheckStatus::options(),
                'check_types' => ['uptime', 'ssl', 'backup', 'sentry', 'server', 'database', 'redis', 'queue'],
                'deployments' => Deployment::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Deployment $deployment) => ['value' => $deployment->id, 'label' => $deployment->name])
                    ->all(),
            ],
        ]);
    }

    public function store(StoreMonitoringCheckRequest $request, Deployment $deployment): JsonResponse
    {
        $check = $this->service->createMonitoringCheck($deployment, $request->validated());

        return response()->json(new MonitoringCheckResource($check), 201);
    }
}
