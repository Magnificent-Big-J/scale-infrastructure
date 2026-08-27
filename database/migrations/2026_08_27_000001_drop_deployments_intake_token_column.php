<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by intake_credentials: a plaintext shared secret stored
        // directly on the deployment row and returned to admins on every
        // fetch. Selector/verifier credentials with hashed storage replace it.
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropUnique(['intake_token']);
            $table->dropColumn('intake_token');
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->string('intake_token')->nullable()->unique()->after('status');
        });
    }
};
