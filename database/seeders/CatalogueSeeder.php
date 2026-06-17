<?php

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Enums\CatalogueStatus;
use App\Models\Package;
use App\Models\Product;
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
    }
}
