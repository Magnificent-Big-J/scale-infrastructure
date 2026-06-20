<?php

namespace App\Http\Controllers\Api;

use App\Contracts\FinanceDashboardServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FinanceDashboardController extends Controller
{
    public function __construct(private readonly FinanceDashboardServiceInterface $service) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->service->metrics()]);
    }
}
