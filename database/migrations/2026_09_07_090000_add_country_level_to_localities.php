<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Locality was seeded Guinea-only, with regions as the root level. Shared delivery
     * zone pricing now needs to cover any destination country, so a country level is
     * inserted above region: every existing root (the 7 administrative regions plus the
     * Conakry special zone) becomes a child of a new "Guinea" country node instead of a
     * root itself.
     *
     * Only the countries this storefront's checkout already offers are seeded here
     * (matching CheckoutPage::getCountryCode()) — the schema accepts any country, an
     * admin or vendor adds one the day it is actually needed rather than pre-loading a
     * full ISO-3166 list nobody uses yet.
     */
    public function up(): void
    {
        $countries = [
            'GN' => 'Guinée',
            'SN' => 'Sénégal',
            'CI' => "Côte d'Ivoire",
            'ML' => 'Mali',
            'FR' => 'France',
            'US' => 'États-Unis',
            'CA' => 'Canada',
            'GB' => 'Royaume-Uni',
        ];

        $countryIds = [];

        foreach ($countries as $code => $name) {
            $countryIds[$code] = DB::table('localities')->insertGetId([
                'parent_id' => null,
                'name' => $name,
                'type' => 'country',
                'country_code' => $code,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Every existing root locality was seeded with country_code = 'GN' — re-parent
        // it under the new Guinea node rather than guessing by name.
        DB::table('localities')
            ->whereNull('parent_id')
            ->where('country_code', 'GN')
            ->whereNotIn('id', array_values($countryIds))
            ->update(['parent_id' => $countryIds['GN']]);
    }

    public function down(): void
    {
        $guinea = DB::table('localities')->where('type', 'country')->where('country_code', 'GN')->first();

        if ($guinea) {
            DB::table('localities')->where('parent_id', $guinea->id)->update(['parent_id' => null]);
        }

        DB::table('localities')->where('type', 'country')->delete();
    }
};
