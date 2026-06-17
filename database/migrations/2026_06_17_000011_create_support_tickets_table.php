<?php

use App\Enums\SupportSeverity;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('deployment_id')->nullable()->constrained('deployments')->nullOnDelete();
            $table->foreignUuid('support_agreement_id')->nullable()->constrained('support_agreements')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('subject');
            $table->string('category')->nullable();
            $table->string('severity')->default(SupportSeverity::Low->value);
            $table->string('status')->default(SupportTicketStatus::Open->value);
            $table->decimal('hours_logged', 8, 2)->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
