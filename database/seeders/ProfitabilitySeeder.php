<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ProfitabilityRecord;
use Illuminate\Database\Seeder;

class ProfitabilitySeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::query()->pluck('id', 'code');

        $records = [
            ['client_code' => 'NALA-PROJECTS', 'period' => '2026-05', 'revenue' => 55000, 'hosting_cost' => 4000, 'labour_cost' => 18000, 'monitoring_cost' => 1500, 'other_cost' => 2000],
            ['client_code' => 'NALA-PROJECTS', 'period' => '2026-06', 'revenue' => 58000, 'hosting_cost' => 4000, 'labour_cost' => 16000, 'monitoring_cost' => 1500, 'other_cost' => 1500],
            ['client_code' => 'AURECON-PMO', 'period' => '2026-05', 'revenue' => 90000, 'hosting_cost' => 6000, 'labour_cost' => 34000, 'monitoring_cost' => 3000, 'other_cost' => 4000],
            ['client_code' => 'AURECON-PMO', 'period' => '2026-06', 'revenue' => 92000, 'hosting_cost' => 6000, 'labour_cost' => 30000, 'monitoring_cost' => 3000, 'other_cost' => 3500],
            ['client_code' => 'KOPANO-CONSULTING', 'period' => '2026-06', 'revenue' => 22000, 'hosting_cost' => 2000, 'labour_cost' => 9000, 'monitoring_cost' => 800, 'other_cost' => 1200],
        ];

        foreach ($records as $record) {
            $clientCode = $record['client_code'];
            unset($record['client_code']);

            $cost = $record['hosting_cost'] + $record['labour_cost'] + $record['monitoring_cost'] + $record['other_cost'];
            $profit = round($record['revenue'] - $cost, 2);
            $margin = $record['revenue'] > 0 ? round($profit / $record['revenue'] * 100, 2) : 0;

            ProfitabilityRecord::updateOrCreate(
                ['client_id' => $clients[$clientCode] ?? null, 'period' => $record['period']],
                [
                    ...$record,
                    'client_id' => $clients[$clientCode] ?? null,
                    'profit' => $profit,
                    'margin' => $margin,
                    'notes' => 'Seeded Module 05 profitability record.',
                ],
            );
        }
    }
}
