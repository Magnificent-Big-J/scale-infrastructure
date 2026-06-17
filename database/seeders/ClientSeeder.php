<?php

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Enums\ClientTier;
use App\Models\Client;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@codescaletech.test')->first();
        $sales = User::where('email', 'sales@codescaletech.test')->first();
        $support = User::where('email', 'support@codescaletech.test')->first();

        $packages = Package::query()->pluck('id', 'code');

        $clients = [
            [
                'code' => 'AURECON-PMO',
                'name' => 'Aurecon Programme Office',
                'legal_name' => 'Aurecon Programme Office (Demo)',
                'tier' => ClientTier::Enterprise,
                'status' => ClientStatus::Active,
                'health_score' => 82,
                'package_code' => 'SCALELENS-ENTERPRISE',
                'owner_user_id' => $admin?->id,
                'notes' => 'Enterprise portfolio visibility rollout with governance reporting focus.',
                'contacts' => [
                    ['name' => 'Thandi Mbeki', 'email' => 'thandi.mbeki@example.test', 'phone' => '+27 82 555 0101', 'role' => 'Programme Director', 'is_primary' => true],
                    ['name' => 'Pieter Botha', 'email' => 'pieter.botha@example.test', 'phone' => '+27 82 555 0102', 'role' => 'IT Owner', 'is_primary' => false],
                ],
            ],
            [
                'code' => 'NALA-PROJECTS',
                'name' => 'Nala Projects',
                'legal_name' => 'Nala Projects (Pty) Ltd',
                'tier' => ClientTier::Growth,
                'status' => ClientStatus::Onboarding,
                'health_score' => 74,
                'package_code' => 'SCALELENS-GROWTH',
                'owner_user_id' => $sales?->id,
                'notes' => 'Growth implementation in onboarding with priority support recommended.',
                'contacts' => [
                    ['name' => 'Aisha Naidoo', 'email' => 'aisha.naidoo@example.test', 'phone' => '+27 83 555 0201', 'role' => 'Operations Lead', 'is_primary' => true],
                    ['name' => 'Lerato Dlamini', 'email' => 'lerato.dlamini@example.test', 'phone' => '+27 83 555 0202', 'role' => 'Finance Contact', 'is_primary' => false],
                ],
            ],
            [
                'code' => 'KOPANO-CONSULTING',
                'name' => 'Kopano Consulting',
                'legal_name' => 'Kopano Consulting CC',
                'tier' => ClientTier::Starter,
                'status' => ClientStatus::Active,
                'health_score' => 91,
                'package_code' => 'SCALELENS-STARTER',
                'owner_user_id' => $support?->id,
                'notes' => 'Starter package client used for support and account workflow rehearsal.',
                'contacts' => [
                    ['name' => 'Michael Jacobs', 'email' => 'michael.jacobs@example.test', 'phone' => '+27 84 555 0301', 'role' => 'Managing Consultant', 'is_primary' => true],
                ],
            ],
        ];

        foreach ($clients as $item) {
            $contacts = $item['contacts'];
            $packageCode = $item['package_code'];
            unset($item['contacts'], $item['package_code']);

            $client = Client::updateOrCreate(
                ['code' => $item['code']],
                [
                    ...$item,
                    'package_id' => $packages[$packageCode] ?? null,
                ],
            );

            foreach ($contacts as $contact) {
                if ($contact['is_primary']) {
                    $client->contacts()->update(['is_primary' => false]);
                }

                $client->contacts()->updateOrCreate(
                    ['email' => $contact['email']],
                    $contact,
                );
            }
        }
    }
}
