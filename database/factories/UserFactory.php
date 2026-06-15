<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function administrator(): static
    {
        return $this
            ->state(fn () => [
                'name' => 'Scale Infrastructure Admin',
                'email' => 'admin@codescaletech.test',
                'email_verified_at' => Carbon::now(),
            ])
            ->afterCreating(function (User $user): void {
                $user->syncRoles(['administrator']);
            });
    }

    public function operations(): static
    {
        return $this
            ->state(fn () => [
                'name' => 'Operations Lead',
                'email' => 'operations@codescaletech.test',
                'email_verified_at' => Carbon::now(),
            ])
            ->afterCreating(function (User $user): void {
                $user->syncRoles(['operations']);
            });
    }

    public function finance(): static
    {
        return $this
            ->state(fn () => [
                'name' => 'Finance User',
                'email' => 'finance@codescaletech.test',
                'email_verified_at' => Carbon::now(),
            ])
            ->afterCreating(function (User $user): void {
                $user->syncRoles(['finance']);
            });
    }
}
