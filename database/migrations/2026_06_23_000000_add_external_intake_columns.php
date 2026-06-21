<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            // Per-deployment shared secret for the public ticket-intake endpoint.
            $table->string('intake_token')->nullable()->unique()->after('status');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('source')->default('internal')->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn('intake_token');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
