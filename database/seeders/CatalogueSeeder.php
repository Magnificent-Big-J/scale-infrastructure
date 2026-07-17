<?php

namespace Database\Seeders;

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
                    'billing_interval' => 'once_off',
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
