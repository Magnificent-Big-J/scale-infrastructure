<?php

use App\Enums\CatalogueStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('monthly_fee', 12, 2)->nullable();
            $table->unsignedInteger('included_hours')->nullable();
            $table->unsignedInteger('response_sla_hours')->nullable();
            $table->string('service_review')->nullable();
            $table->string('best_for')->nullable();
            $table->string('currency', 3)->default(config('catalogue.default_currency'));
            $table->string('status')->default(CatalogueStatus::Active->value);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tiers');
    }
};
