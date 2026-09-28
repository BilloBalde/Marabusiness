<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * title/short_description/description are JSON ({fr, en, zh}) so the site can
 * grow into the other two languages later without another migration — but
 * only 'fr' is exposed on the admin form for now (see SourcingResource), since
 * the trilingual rollout for this module was deliberately deferred.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sourcings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('short_description');
            $table->json('description')->nullable();
            $table->json('gallery')->nullable();
            $table->json('external_links')->nullable();
            $table->string('catalog_pdf')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sourcings');
    }
};
