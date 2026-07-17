<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CommercialOperationsServiceInterface;
use App\Contracts\LookupOptionServiceInterface;
use App\Enums\BillingCadence;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreBillingRecordRequest;
use App\Http\Requests\Commercial\UpdateBillingRecordRequest;
use App\Http\Resources\BillingRecordResource;
use App\Models\BillingRecord;
use App\Models\Client;
use App\Models\Contract;
use App\Models\LookupOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingRecordController extends Controller
{
    public function __construct(
        private readonly CommercialOperationsServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $records = $this->service->paginateBillingRecords(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('type')->toString() ?: null,
            $request->string('cadence')->toString() ?: null,
            $request->string('client_id')->toString() ?: null,
        );

        return response()->json([
            'data' => BillingRecordResource::collection($records->items()),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function store(StoreBillingRecordRequest $request): JsonResponse
    {
        return response()->json(new BillingRecordResource($this->service->createBillingRecord($request->validated())), 201);
    }

    public function update(UpdateBillingRecordRequest $request, BillingRecord $billingRecord): JsonResponse
    {
        return response()->json(new BillingRecordResource($this->service->updateBillingRecord($billingRecord, $request->validated())));
    }

    private function options(): array
    {
        return [
            'types' => $this->lookups->optionsFor(LookupType::BillingRecordType)
                ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                ->all(),
            'cadences' => BillingCadence::options(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
            'contracts' => Contract::query()->orderBy('name')->get(['id', 'name'])->map(fn (Contract $contract) => ['value' => $contract->id, 'label' => $contract->name])->all(),
        ];
    }
}
