<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederProductionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeders_are_skipped_in_production(): void
    {
        app()['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'admin@codescaletech.test']);
        $this->assertDatabaseCount('clients', 0);

        app()['env'] = 'testing';
    }
}
