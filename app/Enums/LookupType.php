<?php

namespace App\Enums;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Names the managed reference-data lists. The *set of lists* is code-controlled
 * (this enum); the *values* inside each list are business-owned data living in
 * the `lookup_options` table. Add a case here to introduce a new managed
 * vocabulary, then seed its defaults.
 */
enum LookupType: string
{
    case TicketCategory = 'ticket_category';
    case OpportunitySource = 'opportunity_source';
    case DeploymentEnvironment = 'deployment_environment';
    case InfrastructureAssetType = 'infrastructure_asset_type';
    case BillingRecordType = 'billing_record_type';
    case BillingInterval = 'billing_interval';
    case PaymentMethod = 'payment_method';
    case ClientTier = 'client_tier';
    case ChangeRisk = 'change_risk';

    public function label(): string
    {
        return match ($this) {
            self::TicketCategory => 'Ticket category',
            self::OpportunitySource => 'Opportunity source',
            self::DeploymentEnvironment => 'Deployment environment',
            self::InfrastructureAssetType => 'Infrastructure asset type',
            self::BillingRecordType => 'Billing record type',
            self::BillingInterval => 'Billing interval',
            self::PaymentMethod => 'Payment method',
            self::ClientTier => 'Client tier',
            self::ChangeRisk => 'Change risk',
        };
    }

    /**
     * Short helper text shown on the reference-data admin screen.
     */
    public function description(): string
    {
        return match ($this) {
            self::TicketCategory => 'Selectable categories used to classify support tickets.',
            self::OpportunitySource => 'Where a sales opportunity originated.',
            self::DeploymentEnvironment => 'Environment tiers a deployment can belong to (production, staging, ...).',
            self::InfrastructureAssetType => 'Kinds of infrastructure asset tracked against a deployment.',
            self::BillingRecordType => 'What a recurring or once-off billing line represents.',
            self::BillingInterval => 'How often a package is billed.',
            self::PaymentMethod => 'How an invoice payment was received.',
            self::ClientTier => 'Client size/plan tier, shown as a badge on the client record.',
            self::ChangeRisk => 'Risk level of a change request, shown as a badge.',
        };
    }

    /**
     * Validation rule asserting a value is an active option of this list.
     * Reuse on any form field backed by this managed vocabulary.
     */
    public function existsRule(): Exists
    {
        return Rule::exists('lookup_options', 'value')
            ->where('type', $this->value)
            ->where('is_active', true);
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
