<?php

namespace App\Http\Controllers\Api;

use App\Contracts\TicketCommentServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;

class TicketCommentController extends Controller
{
    public function __construct(private readonly TicketCommentServiceInterface $service) {}

    public function index(SupportTicket $supportTicket): JsonResponse
    {
        return response()->json([
            'data' => TicketCommentResource::collection($this->service->forTicket($supportTicket)),
        ]);
    }

    public function store(StoreTicketCommentRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $comment = $this->service->create($supportTicket, $request->user(), $request->validated());

        return response()->json(['data' => new TicketCommentResource($comment)], 201);
    }
}
