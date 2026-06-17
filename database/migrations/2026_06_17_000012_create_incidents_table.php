<?php

use App\Enums\IncidentStatus;
use App\Enums\SupportSeverity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('deployment_id')->nullable()->constrained('deployments')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('title');
            $table->string('severity')->default(SupportSeverity::Medium->value);
            $table->string('status')->default(IncidentStatus::Open->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
