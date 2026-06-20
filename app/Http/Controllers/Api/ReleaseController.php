<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReleaseOperationsServiceInterface;
use App\Enums\ReleaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\RollbackReleaseRequest;
use App\Http\Requests\Operations\StoreReleaseRequest;
use App\Http\Requests\Operations\UpdateReleaseRequest;
use App\Http\Resources\ReleaseResource;
use App\Models\ChangeRequest;
use App\Models\Deployment;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function __construct(private readonly ReleaseOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $releases = $this->service->paginateReleases(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('deployment_id')->toString() ?: null,
        );

        return response()->json([
            'data' => ReleaseResource::collection($releases->items()),
            'meta' => [
                'current_page' => $releases->currentPage(),
                'last_page' => $releases->lastPage(),
                'per_page' => $releases->perPage(),
                'total' => $releases->total(),
            ],
            'options' => [
                'statuses' => ReleaseStatus::options(),
                'deployments' => Deployment::query()->orderBy('name')->get(['id', 'name'])->map(fn (Deployment $deployment) => ['value' => $deployment->id, 'label' => $deployment->name])->all(),
                'change_requests' => ChangeRequest::query()->orderByDesc('created_at')->get(['id', 'reference', 'title'])->map(fn (ChangeRequest $cr) => ['value' => $cr->id, 'label' => "{$cr->reference} · {$cr->title}"])->all(),
            ],
        ]);
    }

    public function store(StoreReleaseRequest $request): JsonResponse
    {
        return response()->json(new ReleaseResource($this->service->createRelease($request->validated())), 201);
    }

    public function update(UpdateReleaseRequest $request, Release $release): JsonResponse
    {
        return response()->json(new ReleaseResource($this->service->updateRelease($release, $request->validated())));
    }

    public function approve(Release $release): JsonResponse
    {
        return response()->json(new ReleaseResource($this->service->approveRelease($release)));
    }

    public function deploy(Release $release): JsonResponse
    {
        return response()->json(new ReleaseResource($this->service->deployRelease($release)));
    }

    public function rollback(RollbackReleaseRequest $request, Release $release): JsonResponse
    {
        return response()->json(new ReleaseResource($this->service->rollbackRelease($release, $request->validated()['rollback_notes'] ?? null)));
    }
}
