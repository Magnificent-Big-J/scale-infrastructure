<?php

namespace App\Enums;

enum ReportType: string
{
    case ClientPortfolio = 'client_portfolio';
    case OperationsHealth = 'operations_health';
    case SupportSummary = 'support_summary';
    case FinanceSummary = 'finance_summary';
    case ProfitabilitySummary = 'profitability_summary';
    case AccessReview = 'access_review';

    public function label(): string
    {
        return match ($this) {
            self::ClientPortfolio => 'Client Portfolio Summary',
            self::OperationsHealth => 'Operations Health Summary',
            self::SupportSummary => 'Support Summary',
            self::FinanceSummary => 'Finance Summary',
            self::ProfitabilitySummary => 'Profitability Summary',
            self::AccessReview => 'Access Review',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ClientPortfolio => 'Account status, tier, health, and package across the client base.',
            self::OperationsHealth => 'Deployment status and monitoring posture across environments.',
            self::SupportSummary => 'Agreements, open tickets, and incidents per client.',
            self::FinanceSummary => 'Invoiced, paid, outstanding, and overdue amounts per client.',
            self::ProfitabilitySummary => 'Revenue, cost, profit, and margin per client and period.',
            self::AccessReview => 'Every user, their roles, permission count, and two-factor status.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ClientPortfolio => 'mdi-domain',
            self::OperationsHealth => 'mdi-server-network',
            self::SupportSummary => 'mdi-lifebuoy',
            self::FinanceSummary => 'mdi-cash-multiple',
            self::ProfitabilitySummary => 'mdi-finance',
            self::AccessReview => 'mdi-shield-account-outline',
        };
    }

    /**
     * FinanceSummary/ProfitabilitySummary surface the same revenue/cost/
     * invoice figures the billing and profitability modules already gate
     * behind a dedicated permission - a report is just another view onto
     * that data, so it shouldn't bypass the gate the underlying records are
     * protected by. AccessReview lists every user's roles and 2FA status,
     * which is exactly what users.view already gates on the Users page.
     */
    public function requiredPermission(): ?string
    {
        return match ($this) {
            self::FinanceSummary => 'billing.view',
            self::ProfitabilitySummary => 'profitability.view',
            self::AccessReview => 'users.view',
            default => null,
        };
    }

    public function toOption(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'description' => $this->description(),
            'icon' => $this->icon(),
        ];
    }

    public static function options(): array
    {
        return array_map(fn (self $type) => $type->toOption(), self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
