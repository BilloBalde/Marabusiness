<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // For video
            $table->string('video')->nullable()->after('description');
            $table->string('video_url')->nullable()->after('video');
            $table->string('video_thumbnail')->nullable()->after('video_url');
            
            // For description images (if separate from main gallery)
            $table->json('description_images')->nullable()->after('video_thumbnail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['video', 'video_url', 'video_thumbnail', 'description_images']);
        });
    }
};
