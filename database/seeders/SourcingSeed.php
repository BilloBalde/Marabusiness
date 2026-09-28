<?php

namespace Database\Seeders;

use App\Models\SourceProduct;
use App\Models\Sourcing;
use Illuminate\Database\Seeder;

/**
 * The four specialties are the real ones the owner listed for the business —
 * not placeholder content. Only "communication" got concrete product examples
 * in that description, so it's the only one seeded with sample products; the
 * others stay product-less until the owner (or admin) fills them in.
 */
class SourcingSeed extends Seeder
{
    public function run(): void
    {
        $sourcings = [
            [
                'slug' => 'communication',
                'title' => ['fr' => 'Support de communication'],
                'short_description' => ['fr' => 'Casquettes, t-shirts, cups, clés USB personnalisées.'],
                'sort_order' => 1,
                'products' => [
                    ['slug' => 'casquette-brodee', 'name' => ['fr' => 'Casquette brodée']],
                    ['slug' => 't-shirt-personnalise', 'name' => ['fr' => 'T-shirt personnalisé']],
                    ['slug' => 'cle-usb-personnalisee', 'name' => ['fr' => 'Clé USB personnalisée']],
                ],
            ],
            [
                'slug' => 'playgrounds',
                'title' => ['fr' => 'Playgrounds pour enfants'],
                'short_description' => ['fr' => 'Aires de jeux et équipements pour enfants.'],
                'sort_order' => 2,
                'products' => [],
            ],
            [
                'slug' => 'meubles-cuisine',
                'title' => ['fr' => 'Meubles et fournitures de cuisine'],
                'short_description' => ['fr' => 'Meubles et équipements pour la cuisine.'],
                'sort_order' => 3,
                'products' => [],
            ],
            [
                'slug' => 'maison-construction',
                'title' => ['fr' => 'Maison et construction'],
                'short_description' => ['fr' => 'Matériaux et équipements pour la maison et la construction.'],
                'sort_order' => 4,
                'products' => [],
            ],
        ];

        foreach ($sourcings as $data) {
            $products = $data['products'];
            unset($data['products']);

            $sourcing = Sourcing::updateOrCreate(['slug' => $data['slug']], $data);

            foreach ($products as $i => $product) {
                SourceProduct::updateOrCreate(
                    ['slug' => $product['slug']],
                    $product + ['sourcing_id' => $sourcing->id, 'sort_order' => $i + 1],
                );
            }
        }
    }
}
