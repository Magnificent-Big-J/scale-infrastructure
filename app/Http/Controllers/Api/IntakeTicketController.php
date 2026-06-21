<?php

namespace App\Http\Controllers\Api;

use App\Contracts\IntakeServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreIntakeTicketRequest;
use App\Models\Deployment;
use Illuminate\Http\JsonResponse;

class IntakeTicketController extends Controller
{
    public function __construct(private readonly IntakeServiceInterface $service) {}

    public function store(StoreIntakeTicketRequest $request): JsonResponse
    {
        /** @var Deployment $deployment */
        $deployment = $request->attributes->get('intake_deployment');

        $ticket = $this->service->createTicket($deployment, $request->validated());

        return response()->json([
            'data' => [
                'reference' => $ticket->reference,
                'status' => $ticket->status->value,
                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }
}
