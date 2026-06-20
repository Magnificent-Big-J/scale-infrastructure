<?php

use App\Enums\ReleaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->foreignUuid('change_request_id')->nullable()->constrained('change_requests')->nullOnDelete();
            $table->string('version');
            $table->string('status')->default(ReleaseStatus::Draft->value);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('deployed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->text('rollback_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['deployment_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
