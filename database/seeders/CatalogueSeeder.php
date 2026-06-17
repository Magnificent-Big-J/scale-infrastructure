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

        $packages = [
            [
                'code' => 'SCALELENS-STARTER',
                'name' => 'Starter',
                'description' => 'Entry package for a single deployment with core monitoring.',
                'billing_interval' => BillingInterval::Monthly,
                'price' => 2500.00,
                'sort_order' => 1,
            ],
            [
                'code' => 'SCALELENS-GROWTH',
                'name' => 'Growth',
                'description' => 'Expanded monitoring, infrastructure tracking, and standard support.',
                'billing_interval' => BillingInterval::Monthly,
                'price' => 6500.00,
                'sort_order' => 2,
            ],
            [
                'code' => 'SCALELENS-SCALE',
                'name' => 'Scale',
                'description' => 'Full operational coverage with priority support and incident management.',
                'billing_interval' => BillingInterval::Monthly,
                'price' => 12500.00,
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
                    'billing_interval' => $package['billing_interval'],
                    'price' => $package['price'],
                    'currency' => config('catalogue.default_currency'),
                    'status' => CatalogueStatus::Active,
                    'sort_order' => $package['sort_order'],
                ],
            );
        }
    }
}
