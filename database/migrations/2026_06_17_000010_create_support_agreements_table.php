<?php

use App\Enums\SupportAgreementStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('support_tier_id')->nullable()->constrained('support_tiers')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('monthly_fee', 12, 2)->nullable();
            $table->unsignedInteger('included_hours')->nullable();
            $table->unsignedInteger('response_sla_hours')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default(SupportAgreementStatus::Draft->value);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_agreements');
    }
};
