<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ExecutiveDashboardServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExecutiveDashboardController extends Controller
{
    public function __construct(private readonly ExecutiveDashboardServiceInterface $service) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->service->metrics()]);
    }
}
