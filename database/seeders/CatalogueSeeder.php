<?php

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Enums\CatalogueStatus;
use App\Models\CatalogueFeature;
use App\Models\Package;
use App\Models\Product;
use App\Models\SupportTier;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $scaleLens = Product::updateOrCreate(
            ['code' => 'SCALELENS'],
            [
                'name' => 'ScaleLens',
                'description' => 'Operational analytics and monitoring platform — Code Scale Tech\'s flagship product.',
                'status' => CatalogueStatus::Active,
            ],
        );

        // Packages are feature-based, once-off implementations with indicative
        // price ranges (confirmed after a Project Visibility Assessment).
        // Source: Documentation 26 - ScaleLens Packages, Features & Pricing.
        $packages = [
            [
                'code' => 'SCALELENS-STARTER',
                'name' => 'Starter',
                'description' => 'Smaller teams and consultancies getting projects under control: clients, projects, stages, dashboards, health scoring, standard reports, and audit trail.',
                'price_min' => 200000.00,
                'price_max' => 300000.00,
                'sort_order' => 1,
            ],
            [
                'code' => 'SCALELENS-GROWTH',
                'name' => 'Growth',
                'description' => 'Multi-project organisations and PMOs needing governance. Everything in Starter, plus risk, action and stakeholder registers, meetings and decision log, and advanced reporting.',
                'price_min' => 300000.00,
                'price_max' => 600000.00,
                'sort_order' => 2,
            ],
            [
                'code' => 'SCALELENS-ENTERPRISE',
                'name' => 'Enterprise',
                'description' => 'Large organisations and programme offices needing full document control and portfolio oversight. Everything in Growth, plus document register and version control, approvals and transmittals, portfolio dashboards, custom workflows, and integration APIs.',
                'price_min' => 600000.00,
                'price_max' => null,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['code' => $package['code']],
                [
                    'product_id' => $scaleLens->id,
                    'name' => $package['name'],
                    'description' => $package['description'],
                    'billing_interval' => BillingInterval::OnceOff,
                    'price_min' => $package['price_min'],
                    'price_max' => $package['price_max'],
                    'currency' => config('catalogue.default_currency'),
                    'status' => CatalogueStatus::Active,
                    'sort_order' => $package['sort_order'],
                ],
            );
        }

        $packageIds = Package::query()
            ->whereIn('code', ['SCALELENS-STARTER', 'SCALELENS-GROWTH', 'SCALELENS-ENTERPRISE'])
            ->pluck('id', 'code');

        $features = [
            ['code' => 'SCALELENS-FEATURE-PROJECTS', 'name' => 'Projects, teams, stages, milestones', 'minimum_package_code' => 'SCALELENS-STARTER', 'sort_order' => 1],
            ['code' => 'SCALELENS-FEATURE-DASHBOARDS', 'name' => 'Project & executive dashboards', 'minimum_package_code' => 'SCALELENS-STARTER', 'sort_order' => 2],
            ['code' => 'SCALELENS-FEATURE-HEALTH-SCORING', 'name' => 'Project health scoring', 'minimum_package_code' => 'SCALELENS-STARTER', 'sort_order' => 3],
            ['code' => 'SCALELENS-FEATURE-STANDARD-REPORTS', 'name' => 'Standard reports', 'minimum_package_code' => 'SCALELENS-STARTER', 'sort_order' => 4],
            ['code' => 'SCALELENS-FEATURE-AUDIT-SEARCH-NOTIFICATIONS', 'name' => 'Audit trail, search, notifications', 'minimum_package_code' => 'SCALELENS-STARTER', 'sort_order' => 5],
            ['code' => 'SCALELENS-FEATURE-RISKS', 'name' => 'Risks', 'minimum_package_code' => 'SCALELENS-GROWTH', 'sort_order' => 6],
            ['code' => 'SCALELENS-FEATURE-ACTIONS-ESCALATIONS', 'name' => 'Actions & escalations', 'minimum_package_code' => 'SCALELENS-GROWTH', 'sort_order' => 7],
            ['code' => 'SCALELENS-FEATURE-STAKEHOLDERS', 'name' => 'Stakeholders', 'minimum_package_code' => 'SCALELENS-GROWTH', 'sort_order' => 8],
            ['code' => 'SCALELENS-FEATURE-MEETINGS-DECISIONS', 'name' => 'Meetings & decision log', 'minimum_package_code' => 'SCALELENS-GROWTH', 'sort_order' => 9],
            ['code' => 'SCALELENS-FEATURE-ADVANCED-REPORTING', 'name' => 'Advanced reporting', 'minimum_package_code' => 'SCALELENS-GROWTH', 'sort_order' => 10],
            ['code' => 'SCALELENS-FEATURE-DOCUMENT-REGISTER', 'name' => 'Document register & version control', 'minimum_package_code' => 'SCALELENS-ENTERPRISE', 'sort_order' => 11],
            ['code' => 'SCALELENS-FEATURE-APPROVALS-TRANSMITTALS', 'name' => 'Approvals & transmittals', 'minimum_package_code' => 'SCALELENS-ENTERPRISE', 'sort_order' => 12],
            ['code' => 'SCALELENS-FEATURE-PORTFOLIO-DASHBOARDS', 'name' => 'Portfolio dashboards', 'minimum_package_code' => 'SCALELENS-ENTERPRISE', 'sort_order' => 13],
            ['code' => 'SCALELENS-FEATURE-CUSTOM-REPORTS-WORKFLOWS', 'name' => 'Custom reports & workflows', 'minimum_package_code' => 'SCALELENS-ENTERPRISE', 'sort_order' => 14],
            ['code' => 'SCALELENS-FEATURE-INTEGRATION-APIS', 'name' => 'Integration APIs', 'minimum_package_code' => 'SCALELENS-ENTERPRISE', 'sort_order' => 15],
        ];

        foreach ($features as $feature) {
            CatalogueFeature::updateOrCreate(
                ['code' => $feature['code']],
                [
                    'product_id' => $scaleLens->id,
                    'minimum_package_id' => $packageIds[$feature['minimum_package_code']] ?? null,
                    'name' => $feature['name'],
                    'description' => 'ScaleLens package capability from Documentation 26.',
                    'status' => CatalogueStatus::Active,
                    'sort_order' => $feature['sort_order'],
                ],
            );
        }

        $procurement = Product::updateOrCreate(
            ['code' => 'PROCUREMENT-VISIBILITY'],
            [
                'name' => 'Procurement Visibility Platform',
                'description' => 'Governed source-to-pay visibility platform for supplier compliance, procurement approvals, finance controls, audit evidence, and POPIA-aware document retention.',
                'status' => CatalogueStatus::Active,
            ],
        );

        // Procurement is materially heavier than ScaleLens: regulated supplier
        // onboarding, separation of duties, finance matching, evidence retention,
        // and audit/POPIA controls drive higher implementation ranges.
        $procurementPackages = [
            [
                'code' => 'PROCUREMENT-FOUNDATION',
                'name' => 'Foundation',
                'description' => 'Smaller procurement teams needing controlled supplier, requisition, purchase order, delivery, invoice, payment, dashboard, notification, reporting, and audit visibility.',
                'price_min' => 450000.00,
                'price_max' => 700000.00,
                'sort_order' => 1,
            ],
            [
                'code' => 'PROCUREMENT-GOVERNANCE',
                'name' => 'Governance',
                'description' => 'Procurement departments needing governed RFQs, supplier eligibility, quotations, evaluations, awards, policies, delegations, escalations, exceptions, compliance, spend analysis, and three-way invoice matching.',
                'price_min' => 750000.00,
                'price_max' => 1250000.00,
                'sort_order' => 2,
            ],
            [
                'code' => 'PROCUREMENT-ENTERPRISE',
                'name' => 'Enterprise',
                'description' => 'Regulated or multi-entity environments needing banking proof controls, versioned documents, inline previews, retention review, activity timelines, advanced automation, custom workflows, custom reports, and integration APIs.',
                'price_min' => 1500000.00,
                'price_max' => null,
                'sort_order' => 3,
            ],
        ];

        foreach ($procurementPackages as $package) {
            Package::updateOrCreate(
                ['code' => $package['code']],
                [
                    'product_id' => $procurement->id,
                    'name' => $package['name'],
                    'description' => $package['description'],
                    'billing_interval' => BillingInterval::OnceOff,
                    'price_min' => $package['price_min'],
                    'price_max' => $package['price_max'],
                    'currency' => config('catalogue.default_currency'),
                    'status' => CatalogueStatus::Active,
                    'sort_order' => $package['sort_order'],
                ],
            );
        }

        $procurementPackageIds = Package::query()
            ->whereIn('code', ['PROCUREMENT-FOUNDATION', 'PROCUREMENT-GOVERNANCE', 'PROCUREMENT-ENTERPRISE'])
            ->pluck('id', 'code');

        $procurementFeatures = [
            ['code' => 'PROCUREMENT-FEATURE-ORG-ADMIN', 'name' => 'Organisation setup, users, roles, departments, business units, cost centres', 'minimum_package_code' => 'PROCUREMENT-FOUNDATION', 'sort_order' => 101],
            ['code' => 'PROCUREMENT-FEATURE-SUPPLIERS', 'name' => 'Supplier registry, contacts, addresses, compliance status, health scoring', 'minimum_package_code' => 'PROCUREMENT-FOUNDATION', 'sort_order' => 102],
            ['code' => 'PROCUREMENT-FEATURE-REQUISITIONS-APPROVALS', 'name' => 'Requisitions, approval queue, configurable approval matrices', 'minimum_package_code' => 'PROCUREMENT-FOUNDATION', 'sort_order' => 103],
            ['code' => 'PROCUREMENT-FEATURE-PURCHASING-FINANCE-BASIC', 'name' => 'Purchase orders, deliveries, GRNs, invoice capture, payment tracking', 'minimum_package_code' => 'PROCUREMENT-FOUNDATION', 'sort_order' => 104],
            ['code' => 'PROCUREMENT-FEATURE-DASHBOARDS-REPORTS', 'name' => 'Dashboard metrics, notifications, search, standard reports, exports', 'minimum_package_code' => 'PROCUREMENT-FOUNDATION', 'sort_order' => 105],
            ['code' => 'PROCUREMENT-FEATURE-RFQ-QUOTATIONS', 'name' => 'RFQs, invitations, supplier eligibility, quotations', 'minimum_package_code' => 'PROCUREMENT-GOVERNANCE', 'sort_order' => 106],
            ['code' => 'PROCUREMENT-FEATURE-EVALUATIONS-AWARDS', 'name' => 'Evaluation scoring, award recommendations, quotation comparison', 'minimum_package_code' => 'PROCUREMENT-GOVERNANCE', 'sort_order' => 107],
            ['code' => 'PROCUREMENT-FEATURE-GOVERNANCE-RULES', 'name' => 'Policies, method rules, delegations, escalations, exceptions', 'minimum_package_code' => 'PROCUREMENT-GOVERNANCE', 'sort_order' => 108],
            ['code' => 'PROCUREMENT-FEATURE-MATCHING-SPEND', 'name' => 'Three-way invoice matching and spend analysis', 'minimum_package_code' => 'PROCUREMENT-GOVERNANCE', 'sort_order' => 109],
            ['code' => 'PROCUREMENT-FEATURE-COMPLIANCE-AUDIT', 'name' => 'Compliance dashboard and sensitive-action audit coverage', 'minimum_package_code' => 'PROCUREMENT-GOVERNANCE', 'sort_order' => 110],
            ['code' => 'PROCUREMENT-FEATURE-BANKING-PROOF', 'name' => 'Banking proof-of-account controls and document version history', 'minimum_package_code' => 'PROCUREMENT-ENTERPRISE', 'sort_order' => 111],
            ['code' => 'PROCUREMENT-FEATURE-DOCUMENTS-RETENTION', 'name' => 'Entity document management, inline previews, retention review', 'minimum_package_code' => 'PROCUREMENT-ENTERPRISE', 'sort_order' => 112],
            ['code' => 'PROCUREMENT-FEATURE-ACTIVITY-TIMELINES', 'name' => 'Activity timelines across key records', 'minimum_package_code' => 'PROCUREMENT-ENTERPRISE', 'sort_order' => 113],
            ['code' => 'PROCUREMENT-FEATURE-AUTOMATION-CUSTOM', 'name' => 'Advanced automation, custom workflows, custom reports, integration APIs', 'minimum_package_code' => 'PROCUREMENT-ENTERPRISE', 'sort_order' => 114],
            ['code' => 'PROCUREMENT-FEATURE-MULTI-ENTITY', 'name' => 'Multi-entity, white-label, and regulated deployment patterns', 'minimum_package_code' => 'PROCUREMENT-ENTERPRISE', 'sort_order' => 115],
        ];

        foreach ($procurementFeatures as $feature) {
            CatalogueFeature::updateOrCreate(
                ['code' => $feature['code']],
                [
                    'product_id' => $procurement->id,
                    'minimum_package_id' => $procurementPackageIds[$feature['minimum_package_code']] ?? null,
                    'name' => $feature['name'],
                    'description' => 'Procurement Visibility Platform package capability from Documentation 28.',
                    'status' => CatalogueStatus::Active,
                    'sort_order' => $feature['sort_order'],
                ],
            );
        }

        $supportTiers = [
            [
                'code' => 'SUPPORT-STANDARD',
                'name' => 'Standard',
                'monthly_fee' => 20000.00,
                'included_hours' => 10,
                'response_sla_hours' => 48,
                'service_review' => 'Monthly report',
                'best_for' => 'Stable, smaller environments',
                'sort_order' => 1,
            ],
            [
                'code' => 'SUPPORT-PRIORITY',
                'name' => 'Priority',
                'monthly_fee' => 35000.00,
                'included_hours' => 20,
                'response_sla_hours' => 24,
                'service_review' => 'Monthly review + monitoring',
                'best_for' => 'Most clients & growing organisations',
                'sort_order' => 2,
            ],
            [
                'code' => 'SUPPORT-STRATEGIC',
                'name' => 'Strategic',
                'monthly_fee' => 60000.00,
                'included_hours' => 40,
                'response_sla_hours' => 8,
                'service_review' => 'Quarterly business review + infrastructure health review',
                'best_for' => 'Enterprise / business-critical',
                'sort_order' => 3,
            ],
        ];

        foreach ($supportTiers as $supportTier) {
            SupportTier::updateOrCreate(
                ['code' => $supportTier['code']],
                [
                    'name' => $supportTier['name'],
                    'description' => 'Monthly support retainer from Documentation 26.',
                    'monthly_fee' => $supportTier['monthly_fee'],
                    'included_hours' => $supportTier['included_hours'],
                    'response_sla_hours' => $supportTier['response_sla_hours'],
                    'service_review' => $supportTier['service_review'],
                    'best_for' => $supportTier['best_for'],
                    'currency' => config('catalogue.default_currency'),
                    'status' => CatalogueStatus::Active,
                    'sort_order' => $supportTier['sort_order'],
                ],
            );
        }
    }
}
