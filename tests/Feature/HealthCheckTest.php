<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_ok_when_everything_is_reachable(): void
    {
        // The real default disk is S3 (config/filesystems.php,
        // FILESYSTEM_DISK); faking it here tests the health check's own
        // round-trip logic without needing real S3 credentials in CI. The
        // live endpoint was verified separately against the real dev
        // environment (real S3-backed disk, real Redis).
        Storage::fake(config('filesystems.default'));

        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.ok', true)
            ->assertJsonPath('checks.cache.ok', true)
            ->assertJsonPath('checks.storage.ok', true);
    }

    public function test_health_endpoint_is_unauthenticated(): void
    {
        Storage::fake(config('filesystems.default'));

        $this->getJson('/api/health')->assertOk();
    }
}
