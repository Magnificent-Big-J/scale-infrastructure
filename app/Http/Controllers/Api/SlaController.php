<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SlaServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTicketResource;
use Illuminate\Http\JsonResponse;

class SlaController extends Controller
{
    public function __construct(private readonly SlaServiceInterface $service) {}

    public function index(): JsonResponse
    {
        $overview = $this->service->overview();

        return response()->json([
            'data' => SupportTicketResource::collection($overview['tickets']),
            'summary' => $overview['summary'],
        ]);
    }
}
