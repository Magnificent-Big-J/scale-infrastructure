<?php

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRisk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deployment_id')->nullable()->constrained('deployments')->nullOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('risk')->default(ChangeRisk::Low->value);
            $table->string('status')->default(ChangeRequestStatus::Draft->value);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('scheduled_for')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
