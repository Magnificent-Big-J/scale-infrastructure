<?php

use App\Enums\MonitoringCheckStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('name');
            $table->string('check_type');
            $table->string('target');
            $table->string('status')->default(MonitoringCheckStatus::Passing->value);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['deployment_id', 'status']);
            $table->index('check_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_checks');
    }
};
