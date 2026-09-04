<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) brand_translations
        Schema::create('brand_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2); // en, fr, zh
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'locale']);
            $table->index(['locale']);
        });

        // 2) category_translations
        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('name');
            $table->timestamps();

            $table->unique(['category_id', 'locale']);
            $table->index(['locale']);
        });

        // 3) product_translations
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->index(['locale']);
        });

        // 4) service_translations
        Schema::create('service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'locale']);
            $table->index(['locale']);
        });

        // 5) vendor_translations
        Schema::create('vendor_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('store_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'locale']);
            $table->index(['locale']);
        });

        // 6) vendor_product_review_translations
        Schema::create('vendor_product_review_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_product_review_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['vendor_product_review_id', 'locale'], 'vprt_unique');
            $table->index(['locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_product_review_translations');
        Schema::dropIfExists('vendor_translations');
        Schema::dropIfExists('service_translations');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('brand_translations');
    }
};
