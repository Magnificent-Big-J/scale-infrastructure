<?php

namespace App\Enums;

enum ReportType: string
{
    case ClientPortfolio = 'client_portfolio';
    case OperationsHealth = 'operations_health';
    case SupportSummary = 'support_summary';
    case FinanceSummary = 'finance_summary';
    case ProfitabilitySummary = 'profitability_summary';

    public function label(): string
    {
        return match ($this) {
            self::ClientPortfolio => 'Client Portfolio Summary',
            self::OperationsHealth => 'Operations Health Summary',
            self::SupportSummary => 'Support Summary',
            self::FinanceSummary => 'Finance Summary',
            self::ProfitabilitySummary => 'Profitability Summary',
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
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
            'icon' => $type->icon(),
        ], self::cases());
    }

    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
