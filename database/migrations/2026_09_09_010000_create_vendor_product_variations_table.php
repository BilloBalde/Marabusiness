<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vendor_product_variations exists in the live database, holds real rows, and is
 * read by App\Models\VendorProductVariation and eager-loaded by
 * Api\ProductController::show() — but no migration ever created it. It was made
 * by hand, or by a migration that was later deleted.
 *
 * The consequence is not theoretical: on a database built from migrations alone,
 * GET /api/v1/products/{slug}/{id} returns a 500 — "no such table:
 * vendor_product_variations" — so the mobile app's product page is broken on any
 * freshly built environment. That includes a new developer's machine, a CI run,
 * and a Render deploy that has lost its SQLite file (render.yaml declares a
 * persistent disk now, but the risk is what made this worth fixing rather than
 * documenting).
 *
 * The definition below is transcribed from the live table's own schema, so
 * running this on a fresh database reproduces exactly what production has today.
 * Guarded with hasTable() so it is a no-op on the existing database rather than
 * failing on the table it is describing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_product_variations')) {
            return;
        }

        Schema::create('vendor_product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_product_id')
                ->constrained('vendor_product')
                ->cascadeOnDelete();
            $table->text('attributes');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('sku')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->unique(['vendor_product_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_product_variations');
    }
};
