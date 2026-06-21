<?php

namespace Database\Seeders;

use App\Enums\OpportunityStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'sales@codescaletech.test')->first()
            ?? User::where('email', 'admin@codescaletech.test')->first();

        $clients = Client::query()->pluck('id', 'code');

        $rows = [
            ['client_code' => 'AURECON-PMO', 'title' => 'Production observability uplift', 'stage' => OpportunityStage::Proposal, 'value' => 480000, 'probability' => 60, 'source' => 'existing_client'],
            ['client_code' => 'NALA-PROJECTS', 'title' => 'Second environment rollout', 'stage' => OpportunityStage::Qualified, 'value' => 260000, 'probability' => 40, 'source' => 'referral'],
            ['client_code' => 'KOPANO-CONSULTING', 'title' => 'Annual support retainer', 'stage' => OpportunityStage::Negotiation, 'value' => 180000, 'probability' => 75, 'source' => 'existing_client'],
            ['prospect_name' => 'Thoko Digital', 'title' => 'Greenfield ScaleLens build', 'stage' => OpportunityStage::Lead, 'value' => 600000, 'probability' => 20, 'source' => 'inbound'],
            ['client_code' => 'AURECON-PMO', 'title' => 'Legacy platform migration', 'stage' => OpportunityStage::Won, 'value' => 320000, 'probability' => 100, 'source' => 'existing_client'],
            ['client_code' => 'NALA-PROJECTS', 'title' => 'Reporting pilot', 'stage' => OpportunityStage::Lost, 'value' => 90000, 'probability' => 0, 'source' => 'outbound', 'lost_reason' => 'Budget deferred to next year'],
        ];

        foreach ($rows as $row) {
            Opportunity::query()->updateOrCreate(
                ['title' => $row['title']],
                [
                    'client_id' => isset($row['client_code']) ? $clients->get($row['client_code']) : null,
                    'prospect_name' => $row['prospect_name'] ?? null,
                    'owner_id' => $owner?->id,
                    'stage' => $row['stage']->value,
                    'value' => $row['value'],
                    'probability' => $row['probability'],
                    'source' => $row['source'],
                    'expected_close_date' => Carbon::now()->addMonths(2)->toDateString(),
                    'won_at' => $row['stage'] === OpportunityStage::Won ? Carbon::now()->subDays(10) : null,
                    'lost_at' => $row['stage'] === OpportunityStage::Lost ? Carbon::now()->subDays(20) : null,
                    'lost_reason' => $row['lost_reason'] ?? null,
                ],
            );
        }
    }
}
