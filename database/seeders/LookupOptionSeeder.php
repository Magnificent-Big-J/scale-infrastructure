<?php

namespace Database\Seeders;

use App\Enums\LookupType;
use App\Models\LookupOption;
use Illuminate\Database\Seeder;

class LookupOptionSeeder extends Seeder
{
    /**
     * Default managed reference data. Keyed by {@see LookupType}; business users
     * extend/rename/reorder these from the Reference data admin screen.
     *
     * @var array<string, list<array{value: string, label: string}>>
     */
    private array $defaults = [
        LookupType::TicketCategory->value => [
            ['value' => 'general', 'label' => 'General'],
            ['value' => 'support', 'label' => 'Support request'],
            ['value' => 'incident', 'label' => 'Incident'],
            ['value' => 'operations', 'label' => 'Operations'],
            ['value' => 'reporting', 'label' => 'Reporting'],
            ['value' => 'backup', 'label' => 'Backup & recovery'],
            ['value' => 'billing', 'label' => 'Billing & accounts'],
            ['value' => 'deployment', 'label' => 'Deployment & provisioning'],
            ['value' => 'security', 'label' => 'Security'],
        ],
        LookupType::OpportunitySource->value => [
            ['value' => 'referral', 'label' => 'Referral'],
            ['value' => 'inbound', 'label' => 'Inbound enquiry'],
            ['value' => 'outbound', 'label' => 'Outbound'],
            ['value' => 'existing_client', 'label' => 'Existing client'],
            ['value' => 'partner', 'label' => 'Partner'],
            ['value' => 'event', 'label' => 'Event'],
        ],
        LookupType::DeploymentEnvironment->value => [
            ['value' => 'production', 'label' => 'Production'],
            ['value' => 'staging', 'label' => 'Staging'],
            ['value' => 'uat', 'label' => 'UAT'],
            ['value' => 'development', 'label' => 'Development'],
        ],
        LookupType::InfrastructureAssetType->value => [
            ['value' => 'app_server', 'label' => 'App server'],
            ['value' => 'database', 'label' => 'Database'],
            ['value' => 'cache', 'label' => 'Cache'],
            ['value' => 'storage', 'label' => 'Storage'],
            ['value' => 'queue', 'label' => 'Queue'],
            ['value' => 'domain', 'label' => 'Domain'],
        ],
        LookupType::BillingRecordType->value => [
            ['value' => 'assessment', 'label' => 'Assessment'],
            ['value' => 'implementation', 'label' => 'Implementation'],
            ['value' => 'support', 'label' => 'Support'],
            ['value' => 'hosting', 'label' => 'Hosting'],
            ['value' => 'enhancement', 'label' => 'Enhancement'],
            ['value' => 'consulting', 'label' => 'Consulting'],
        ],
        LookupType::BillingInterval->value => [
            ['value' => 'monthly', 'label' => 'Monthly'],
            ['value' => 'quarterly', 'label' => 'Quarterly'],
            ['value' => 'annual', 'label' => 'Annual'],
            ['value' => 'once_off', 'label' => 'Once-off'],
        ],
        LookupType::PaymentMethod->value => [
            ['value' => 'eft', 'label' => 'EFT'],
            ['value' => 'card', 'label' => 'Card'],
            ['value' => 'payfast', 'label' => 'PayFast'],
            ['value' => 'debit_order', 'label' => 'Debit order'],
            ['value' => 'cash', 'label' => 'Cash'],
            ['value' => 'other', 'label' => 'Other'],
        ],
    ];

    /**
     * Options that carry a display color hint (used for badges) alongside the
     * label. Kept separate from $defaults since it's the exception, not the rule.
     *
     * @var array<string, list<array{value: string, label: string, color: string}>>
     */
    private array $coloredDefaults = [
        LookupType::ClientTier->value => [
            ['value' => 'starter', 'label' => 'Starter', 'color' => 'processing'],
            ['value' => 'growth', 'label' => 'Growth', 'color' => 'active'],
            ['value' => 'enterprise', 'label' => 'Enterprise', 'color' => 'active'],
        ],
        LookupType::ChangeRisk->value => [
            ['value' => 'low', 'label' => 'Low', 'color' => 'active'],
            ['value' => 'medium', 'label' => 'Medium', 'color' => 'pending'],
            ['value' => 'high', 'label' => 'High', 'color' => 'suspended'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->defaults as $type => $options) {
            foreach ($options as $index => $option) {
                // firstOrCreate so re-seeding only fills gaps and never clobbers
                // business renames, reordering, or deactivations made in the UI.
                LookupOption::query()->firstOrCreate(
                    ['type' => $type, 'value' => $option['value']],
                    ['label' => $option['label'], 'sort_order' => $index, 'is_active' => true],
                );
            }
        }

        foreach ($this->coloredDefaults as $type => $options) {
            foreach ($options as $index => $option) {
                LookupOption::query()->firstOrCreate(
                    ['type' => $type, 'value' => $option['value']],
                    ['label' => $option['label'], 'sort_order' => $index, 'is_active' => true, 'metadata' => ['color' => $option['color']]],
                );
            }
        }
    }
}
