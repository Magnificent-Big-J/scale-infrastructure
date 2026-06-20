<?php

namespace App\Services;

use App\Contracts\FinanceDashboardServiceInterface;
use App\Enums\BillingCadence;
use App\Enums\ContractStatus;
use App\Models\BillingRecord;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoicePayment;

class FinanceDashboardService implements FinanceDashboardServiceInterface
{
    public function metrics(): array
    {
        $monthlyRecurring = (float) BillingRecord::query()
            ->where('is_active', true)
            ->where('cadence', BillingCadence::Monthly->value)
            ->sum('amount');

        $annualRecurring = (float) BillingRecord::query()
            ->where('is_active', true)
            ->where('cadence', BillingCadence::Annual->value)
            ->sum('amount');

        $mrr = round($monthlyRecurring + ($annualRecurring / 12), 2);

        $outstanding = (float) Invoice::query()
            ->open()
            ->selectRaw('coalesce(sum(amount - amount_paid), 0) as total')
            ->value('total');

        $overdue = (float) Invoice::query()
            ->overdue()
            ->selectRaw('coalesce(sum(amount - amount_paid), 0) as total')
            ->value('total');

        $overdueCount = Invoice::query()->overdue()->count();

        $paymentsThisMonth = (float) InvoicePayment::query()
            ->whereBetween('paid_on', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $activeContractValue = (float) Contract::query()
            ->whereIn('status', [ContractStatus::Active->value, ContractStatus::Renewing->value])
            ->sum('total_value');

        $renewalsDueSoon = Contract::query()
            ->whereIn('status', [ContractStatus::Active->value, ContractStatus::Renewing->value])
            ->whereNotNull('renewal_date')
            ->whereBetween('renewal_date', [now()->toDateString(), now()->addDays(60)->toDateString()])
            ->count();

        return [
            'mrr' => $mrr,
            'arr' => round($mrr * 12, 2),
            'outstanding_total' => round($outstanding, 2),
            'overdue_total' => round($overdue, 2),
            'overdue_count' => $overdueCount,
            'payments_this_month' => round($paymentsThisMonth, 2),
            'active_contract_value' => round($activeContractValue, 2),
            'renewals_due_soon' => $renewalsDueSoon,
        ];
    }
}
