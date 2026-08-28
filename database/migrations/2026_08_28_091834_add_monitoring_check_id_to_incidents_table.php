<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Traces an incident back to the check that opened it, so a
            // recovered check can find and auto-resolve the incident it
            // raised instead of leaving it open or opening a duplicate.
            $table->foreignUuid('monitoring_check_id')->nullable()->after('deployment_id')
                ->constrained('monitoring_checks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Drop the FK before the column - see 2026_06_23_000000's down()
            // for why SQLite needs constraints dropped explicitly first.
            $table->dropForeign(['monitoring_check_id']);
            $table->dropColumn('monitoring_check_id');
        });
    }
};
