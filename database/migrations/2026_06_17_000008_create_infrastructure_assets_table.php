<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastructure_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('app_server');
            $table->string('provider')->nullable();
            $table->string('region')->nullable();
            $table->string('size')->nullable();
            $table->decimal('monthly_cost', 12, 2)->nullable();
            $table->string('currency', 3)->default(config('catalogue.default_currency'));
            $table->string('public_ip')->nullable();
            $table->string('private_ip')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['deployment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_assets');
    }
};
