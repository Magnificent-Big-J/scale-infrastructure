<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CommercialOperationsServiceInterface;
use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreContractRequest;
use App\Http\Requests\Commercial\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(private readonly CommercialOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $contracts = $this->service->paginateContracts(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return response()->json([
            'data' => ContractResource::collection($contracts->items()),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        return response()->json(new ContractResource($this->service->createContract($request->validated())), 201);
    }

    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        return response()->json(new ContractResource($this->service->updateContract($contract, $request->validated())));
    }

    private function options(): array
    {
        return [
            'statuses' => ContractStatus::options(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name'])->map(fn (Product $product) => ['value' => $product->id, 'label' => $product->name])->all(),
            'packages' => Package::query()->orderBy('name')->get(['id', 'name'])->map(fn (Package $package) => ['value' => $package->id, 'label' => $package->name])->all(),
        ];
    }
}
