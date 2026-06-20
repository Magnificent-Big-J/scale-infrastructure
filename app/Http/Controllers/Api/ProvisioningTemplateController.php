<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ReleaseOperationsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreProvisioningTemplateRequest;
use App\Http\Requests\Operations\UpdateProvisioningTemplateRequest;
use App\Http\Resources\ProvisioningTemplateResource;
use App\Models\ProvisioningTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvisioningTemplateController extends Controller
{
    public function __construct(private readonly ReleaseOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $templates = $this->service->paginateTemplates(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
        );

        return response()->json([
            'data' => ProvisioningTemplateResource::collection($templates->items()),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function store(StoreProvisioningTemplateRequest $request): JsonResponse
    {
        return response()->json(new ProvisioningTemplateResource($this->service->createTemplate($request->validated())), 201);
    }

    public function update(UpdateProvisioningTemplateRequest $request, ProvisioningTemplate $provisioningTemplate): JsonResponse
    {
        return response()->json(new ProvisioningTemplateResource($this->service->updateTemplate($provisioningTemplate, $request->validated())));
    }
}
