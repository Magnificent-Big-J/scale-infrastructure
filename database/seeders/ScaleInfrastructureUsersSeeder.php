<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Rainwaves\LaraAuthSuite\TwoFactor\Contracts\ITwoFactorAuth;

class ScaleInfrastructureUsersSeeder extends Seeder
{
    public function __construct(private ITwoFactorAuth $twoFactor)
    {
    }

    public function run(): void
    {
        $administrator = $this->upsertUser('admin@codescaletech.test', 'Scale Infrastructure Admin', 'administrator');
        $operations = $this->upsertUser('operations@codescaletech.test', 'Operations Lead', 'operations');

        $this->upsertUser('executive@codescaletech.test', 'Executive User', 'executive');
        $this->upsertUser('finance@codescaletech.test', 'Finance User', 'finance');
        $this->upsertUser('sales@codescaletech.test', 'Sales User', 'sales');
        $this->upsertUser('support@codescaletech.test', 'Support User', 'support');
        $this->upsertUser('technical@codescaletech.test', 'Technical User', 'technical');

        if (! $administrator->hasRole('administrator')) {
            $administrator->syncRoles(['administrator']);
        }

        $this->twoFactor->enableEmailOtp($operations);
    }

    private function upsertUser(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now(),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }
}
