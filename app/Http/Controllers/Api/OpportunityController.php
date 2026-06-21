<?php

namespace App\Http\Controllers\Api;

use App\Contracts\LookupOptionServiceInterface;
use App\Contracts\OpportunityServiceInterface;
use App\Enums\LookupType;
use App\Enums\OpportunityStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreOpportunityRequest;
use App\Http\Requests\Commercial\UpdateOpportunityRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Client;
use App\Models\LookupOption;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpportunityController extends Controller
{
    public function __construct(
        private readonly OpportunityServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $opportunities = $this->service->paginate(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('stage')->toString() ?: null,
            $request->string('client_id')->toString() ?: null,
            $request->integer('owner_id') ?: null,
        );

        return response()->json([
            'data' => OpportunityResource::collection($opportunities->items()),
            'meta' => [
                'current_page' => $opportunities->currentPage(),
                'last_page' => $opportunities->lastPage(),
                'per_page' => $opportunities->perPage(),
                'total' => $opportunities->total(),
            ],
            'summary' => $this->service->summary(),
            'pipeline' => $this->service->pipeline(),
            'options' => $this->options(),
        ]);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        $opportunity->load(['client', 'owner', 'contract']);

        return response()->json(['data' => new OpportunityResource($opportunity)]);
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        return response()->json(new OpportunityResource($this->service->create($request->validated())), 201);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): OpportunityResource
    {
        return new OpportunityResource($this->service->update($opportunity, $request->validated()));
    }

    public function destroy(Opportunity $opportunity): Response
    {
        $this->service->delete($opportunity);

        return response()->noContent();
    }

    public function win(Opportunity $opportunity): OpportunityResource
    {
        return new OpportunityResource($this->service->markWon($opportunity));
    }

    private function options(): array
    {
        return [
            'stages' => OpportunityStage::options(),
            'sources' => $this->lookups->optionsFor(LookupType::OpportunitySource)
                ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                ->all(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
            'owners' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])->all(),
        ];
    }
}
