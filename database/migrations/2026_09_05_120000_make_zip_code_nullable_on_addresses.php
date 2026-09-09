<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * zip_code was created NOT NULL and is required by nine validators, yet no pricing
     * or carrier lookup ever reads it — it is only echoed back into views and mails.
     * Guinean addresses have no postal code, so buyers were blocked by a field that
     * carries no information. Relaxing the validators alone would hit the NOT NULL
     * constraint, hence this change.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('zip_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing rows may hold NULL by then; backfill before restoring the constraint.
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('zip_code')->nullable(false)->default('')->change();
        });
    }
};
