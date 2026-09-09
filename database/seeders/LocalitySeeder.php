<?php

namespace Database\Seeders;

use App\Models\Locality;
use Illuminate\Database\Seeder;

class LocalitySeeder extends Seeder
{
    /**
     * The countries this storefront's checkout already offers (matches
     * CheckoutPage::getCountryCode()) — not a full ISO-3166 list. Shared delivery
     * pricing accepts any country an admin or vendor adds through the form; these are
     * only the ones pre-loaded because they are already in real use.
     */
    private const COUNTRIES = [
        'GN' => 'Guinée',
        'SN' => 'Sénégal',
        'CI' => "Côte d'Ivoire",
        'ML' => 'Mali',
        'FR' => 'France',
        'US' => 'États-Unis',
        'CA' => 'Canada',
        'GB' => 'Royaume-Uni',
    ];

    /**
     * Guinean administrative divisions: 7 administrative regions holding 33
     * prefectures, plus the special zone of Conakry split into communes — all nested
     * under the Guinea country node.
     *
     * Outside Conakry the prefecture is the unit people are delivered to, so
     * prefectures are selectable; regions only group the pickers.
     *
     * This is a starting point, not a closed list — the admin can add or deactivate
     * entries. Conakry in particular has been reorganised in recent years; the five
     * historic communes are seeded here and further ones can be added as needed.
     */
    public function run(): void
    {
        $countries = [];

        foreach (self::COUNTRIES as $code => $name) {
            $countries[$code] = $this->upsertCountry($code, $name);
        }

        $guinea = $countries['GN'];

        $regions = [
            'Boké' => [
                'Boffa', 'Boké', 'Fria', 'Gaoual', 'Koundara',
            ],
            'Faranah' => [
                'Dabola', 'Dinguiraye', 'Faranah', 'Kissidougou',
            ],
            'Kankan' => [
                'Kankan', 'Kérouané', 'Kouroussa', 'Mandiana', 'Siguiri',
            ],
            'Kindia' => [
                'Coyah', 'Dubréka', 'Forécariah', 'Kindia', 'Télimélé',
            ],
            'Labé' => [
                'Koubia', 'Labé', 'Lélouma', 'Mali', 'Tougué',
            ],
            'Mamou' => [
                'Dalaba', 'Mamou', 'Pita',
            ],
            'Nzérékoré' => [
                'Beyla', 'Guéckédou', 'Lola', 'Macenta', 'Nzérékoré', 'Yomou',
            ],
        ];

        foreach ($regions as $regionName => $prefectures) {
            $region = $this->upsert($regionName, Locality::TYPE_REGION, $guinea->id, 'GN');

            foreach ($prefectures as $prefecture) {
                $this->upsert($prefecture, Locality::TYPE_PREFECTURE, $region->id, 'GN');
            }
        }

        // Conakry is a special zone: no prefectures, communes sit directly under it.
        $conakry = $this->upsert('Conakry', Locality::TYPE_REGION, $guinea->id, 'GN');

        foreach (['Dixinn', 'Kaloum', 'Matam', 'Matoto', 'Ratoma'] as $commune) {
            $this->upsert($commune, Locality::TYPE_COMMUNE, $conakry->id, 'GN');
        }

        $this->command?->info('Localities seeded: ' . Locality::count() . ' entries.');
    }

    private function upsertCountry(string $code, string $name): Locality
    {
        return Locality::firstOrCreate(
            ['type' => Locality::TYPE_COUNTRY, 'country_code' => $code],
            ['name' => $name, 'parent_id' => null, 'is_active' => true],
        );
    }

    /**
     * Keyed on name + parent so the seeder can be re-run to add newly created
     * localities without duplicating or resetting what an admin has edited.
     */
    private function upsert(string $name, string $type, ?int $parentId, string $countryCode): Locality
    {
        return Locality::firstOrCreate(
            ['name' => $name, 'parent_id' => $parentId],
            ['type' => $type, 'country_code' => $countryCode, 'is_active' => true],
        );
    }
}
