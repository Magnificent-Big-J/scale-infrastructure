<?php

use App\Enums\AutomationRunStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provisioning_template_id')->nullable()->constrained('provisioning_templates')->nullOnDelete();
            $table->foreignUuid('deployment_id')->nullable()->constrained('deployments')->nullOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('change_request_id')->nullable()->constrained('change_requests')->nullOnDelete();
            $table->string('reference');
            $table->string('status')->default(AutomationRunStatus::Queued->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('output_summary')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
    }
};
