<?php

namespace Database\Seeders;

use App\Enums\IncidentStatus;
use App\Enums\SupportAgreementStatus;
use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\Client;
use App\Models\Deployment;
use App\Models\Incident;
use App\Models\SupportAgreement;
use App\Models\SupportTicket;
use App\Models\SupportTier;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupportOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::query()->pluck('id', 'code');
        $deployments = Deployment::query()->pluck('id', 'name');
        $tiers = SupportTier::query()->pluck('id', 'code');
        $supportUser = User::where('email', 'support@codescaletech.test')->first();

        $agreements = [
            ['client_code' => 'NALA-PROJECTS', 'tier_code' => 'SUPPORT-PRIORITY', 'code' => 'AGR-NALA-PRIORITY', 'name' => 'Priority support - Nala Projects', 'monthly_fee' => 35000, 'included_hours' => 20, 'response_sla_hours' => 24, 'starts_on' => '2026-06-01', 'status' => SupportAgreementStatus::Active],
            ['client_code' => 'AURECON-PMO', 'tier_code' => 'SUPPORT-STRATEGIC', 'code' => 'AGR-AURECON-STRATEGIC', 'name' => 'Strategic support - Aurecon PMO', 'monthly_fee' => 60000, 'included_hours' => 40, 'response_sla_hours' => 8, 'starts_on' => '2026-06-01', 'status' => SupportAgreementStatus::Review],
            ['client_code' => 'KOPANO-CONSULTING', 'tier_code' => 'SUPPORT-STANDARD', 'code' => 'AGR-KOPANO-STANDARD', 'name' => 'Standard support - Kopano Consulting', 'monthly_fee' => 20000, 'included_hours' => 10, 'response_sla_hours' => 48, 'starts_on' => '2026-05-20', 'status' => SupportAgreementStatus::Active],
        ];

        foreach ($agreements as $agreement) {
            $clientCode = $agreement['client_code'];
            $tierCode = $agreement['tier_code'];
            unset($agreement['client_code'], $agreement['tier_code']);

            SupportAgreement::updateOrCreate(
                ['code' => $agreement['code']],
                [
                    ...$agreement,
                    'client_id' => $clients[$clientCode] ?? null,
                    'support_tier_id' => $tiers[$tierCode] ?? null,
                    'status' => $agreement['status']->value,
                    'notes' => 'Seeded Module 03 support agreement.',
                ],
            );
        }

        $agreementIds = SupportAgreement::query()->pluck('id', 'code');

        $tickets = [
            ['client_code' => 'NALA-PROJECTS', 'deployment' => 'ScaleLens Staging', 'agreement_code' => 'AGR-NALA-PRIORITY', 'reference' => 'TCK-1001', 'subject' => 'Dashboard export formatting', 'category' => 'reporting', 'severity' => SupportSeverity::Medium, 'status' => SupportTicketStatus::InProgress, 'hours_logged' => 2.5, 'summary' => 'Client requested PDF spacing polish for executive pack.'],
            ['client_code' => 'AURECON-PMO', 'deployment' => 'ScaleLens Production', 'agreement_code' => 'AGR-AURECON-STRATEGIC', 'reference' => 'TCK-1002', 'subject' => 'Nightly backup confirmation delay', 'category' => 'backup', 'severity' => SupportSeverity::High, 'status' => SupportTicketStatus::Open, 'hours_logged' => 1.25, 'summary' => 'Backup confirmation exceeded warning threshold and is being reviewed.'],
            ['client_code' => 'KOPANO-CONSULTING', 'deployment' => 'Client Sandbox', 'agreement_code' => 'AGR-KOPANO-STANDARD', 'reference' => 'TCK-1003', 'subject' => 'Report date filter question', 'category' => 'support', 'severity' => SupportSeverity::Low, 'status' => SupportTicketStatus::Resolved, 'hours_logged' => 1.0, 'summary' => 'Support answered report filtering query and closed the thread.'],
        ];

        foreach ($tickets as $ticket) {
            $clientCode = $ticket['client_code'];
            $deploymentName = $ticket['deployment'];
            $agreementCode = $ticket['agreement_code'];
            unset($ticket['client_code'], $ticket['deployment'], $ticket['agreement_code']);

            SupportTicket::updateOrCreate(
                ['reference' => $ticket['reference']],
                [
                    ...$ticket,
                    'client_id' => $clients[$clientCode] ?? null,
                    'deployment_id' => $deployments[$deploymentName] ?? null,
                    'support_agreement_id' => $agreementIds[$agreementCode] ?? null,
                    'assigned_user_id' => $supportUser?->id,
                    'severity' => $ticket['severity']->value,
                    'status' => $ticket['status']->value,
                    'opened_at' => now()->subDays(2),
                    'resolved_at' => $ticket['status'] === SupportTicketStatus::Resolved ? now()->subDay() : null,
                ],
            );
        }

        $incidents = [
            ['client_code' => 'AURECON-PMO', 'deployment' => 'ScaleLens Production', 'reference' => 'INC-2001', 'title' => 'Delayed backup confirmation', 'severity' => SupportSeverity::High, 'status' => IncidentStatus::Investigating, 'root_cause' => null, 'resolution_summary' => null],
            ['client_code' => 'NALA-PROJECTS', 'deployment' => 'ScaleLens Staging', 'reference' => 'INC-2002', 'title' => 'Staging queue saturation', 'severity' => SupportSeverity::Medium, 'status' => IncidentStatus::Resolved, 'root_cause' => 'Release rehearsal generated queued jobs faster than the staging worker limit.', 'resolution_summary' => 'Scaled worker count and added queue alerting.'],
            ['client_code' => 'KOPANO-CONSULTING', 'deployment' => 'Client Sandbox', 'reference' => 'INC-2003', 'title' => 'SSL monitor false positive', 'severity' => SupportSeverity::Low, 'status' => IncidentStatus::Closed, 'root_cause' => 'Intermediate certificate cache mismatch.', 'resolution_summary' => 'Monitor chain validation was tuned.'],
        ];

        foreach ($incidents as $incident) {
            $clientCode = $incident['client_code'];
            $deploymentName = $incident['deployment'];
            unset($incident['client_code'], $incident['deployment']);

            Incident::updateOrCreate(
                ['reference' => $incident['reference']],
                [
                    ...$incident,
                    'client_id' => $clients[$clientCode] ?? null,
                    'deployment_id' => $deployments[$deploymentName] ?? null,
                    'severity' => $incident['severity']->value,
                    'status' => $incident['status']->value,
                    'started_at' => now()->subDays(3),
                    'resolved_at' => in_array($incident['status'], [IncidentStatus::Resolved, IncidentStatus::Closed], true) ? now()->subDays(2) : null,
                ],
            );
        }
    }
}
