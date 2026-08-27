<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Structural data only - roles/permissions and the managed reference-data
        // lists are required for the app to function and contain no fake
        // people, credentials, or business records. Safe in every environment.
        $this->call([
            RolesAndPermissionsSeeder::class,
            LookupOptionSeeder::class,
        ]);

        if (app()->environment('production')) {
            $this->command?->warn(
                'Skipping demo data seeders (fake users with a shared hardcoded '.
                'password, fake clients/deployments/tickets/etc.) - production '.
                'environment detected. Use `php artisan app:bootstrap-admin` '.
                'to create a real administrator account instead.'
            );

            return;
        }

        $this->call([
            ScaleInfrastructureUsersSeeder::class,
            CatalogueSeeder::class,
            ClientSeeder::class,
            DeploymentSeeder::class,
            SupportOperationsSeeder::class,
            CommercialSeeder::class,
            OpportunitySeeder::class,
            ProfitabilitySeeder::class,
            ReleaseOperationsSeeder::class,
            ModuleDemoSeeder::class,
        ]);
    }
}
