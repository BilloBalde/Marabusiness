<?php

namespace Database\Seeders;

use App\Models\Locality;
use Illuminate\Database\Seeder;

class LocalitySeeder extends Seeder
{
    /**
     * Guinean administrative divisions: 7 administrative regions holding 33
     * prefectures, plus the special zone of Conakry split into communes.
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
            $region = $this->upsert($regionName, Locality::TYPE_REGION, null);

            foreach ($prefectures as $prefecture) {
                $this->upsert($prefecture, Locality::TYPE_PREFECTURE, $region->id);
            }
        }

        // Conakry is a special zone: no prefectures, communes sit directly under it.
        $conakry = $this->upsert('Conakry', Locality::TYPE_REGION, null);

        foreach (['Dixinn', 'Kaloum', 'Matam', 'Matoto', 'Ratoma'] as $commune) {
            $this->upsert($commune, Locality::TYPE_COMMUNE, $conakry->id);
        }

        $this->command?->info('Localities seeded: ' . Locality::count() . ' entries.');
    }

    /**
     * Keyed on name + parent so the seeder can be re-run to add newly created
     * localities without duplicating or resetting what an admin has edited.
     */
    private function upsert(string $name, string $type, ?int $parentId): Locality
    {
        return Locality::firstOrCreate(
            ['name' => $name, 'parent_id' => $parentId],
            ['type' => $type, 'country_code' => 'GN', 'is_active' => true],
        );
    }
}
