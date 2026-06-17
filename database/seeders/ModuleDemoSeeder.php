<?php

namespace Database\Seeders;

use App\Models\ModuleDemoRecord;
use Illuminate\Database\Seeder;

class ModuleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'clients' => [
                'permission' => 'clients.view',
                'records' => [
                    ['Client', 'Aurecon Programme Office', 'Enterprise portfolio visibility rollout with active governance workshops.', 'active', ['Tier' => 'Enterprise', 'Health' => '82', 'Owner' => 'Sales']],
                    ['Client', 'Nala Projects', 'Growth-tier implementation proposal in assessment stage.', 'processing', ['Tier' => 'Growth', 'Health' => '74', 'Owner' => 'Operations']],
                    ['Client', 'Kopano Consulting', 'Starter package client used for reporting and support rehearsal data.', 'active', ['Tier' => 'Starter', 'Health' => '91', 'Owner' => 'Support']],
                ],
            ],
            'commercial.opportunities' => [
                'permission' => 'opportunities.view',
                'records' => [
                    ['Opportunity', 'Municipal PMO visibility assessment', 'Assessment scoped for multi-project governance and reporting gaps.', 'processing', ['Value' => 'R35,000', 'Stage' => 'Proposal', 'Close' => 'Jul 2026']],
                    ['Opportunity', 'Construction portfolio dashboard', 'Enterprise package conversation with integration API requirement.', 'active', ['Value' => 'R680,000', 'Stage' => 'Discovery', 'Close' => 'Aug 2026']],
                    ['Opportunity', 'Consultancy project controls starter', 'Starter implementation for a compact delivery team.', 'active', ['Value' => 'R240,000', 'Stage' => 'Qualified', 'Close' => 'Jul 2026']],
                ],
            ],
            'commercial.contracts' => [
                'permission' => 'contracts.view',
                'records' => [
                    ['Contract', 'ScaleLens Enterprise implementation', 'Draft fixed-scope implementation contract linked to ScaleLens Enterprise.', 'processing', ['Value' => 'R720,000', 'Status' => 'Draft', 'Term' => 'Once-off']],
                    ['Contract', 'Priority support retainer', 'Recurring support commitment attached to the active pilot client.', 'active', ['MRR' => 'R35,000', 'SLA' => '24h', 'Hours' => '20']],
                    ['Contract', 'Managed hosting recovery fee', 'Monthly infrastructure recovery line for managed production hosting.', 'active', ['MRR' => 'R4,500', 'Provider' => 'Sail', 'Status' => 'Active']],
                ],
            ],
            'commercial.billing' => [
                'permission' => 'billing.view',
                'records' => [
                    ['Billing', 'Implementation milestone 1', 'Discovery and configuration milestone ready for invoice draft.', 'processing', ['Amount' => 'R180,000', 'Due' => '2026-07-05', 'Type' => 'Once-off']],
                    ['Billing', 'Priority support retainer', 'Monthly recurring support line for active account.', 'active', ['Amount' => 'R35,000', 'Cycle' => 'Monthly', 'Type' => 'Recurring']],
                    ['Billing', 'Managed hosting', 'Infrastructure recovery fee for production environment.', 'active', ['Amount' => 'R4,500', 'Cycle' => 'Monthly', 'Type' => 'Recurring']],
                ],
            ],
            'commercial.profitability' => [
                'permission' => 'profitability.view',
                'records' => [
                    ['Profitability', 'Enterprise implementation margin', 'Planned delivery effort against fixed implementation value.', 'active', ['Revenue' => 'R720,000', 'Cost' => 'R390,000', 'Margin' => '46%']],
                    ['Profitability', 'Priority support utilisation', 'Support hours consumed against included monthly allocation.', 'processing', ['Included' => '20h', 'Used' => '12h', 'Remaining' => '8h']],
                    ['Profitability', 'Hosting recovery', 'Monthly recovery fee compared with managed infrastructure cost.', 'active', ['Revenue' => 'R4,500', 'Cost' => 'R2,100', 'Margin' => '53%']],
                ],
            ],
            'operations.deployments' => [
                'permission' => 'deployments.view',
                'records' => [
                    ['Deployment', 'ScaleLens Production', 'Production environment for the flagship programme office demo.', 'active', ['Version' => 'v1.8.2', 'Region' => 'af-south-1', 'Health' => 'Operational']],
                    ['Deployment', 'ScaleLens Staging', 'Release validation environment for package and reporting changes.', 'processing', ['Version' => 'v1.9.0-rc', 'Region' => 'af-south-1', 'Health' => 'Watching']],
                    ['Deployment', 'Client sandbox', 'Seed sandbox used for demos and onboarding rehearsal.', 'active', ['Version' => 'v1.8.1', 'Region' => 'eu-west-1', 'Health' => 'Operational']],
                ],
            ],
            'operations.infrastructure' => [
                'permission' => 'infrastructure.view',
                'records' => [
                    ['Asset', 'Production app node', 'Primary application container for managed ScaleLens production.', 'active', ['Provider' => 'AWS', 'Size' => '2 vCPU / 4GB', 'Monthly' => 'R1,850']],
                    ['Asset', 'PostgreSQL managed database', 'Registry database target with backups and monitoring.', 'active', ['Provider' => 'AWS', 'Size' => 'db.t4g.small', 'Monthly' => 'R1,250']],
                    ['Asset', 'Redis queue backend', 'Queue and cache backend for jobs and Horizon readiness.', 'active', ['Provider' => 'AWS', 'Size' => 'cache.t4g.micro', 'Monthly' => 'R420']],
                ],
            ],
            'operations.monitoring' => [
                'permission' => 'monitoring.view',
                'records' => [
                    ['Check', 'Production uptime', 'HTTP availability check for the production application URL.', 'active', ['Status' => 'Passing', 'Interval' => '60s', 'Last' => '2 min ago']],
                    ['Check', 'SSL certificate', 'Certificate expiry and chain validation for public endpoints.', 'active', ['Status' => 'Passing', 'Expiry' => '72 days', 'Last' => '1h ago']],
                    ['Check', 'Nightly backups', 'Database backup freshness and restore-point validation.', 'processing', ['Status' => 'Watching', 'Age' => '18h', 'Last' => '18h ago']],
                ],
            ],
            'operations.incidents' => [
                'permission' => 'incidents.view',
                'records' => [
                    ['Incident', 'Delayed backup confirmation', 'Backup confirmation exceeded the warning threshold and is under review.', 'processing', ['Severity' => 'Medium', 'Opened' => '2026-06-16', 'Owner' => 'Operations']],
                    ['Incident', 'Staging queue saturation', 'Queue workers lagged during release rehearsal and recovered after scaling.', 'resolved', ['Severity' => 'Low', 'Opened' => '2026-06-14', 'Owner' => 'Technical']],
                    ['Incident', 'SSL monitor false positive', 'Monitor misread intermediate chain and was tuned.', 'resolved', ['Severity' => 'Low', 'Opened' => '2026-06-10', 'Owner' => 'Support']],
                ],
            ],
            'operations.releases' => [
                'permission' => 'releases.view',
                'records' => [
                    ['Release', 'Catalogue pricing update', 'Package pricing range and catalogue feature support branch.', 'processing', ['Version' => 'v1.9.0', 'Window' => '2026-06-18', 'Risk' => 'Low']],
                    ['Release', 'Profile security polish', 'Two-factor and recovery code UI alignment release.', 'active', ['Version' => 'v1.8.2', 'Window' => '2026-06-12', 'Risk' => 'Low']],
                    ['Release', 'Foundation shell', 'Initial navigation and module placeholder release.', 'active', ['Version' => 'v1.8.0', 'Window' => '2026-06-01', 'Risk' => 'Low']],
                ],
            ],
            'support.agreements' => [
                'permission' => 'support_agreements.view',
                'records' => [
                    ['Agreement', 'Priority support - Nala Projects', 'Active priority retainer linked to the Growth implementation path.', 'active', ['Monthly' => 'R35,000', 'Hours' => '20', 'SLA' => '24h']],
                    ['Agreement', 'Strategic support - Enterprise PMO', 'Strategic support proposal for business-critical programme office.', 'processing', ['Monthly' => 'R60,000', 'Hours' => '40', 'SLA' => '8h']],
                    ['Agreement', 'Standard support - Sandbox client', 'Standard retainer used for stable smaller environment rehearsal.', 'active', ['Monthly' => 'R20,000', 'Hours' => '10', 'SLA' => '48h']],
                ],
            ],
            'support.tickets' => [
                'permission' => 'support_tickets.view',
                'records' => [
                    ['Ticket', 'Dashboard export formatting', 'Client requested PDF spacing polish for executive pack.', 'processing', ['Severity' => 'Medium', 'Hours' => '2.5', 'SLA' => 'On track']],
                    ['Ticket', 'User access request', 'Add PMO stakeholder access to staging workspace.', 'active', ['Severity' => 'Low', 'Hours' => '0.5', 'SLA' => 'On track']],
                    ['Ticket', 'Report date filter question', 'Support answered report filtering query and closed the thread.', 'resolved', ['Severity' => 'Low', 'Hours' => '1.0', 'SLA' => 'Met']],
                ],
            ],
            'support.sla-overview' => [
                'permission' => 'support_tickets.view',
                'records' => [
                    ['SLA', 'Priority queue', 'Open priority tickets remain inside the 24-hour response target.', 'active', ['Open' => '2', 'At risk' => '0', 'Target' => '24h']],
                    ['SLA', 'Strategic queue', 'Strategic retainer proposal has no active live tickets yet.', 'active', ['Open' => '0', 'At risk' => '0', 'Target' => '8h']],
                    ['SLA', 'Standard queue', 'Standard support queue has one resolved ticket this cycle.', 'resolved', ['Open' => '0', 'Closed' => '1', 'Target' => '48h']],
                ],
            ],
        ];

        foreach ($pages as $pageKey => $page) {
            foreach ($page['records'] as $index => [$label, $headline, $summary, $status, $metrics]) {
                ModuleDemoRecord::updateOrCreate(
                    [
                        'page_key' => $pageKey,
                        'headline' => $headline,
                    ],
                    [
                        'permission' => $page['permission'],
                        'label' => $label,
                        'summary' => $summary,
                        'status' => $status,
                        'metrics' => $metrics,
                        'sort_order' => $index + 1,
                    ],
                );
            }
        }
    }
}
