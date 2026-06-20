<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReleaseOperationsServiceInterface;
use App\Enums\AutomationRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreAutomationRunRequest;
use App\Http\Resources\AutomationRunResource;
use App\Models\ChangeRequest;
use App\Models\ProvisioningTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationRunController extends Controller
{
    public function __construct(private readonly ReleaseOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $runs = $this->service->paginateAutomationRuns(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return response()->json([
            'data' => AutomationRunResource::collection($runs->items()),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
            'options' => [
                'statuses' => AutomationRunStatus::options(),
                'templates' => ProvisioningTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn (ProvisioningTemplate $template) => ['value' => $template->id, 'label' => $template->name])->all(),
                'change_requests' => ChangeRequest::query()->orderByDesc('created_at')->get(['id', 'reference', 'title'])->map(fn (ChangeRequest $cr) => ['value' => $cr->id, 'label' => "{$cr->reference} · {$cr->title}"])->all(),
            ],
        ]);
    }

    public function store(StoreAutomationRunRequest $request): JsonResponse
    {
        return response()->json(new AutomationRunResource($this->service->createAutomationRun($request->validated())), 201);
    }
}
