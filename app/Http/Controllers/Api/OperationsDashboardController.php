<?php

namespace App\Http\Controllers\Api;

use App\Contracts\OperationsDashboardServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OperationsDashboardController extends Controller
{
    public function __construct(private readonly OperationsDashboardServiceInterface $service) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->service->metrics()]);
    }
}
