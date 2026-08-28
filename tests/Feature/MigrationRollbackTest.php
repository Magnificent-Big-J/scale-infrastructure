<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every test in this suite already exercises every migration's up() (via
 * RefreshDatabase before each test), but nothing exercises down() - a
 * broken rollback is only ever discovered the moment someone actually needs
 * to roll back in production. This runs the full migrate -> rollback ->
 * migrate cycle for real and asserts the schema survives it.
 */
class MigrationRollbackTest extends TestCase
{
    public function test_every_migration_can_be_rolled_back_and_reapplied(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $tablesAfterMigrate = $this->tableNames();
        $this->assertNotEmpty($tablesAfterMigrate);

        $migrationCount = DB::table('migrations')->count();
        $this->assertGreaterThan(0, $migrationCount);

        Artisan::call('migrate:rollback', ['--step' => $migrationCount, '--force' => true]);

        $remainingTables = array_diff($this->tableNames(), ['migrations']);
        $this->assertEmpty(
            $remainingTables,
            'Rolling back every migration should leave no application tables behind: '.implode(', ', $remainingTables)
        );

        Artisan::call('migrate', ['--force' => true]);
        $this->assertEquals($tablesAfterMigrate, $this->tableNames());
    }

    /**
     * @return list<string>
     */
    private function tableNames(): array
    {
        return DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'not like', 'sqlite_%')
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }
}
