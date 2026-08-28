<?php

namespace App\Http\Controllers\Api;

use App\Contracts\IncidentCommentServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreIncidentCommentRequest;
use App\Http\Resources\IncidentCommentResource;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;

class IncidentCommentController extends Controller
{
    public function __construct(private readonly IncidentCommentServiceInterface $service) {}

    public function index(Incident $incident): JsonResponse
    {
        return response()->json([
            'data' => IncidentCommentResource::collection($this->service->forIncident($incident)),
        ]);
    }

    public function store(StoreIncidentCommentRequest $request, Incident $incident): JsonResponse
    {
        $comment = $this->service->create($incident, $request->user(), $request->validated());

        return response()->json(['data' => new IncidentCommentResource($comment)], 201);
    }
}
