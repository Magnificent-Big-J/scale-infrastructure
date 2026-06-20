<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SupportOperationsServiceInterface;
use App\Enums\SupportAgreementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportAgreementRequest;
use App\Http\Requests\Support\UpdateSupportAgreementRequest;
use App\Http\Resources\SupportAgreementResource;
use App\Models\Client;
use App\Models\SupportAgreement;
use App\Models\SupportTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportAgreementController extends Controller
{
    public function __construct(private readonly SupportOperationsServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $agreements = $this->service->paginateAgreements(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('client_id')->toString() ?: null,
        );

        return response()->json([
            'data' => SupportAgreementResource::collection($agreements->items()),
            'meta' => [
                'current_page' => $agreements->currentPage(),
                'last_page' => $agreements->lastPage(),
                'per_page' => $agreements->perPage(),
                'total' => $agreements->total(),
            ],
            'options' => [
                'statuses' => SupportAgreementStatus::options(),
                'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
                'support_tiers' => SupportTier::query()->orderBy('sort_order')->get(['id', 'name'])->map(fn (SupportTier $tier) => ['value' => $tier->id, 'label' => $tier->name])->all(),
            ],
        ]);
    }

    public function store(StoreSupportAgreementRequest $request): JsonResponse
    {
        return response()->json(new SupportAgreementResource($this->service->createAgreement($request->validated())), 201);
    }

    public function update(UpdateSupportAgreementRequest $request, SupportAgreement $supportAgreement): JsonResponse
    {
        return response()->json(new SupportAgreementResource($this->service->updateAgreement($supportAgreement, $request->validated())));
    }
}
