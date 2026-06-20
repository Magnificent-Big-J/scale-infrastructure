<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SupportOperationsServiceInterface;
use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\UpdateSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function __construct(private readonly SupportOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = $this->service->paginateTickets(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('severity')->toString() ?: null,
        );

        return response()->json([
            'data' => SupportTicketResource::collection($tickets->items()),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        return response()->json(new SupportTicketResource($this->service->createTicket($request->validated())), 201);
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        return response()->json(new SupportTicketResource($this->service->updateTicket($supportTicket, $request->validated())));
    }

    private function options(): array
    {
        return [
            'statuses' => SupportTicketStatus::options(),
            'severities' => SupportSeverity::options(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
            'deployments' => Deployment::query()->orderBy('name')->get(['id', 'name'])->map(fn (Deployment $deployment) => ['value' => $deployment->id, 'label' => $deployment->name])->all(),
            'agreements' => SupportAgreement::query()->orderBy('name')->get(['id', 'name'])->map(fn (SupportAgreement $agreement) => ['value' => $agreement->id, 'label' => $agreement->name])->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])->all(),
        ];
    }
}
