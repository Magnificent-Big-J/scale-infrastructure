<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_demo_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('page_key');
            $table->string('permission');
            $table->string('label');
            $table->string('headline');
            $table->text('summary')->nullable();
            $table->string('status')->default('active');
            $table->json('metrics')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['page_key', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_demo_records');
    }
};
