<?php

use App\Enums\DeploymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->string('name');
            $table->string('environment')->default('production');
            $table->string('domain')->nullable();
            $table->string('app_url')->nullable();
            $table->string('current_version')->nullable();
            $table->date('go_live_date')->nullable();
            $table->string('status')->default(DeploymentStatus::Planned->value);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'environment']);
            $table->index(['status', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
