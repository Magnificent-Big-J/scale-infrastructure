<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profitability_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->decimal('revenue', 14, 2)->default(0);
            $table->decimal('hosting_cost', 12, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('monitoring_cost', 12, 2)->default(0);
            $table->decimal('other_cost', 12, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->decimal('margin', 6, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profitability_records');
    }
};
