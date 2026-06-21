<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ActivityFeedServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(private readonly ActivityFeedServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));

        $activities = $this->service->paginate($perPage, [
            'log_name' => $request->string('log_name')->toString() ?: null,
            'subject_type' => $request->string('subject_type')->toString() ?: null,
            'subject_id' => $request->string('subject_id')->toString() ?: null,
            'causer_id' => $request->string('causer_id')->toString() ?: null,
        ]);

        return response()->json([
            'data' => ActivityResource::collection($activities->items()),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
