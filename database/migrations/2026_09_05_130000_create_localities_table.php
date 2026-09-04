<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared reference list of delivery localities, replacing the postal code as the
     * unit a buyer picks and a vendor prices. Kept marketplace-wide rather than
     * per-vendor so the buyer always sees one consistent list whatever the shop, and
     * so two vendors cannot spell the same place differently.
     *
     * `parent_id` carries the Guinean hierarchy region > prefecture > commune. It is
     * only used for grouping in the pickers: pricing is always attached to the leaf a
     * buyer actually selects.
     */
    public function up(): void
    {
        Schema::create('localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('localities')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('commune'); // region, prefecture, commune, ville, quartier
            $table->string('country_code', 2)->default('GN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
            $table->index(['parent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localities');
    }
};
