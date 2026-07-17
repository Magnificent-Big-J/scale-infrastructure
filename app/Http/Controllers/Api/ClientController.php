<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ClientServiceInterface;
use App\Contracts\LookupOptionServiceInterface;
use App\Enums\ClientStatus;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\LookupOption;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $tier = $request->string('tier')->toString() ?: null;

        $clients = $this->service->paginate($perPage, $search, $status, $tier);

        return response()->json([
            'data' => ClientResource::collection($clients->items()),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load(['package.product', 'owner', 'primaryContact', 'contacts'])->loadCount('contacts');

        return response()->json([
            'data' => new ClientResource($client),
            'summary' => $this->service->summary($client),
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->service->create($request->validated());

        return response()->json(new ClientResource($client), 201);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        return new ClientResource($this->service->update($client, $request->validated()));
    }

    public function destroy(Client $client): Response
    {
        $this->service->archive($client);

        return response()->noContent();
    }

    private function options(): array
    {
        return [
            'statuses' => ClientStatus::options(),
            'tiers' => $this->lookups->optionsFor(LookupType::ClientTier)
                ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                ->all(),
            'packages' => Package::query()
                ->with('product')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (Package $package) => [
                    'value' => $package->id,
                    'label' => "{$package->product?->name} {$package->name}",
                ])
                ->all(),
            'owners' => User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])
                ->all(),
        ];
    }
}
