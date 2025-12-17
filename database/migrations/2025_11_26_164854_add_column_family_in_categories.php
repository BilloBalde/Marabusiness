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
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('family', [
                'Mode',
                'Beauté',
                'Maison',
                'Cuisine',
                'Électronique',
                'Sport',
                'Accessoires',
                'Décoration',
                'Jouets & Enfants',
                'Auto & Moto',
                'Animaux',
                'Bricolage & Outils',
                'Jardin & Extérieur',
                'Bags & Luggage',
                'Santé & Bien-être',
                'Arts & Loisirs',
                'Informatique',
                'Fêtes & Événements',
                'Bébé & Puériculture',
                'Fournitures de Bureau',
                'Jeux Vidéo',
                'Équipement Industriel',
            ])->default('Maison');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('family');
        });
    }
};
