<?php

namespace Database\Seeders;

use App\Enums\AutomationRunStatus;
use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRisk;
use App\Enums\ReleaseStatus;
use App\Models\AutomationRun;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\ProvisioningTemplate;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReleaseOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::query()->pluck('id', 'code');
        $deployments = Deployment::query()->pluck('id', 'name');
        $admin = User::where('email', 'admin@codescaletech.test')->first();

        $template = ProvisioningTemplate::updateOrCreate(
            ['code' => 'PROV-DO-LARAVEL'],
            [
                'name' => 'DigitalOcean Laravel app',
                'provider' => 'DigitalOcean',
                'summary' => 'Repeatable provisioning for a Laravel app droplet with managed PostgreSQL.',
                'steps' => [
                    'Create droplet from base image',
                    'Attach managed PostgreSQL database',
                    'Configure environment and secrets',
                    'Deploy application and run migrations',
                    'Register monitoring checks',
                ],
                'is_active' => true,
            ],
        );

        $approved = ChangeRequest::updateOrCreate(
            ['reference' => 'CR-3001'],
            [
                'deployment_id' => $deployments['ScaleLens Production'] ?? null,
                'client_id' => $clients['AURECON-PMO'] ?? null,
                'title' => 'Production reporting hotfix release',
                'description' => 'Approved change to deploy the reporting latency hotfix to production.',
                'risk' => ChangeRisk::Medium->value,
                'status' => ChangeRequestStatus::Approved->value,
                'requested_by' => $admin?->id,
                'approved_by' => $admin?->id,
                'approved_at' => now()->subDays(2),
                'scheduled_for' => now()->subDay()->toDateString(),
                'notes' => 'Seeded Module 06 change request.',
            ],
        );

        ChangeRequest::updateOrCreate(
            ['reference' => 'CR-3002'],
            [
                'deployment_id' => $deployments['ScaleLens Staging'] ?? null,
                'client_id' => $clients['NALA-PROJECTS'] ?? null,
                'title' => 'Staging queue tuning',
                'description' => 'Requested change to adjust staging worker counts.',
                'risk' => ChangeRisk::Low->value,
                'status' => ChangeRequestStatus::Submitted->value,
                'requested_by' => $admin?->id,
                'notes' => 'Seeded Module 06 change request.',
            ],
        );

        Release::updateOrCreate(
            ['deployment_id' => $deployments['ScaleLens Production'] ?? null, 'version' => '2026.06.1'],
            [
                'change_request_id' => $approved->id,
                'status' => ReleaseStatus::Deployed->value,
                'notes' => 'Reporting latency hotfix.',
                'approved_by' => $admin?->id,
                'approved_at' => now()->subDays(2),
                'deployed_by' => $admin?->id,
                'deployed_at' => now()->subDay(),
            ],
        );

        Release::updateOrCreate(
            ['deployment_id' => $deployments['ScaleLens Staging'] ?? null, 'version' => '2026.06.0'],
            [
                'status' => ReleaseStatus::Approved->value,
                'notes' => 'Staging candidate build.',
                'approved_by' => $admin?->id,
                'approved_at' => now()->subHours(6),
            ],
        );

        Release::updateOrCreate(
            ['deployment_id' => $deployments['Client Sandbox'] ?? null, 'version' => '2026.05.3'],
            [
                'status' => ReleaseStatus::Draft->value,
                'notes' => 'Sandbox draft release.',
            ],
        );

        AutomationRun::updateOrCreate(
            ['reference' => 'RUN-4001'],
            [
                'provisioning_template_id' => $template->id,
                'deployment_id' => $deployments['ScaleLens Production'] ?? null,
                'client_id' => $clients['AURECON-PMO'] ?? null,
                'change_request_id' => $approved->id,
                'status' => AutomationRunStatus::Succeeded->value,
                'started_at' => now()->subDay(),
                'finished_at' => now()->subDay()->addMinutes(12),
                'output_summary' => 'Provisioning completed: droplet, database, and monitoring checks created.',
                'triggered_by' => $admin?->id,
            ],
        );
    }
}
