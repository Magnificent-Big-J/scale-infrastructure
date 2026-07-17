<?php

namespace Database\Seeders;

use App\Enums\DeploymentStatus;
use App\Enums\MonitoringCheckStatus;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DeploymentSeeder extends Seeder
{
    public function run(): void
    {
        $scaleLens = Product::where('code', 'SCALELENS')->first();
        $packages = Package::query()->pluck('id', 'code');
        $clients = Client::query()->pluck('id', 'code');

        $deployments = [
            [
                'client_code' => 'AURECON-PMO',
                'package_code' => 'SCALELENS-ENTERPRISE',
                'name' => 'ScaleLens Production',
                'environment' => 'production',
                'domain' => 'aurecon.scalelens.test',
                'app_url' => 'https://aurecon.scalelens.test',
                'current_version' => 'v1.8.2',
                'go_live_date' => '2026-06-01',
                'status' => DeploymentStatus::Active,
                'notes' => 'Production environment for the enterprise programme office demo.',
                'assets' => [
                    ['name' => 'Production app node', 'type' => 'app_server', 'provider' => 'AWS', 'region' => 'af-south-1', 'size' => '2 vCPU / 4GB', 'monthly_cost' => 1850],
                    ['name' => 'Managed PostgreSQL', 'type' => 'database', 'provider' => 'AWS', 'region' => 'af-south-1', 'size' => 'db.t4g.small', 'monthly_cost' => 1250],
                ],
                'checks' => [
                    ['name' => 'Production uptime', 'check_type' => 'uptime', 'target' => 'https://aurecon.scalelens.test', 'status' => MonitoringCheckStatus::Passing],
                    ['name' => 'SSL certificate', 'check_type' => 'ssl', 'target' => 'aurecon.scalelens.test', 'status' => MonitoringCheckStatus::Passing],
                    ['name' => 'Nightly backups', 'check_type' => 'backup', 'target' => 'aurecon-pgsql', 'status' => MonitoringCheckStatus::Warning],
                ],
            ],
            [
                'client_code' => 'NALA-PROJECTS',
                'package_code' => 'SCALELENS-GROWTH',
                'name' => 'ScaleLens Staging',
                'environment' => 'staging',
                'domain' => 'nala-staging.scalelens.test',
                'app_url' => 'https://nala-staging.scalelens.test',
                'current_version' => 'v1.9.0-rc',
                'go_live_date' => null,
                'status' => DeploymentStatus::Provisioning,
                'notes' => 'Release validation environment for Growth onboarding.',
                'assets' => [
                    ['name' => 'Staging app node', 'type' => 'app_server', 'provider' => 'DigitalOcean', 'region' => 'fra1', 'size' => '1 vCPU / 2GB', 'monthly_cost' => 640],
                    ['name' => 'Redis queue backend', 'type' => 'cache', 'provider' => 'DigitalOcean', 'region' => 'fra1', 'size' => 'basic-xs', 'monthly_cost' => 220],
                ],
                'checks' => [
                    ['name' => 'Staging uptime', 'check_type' => 'uptime', 'target' => 'https://nala-staging.scalelens.test', 'status' => MonitoringCheckStatus::Passing],
                    ['name' => 'Queue latency', 'check_type' => 'queue', 'target' => 'nala-redis', 'status' => MonitoringCheckStatus::Warning],
                ],
            ],
            [
                'client_code' => 'KOPANO-CONSULTING',
                'package_code' => 'SCALELENS-STARTER',
                'name' => 'Client Sandbox',
                'environment' => 'development',
                'domain' => 'kopano-sandbox.scalelens.test',
                'app_url' => 'https://kopano-sandbox.scalelens.test',
                'current_version' => 'v1.8.1',
                'go_live_date' => '2026-05-20',
                'status' => DeploymentStatus::Active,
                'notes' => 'Sandbox used for onboarding and support workflow rehearsal.',
                'assets' => [
                    ['name' => 'Sandbox app node', 'type' => 'app_server', 'provider' => 'Hetzner', 'region' => 'fsn1', 'size' => 'CX22', 'monthly_cost' => 210],
                ],
                'checks' => [
                    ['name' => 'Sandbox uptime', 'check_type' => 'uptime', 'target' => 'https://kopano-sandbox.scalelens.test', 'status' => MonitoringCheckStatus::Passing],
                ],
            ],
        ];

        foreach ($deployments as $item) {
            $assets = $item['assets'];
            $checks = $item['checks'];
            $clientCode = $item['client_code'];
            $packageCode = $item['package_code'];
            unset($item['assets'], $item['checks'], $item['client_code'], $item['package_code']);

            $deployment = Deployment::updateOrCreate(
                ['name' => $item['name']],
                [
                    ...$item,
                    'client_id' => $clients[$clientCode] ?? null,
                    'product_id' => $scaleLens?->id,
                    'package_id' => $packages[$packageCode] ?? null,
                    'environment' => $item['environment'],
                    'status' => $item['status']->value,
                ],
            );

            foreach ($assets as $asset) {
                $deployment->infrastructureAssets()->updateOrCreate(
                    ['name' => $asset['name']],
                    [
                        ...$asset,
                        'type' => $asset['type'],
                        'currency' => config('catalogue.default_currency'),
                    ],
                );
            }

            foreach ($checks as $check) {
                $deployment->monitoringChecks()->updateOrCreate(
                    ['name' => $check['name']],
                    [
                        ...$check,
                        'status' => $check['status']->value,
                        'last_checked_at' => now()->subMinutes(5),
                        'last_success_at' => $check['status'] === MonitoringCheckStatus::Passing ? now()->subMinutes(5) : now()->subHours(18),
                    ],
                );
            }
        }
    }
}
