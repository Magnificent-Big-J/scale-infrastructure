<?php

use App\Enums\OpportunityStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Either an existing client, or a free-text prospect (one is required).
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('prospect_name')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stage')->default(OpportunityStage::Lead->value);
            $table->decimal('value', 14, 2)->default(0);
            $table->unsignedTinyInteger('probability')->nullable();
            $table->string('source')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'expected_close_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
