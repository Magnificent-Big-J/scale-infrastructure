<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CommercialOperationsServiceInterface;
use App\Contracts\LookupOptionServiceInterface;
use App\Enums\InvoiceStatus;
use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StoreInvoiceRequest;
use App\Http\Requests\Commercial\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\LookupOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly CommercialOperationsServiceInterface $service,
        private readonly LookupOptionServiceInterface $lookups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->service->paginateInvoices(
            max(1, min((int) $request->integer('per_page', 10), 100)),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->string('client_id')->toString() ?: null,
        );

        return response()->json([
            'data' => InvoiceResource::collection($invoices->items()),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => new InvoiceResource($invoice->load(['client', 'contract', 'payments.client']))]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        return response()->json(new InvoiceResource($this->service->createInvoice($request->validated())), 201);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        return response()->json(new InvoiceResource($this->service->updateInvoice($invoice, $request->validated())));
    }

    private function options(): array
    {
        return [
            'statuses' => InvoiceStatus::options(),
            'payment_methods' => $this->lookups->optionsFor(LookupType::PaymentMethod)
                ->map(fn (LookupOption $option) => ['value' => $option->value, 'label' => $option->label])
                ->all(),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => ['value' => $client->id, 'label' => $client->name])->all(),
            'contracts' => Contract::query()->orderBy('name')->get(['id', 'name'])->map(fn (Contract $contract) => ['value' => $contract->id, 'label' => $contract->name])->all(),
        ];
    }
}
