<?php

namespace App\Services;

use App\Contracts\CommercialOperationsServiceInterface;
use App\Enums\InvoiceStatus;
use App\Models\BillingRecord;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommercialOperationsService implements CommercialOperationsServiceInterface
{
    public function paginateContracts(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $clientId = null): LengthAwarePaginator
    {
        return Contract::query()
            ->with(['client', 'product', 'package'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw("case when status = 'active' then 0 when status = 'renewing' then 1 else 2 end")
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function paginateBillingRecords(int $perPage = 15, ?string $search = null, ?string $type = null, ?string $cadence = null, ?string $clientId = null): LengthAwarePaginator
    {
        return BillingRecord::query()
            ->with(['client', 'contract'])
            ->search($search)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($cadence, fn ($query) => $query->where('cadence', $cadence))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw('case when is_active then 0 else 1 end')
            ->latest('starts_on')
            ->paginate($perPage);
    }

    public function paginateInvoices(int $perPage = 15, ?string $search = null, ?string $status = null, ?string $clientId = null): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['client', 'contract'])
            ->search($search)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw("case when status in ('sent', 'partially_paid', 'overdue') then 0 else 1 end")
            ->latest('issued_on')
            ->paginate($perPage);
    }

    public function createContract(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $contract = Contract::query()->create($data);

            $this->log('contracts', $contract, 'created', 'Created contract');

            return $contract->load(['client', 'product', 'package']);
        });
    }

    public function updateContract(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            $contract->fill($data)->save();

            $this->log('contracts', $contract, 'updated', 'Updated contract', ['changes' => array_keys($contract->getChanges())]);

            return $contract->refresh()->load(['client', 'product', 'package']);
        });
    }

    public function createBillingRecord(array $data): BillingRecord
    {
        return DB::transaction(function () use ($data) {
            $record = BillingRecord::query()->create($data);

            $this->log('billing_records', $record, 'created', 'Created billing record');

            return $record->load(['client', 'contract']);
        });
    }

    public function updateBillingRecord(BillingRecord $record, array $data): BillingRecord
    {
        return DB::transaction(function () use ($record, $data) {
            $record->fill($data)->save();

            $this->log('billing_records', $record, 'updated', 'Updated billing record', ['changes' => array_keys($record->getChanges())]);

            return $record->refresh()->load(['client', 'contract']);
        });
    }

    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::query()->create($data);

            $this->log('invoices', $invoice, 'created', 'Created invoice');

            return $invoice->load(['client', 'contract']);
        });
    }

    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice->fill($data)->save();

            $this->log('invoices', $invoice, 'updated', 'Updated invoice', ['changes' => array_keys($invoice->getChanges())]);

            return $invoice->refresh()->load(['client', 'contract']);
        });
    }

    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = $invoice->payments()->create([
                ...$data,
                'client_id' => $invoice->client_id,
            ]);

            $invoice->amount_paid = round((float) $invoice->amount_paid + (float) $payment->amount, 2);
            $invoice->status = $this->settleStatus($invoice);
            $invoice->save();

            $this->log('payments', $payment, 'created', 'Recorded invoice payment', ['invoice_id' => $invoice->id]);

            return $payment->load('client');
        });
    }

    private function settleStatus(Invoice $invoice): InvoiceStatus
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return InvoiceStatus::Cancelled;
        }

        if ((float) $invoice->amount_paid >= (float) $invoice->amount) {
            return InvoiceStatus::Paid;
        }

        if ((float) $invoice->amount_paid > 0) {
            return InvoiceStatus::PartiallyPaid;
        }

        return $invoice->status;
    }

    private function log(string $domain, Model $subject, string $event, string $message, array $properties = []): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity($domain)
            ->performedOn($subject)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log($message);
    }
}
