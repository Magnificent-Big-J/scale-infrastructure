<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ProfitabilityServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreProfitabilityRecordRequest;
use App\Http\Requests\Commercial\UpdateProfitabilityRecordRequest;
use App\Http\Resources\ProfitabilityRecordResource;
use App\Models\Client;
use App\Models\ProfitabilityRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitabilityController extends Controller
{
    public function __construct(private readonly ProfitabilityServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $records = $this->service->paginate(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('period')->toString() ?: null,
            $request->string('client_id')->toString() ?: null,
        );

        return response()->json([
            'data' => ProfitabilityRecordResource::collection($records->items()),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
            'summary' => $this->service->summary(),
            'trend' => $this->service->trend(),
            'options' => [
                'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
            ],
        ]);
    }

    public function store(StoreProfitabilityRecordRequest $request): JsonResponse
    {
        return response()->json(new ProfitabilityRecordResource($this->service->create($request->validated())), 201);
    }

    public function update(UpdateProfitabilityRecordRequest $request, ProfitabilityRecord $profitabilityRecord): JsonResponse
    {
        return response()->json(new ProfitabilityRecordResource($this->service->update($profitabilityRecord, $request->validated())));
    }
}
