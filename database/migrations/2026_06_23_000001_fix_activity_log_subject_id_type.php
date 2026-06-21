<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity subjects across this app use UUID primary keys, but the default
 * spatie `nullableMorphs('subject')` created `subject_id` as a bigint. On
 * PostgreSQL that rejects UUID subjects, breaking activity logging on every
 * create/update. Widen the column to a string so UUID subjects persist.
 * (causer_id stays bigint — causers are users.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->change();
        });
    }
};
