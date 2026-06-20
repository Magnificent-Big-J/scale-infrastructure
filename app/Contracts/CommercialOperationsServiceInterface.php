<?php

namespace App\Contracts;

use App\Models\BillingRecord;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommercialOperationsServiceInterface
{
    public function paginateContracts(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $clientId = null): LengthAwarePaginator;

    public function paginateBillingRecords(int $perPage = 15, ?string $search = null, ?string $type = null, ?string $cadence = null, ?string $clientId = null): LengthAwarePaginator;

    public function paginateInvoices(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $clientId = null): LengthAwarePaginator;

    public function createContract(array $data): Contract;

    public function updateContract(Contract $contract, array $data): Contract;

    public function createBillingRecord(array $data): BillingRecord;

    public function updateBillingRecord(BillingRecord $record, array $data): BillingRecord;

    public function createInvoice(array $data): Invoice;

    public function updateInvoice(Invoice $invoice, array $data): Invoice;

    public function recordPayment(Invoice $invoice, array $data): InvoicePayment;
}
