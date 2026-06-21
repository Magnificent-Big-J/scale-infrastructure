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
    }
}
