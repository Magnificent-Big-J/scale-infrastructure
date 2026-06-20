<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CommercialOperationsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commercial\StorePaymentRequest;
use App\Http\Resources\InvoicePaymentResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly CommercialOperationsServiceInterface $service) {}

    public function store(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        return response()->json(new InvoicePaymentResource($this->service->recordPayment($invoice, $request->validated())), 201);
    }
}
