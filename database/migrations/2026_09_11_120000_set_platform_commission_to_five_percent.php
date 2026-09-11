<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La commission de la plateforme : 5 % sur toutes les boutiques.
 *
 * La table ne portait qu'une seule ligne, pour BilloStore, et neuf boutiques
 * sur dix n'avaient donc aucune commission : sur presque chaque vente, la
 * plateforme prélevait zéro. FinanceCalculator::calculateCommission() retombe
 * sur la ligne dont vendor_id est null lorsqu'une boutique n'a pas la sienne,
 * et c'est cette règle globale que cette migration pose.
 *
 * La ligne de BilloStore est corrigée plutôt que supprimée. Elle annonçait déjà
 * 5 %, mais bornée entre 100 et 200 — et ces bornes s'appliquent à la
 * COMMISSION, pas au montant de la commande. Une vente de 500 y donnait donc
 * 100 de commission au lieu de 25, et une vente de 10 000 en donnait 200 au
 * lieu de 500. Ce n'était 5 % que dans une étroite fenêtre. Les bornes sont
 * retirées, l'ancien réglage est conservé dans `notes`, et la ligne reste
 * visible dans l'admin pour être supprimée d'un clic si elle n'a plus lieu
 * d'être.
 *
 * Le taux se règle ensuite depuis Filament (CommissionSettingResource) : cette
 * migration pose un point de départ, elle ne le fige pas.
 */
return new class extends Migration
{
    private const RATE = 5;

    public function up(): void
    {
        $now = now();

        // Idempotente : rejouée, elle met à jour la règle existante plutôt que
        // d'en empiler une seconde, ce qui rendrait le taux appliqué imprévisible.
        $global = DB::table('commission_settings')->whereNull('vendor_id')->first();

        $values = [
            'commission_type' => 'percentage',
            'commission_rate' => self::RATE,
            // Aucune borne : une commission plancher transforme une petite vente
            // en perte pour la boutique, et un plafond fait chuter le taux réel
            // dès que la commande grossit.
            'minimum_amount' => null,
            'maximum_amount' => null,
            // null = tous les moyens de paiement. Voir la remarque dans
            // calculateCommission() : cette colonne n'est pas encore lue.
            'payment_method' => null,
            'is_active' => true,
            'notes' => 'Taux par defaut de la plateforme.',
            'updated_at' => $now,
        ];

        if ($global) {
            DB::table('commission_settings')->where('id', $global->id)->update($values);
        } else {
            DB::table('commission_settings')->insert($values + ['vendor_id' => null, 'created_at' => $now]);
        }

        // Les exceptions par boutique qui promettaient 5 % sans le tenir, a
        // cause de leurs bornes.
        $clamped = DB::table('commission_settings')
            ->whereNotNull('vendor_id')
            ->where(function ($query) {
                $query->whereNotNull('minimum_amount')->orWhereNotNull('maximum_amount');
            })
            ->get();

        foreach ($clamped as $row) {
            $was = sprintf(
                'Avant le passage a %s%% uniforme : taux %s, minimum %s, maximum %s, moyen %s.',
                self::RATE,
                $row->commission_rate,
                $row->minimum_amount ?? 'aucun',
                $row->maximum_amount ?? 'aucun',
                $row->payment_method ?? 'tous'
            );

            DB::table('commission_settings')->where('id', $row->id)->update([
                'commission_rate' => self::RATE,
                'minimum_amount' => null,
                'maximum_amount' => null,
                'payment_method' => null,
                'is_active' => true,
                'notes' => trim(($row->notes ? $row->notes . ' ' : '') . $was),
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Le taux d'avant n'est pas restaurable : il n'existait pas. Seule la
        // regle globale posee ici est retiree ; les lignes par boutique restent,
        // avec dans `notes` le reglage qu'elles avaient.
        DB::table('commission_settings')
            ->whereNull('vendor_id')
            ->where('notes', 'Taux par defaut de la plateforme.')
            ->delete();
    }
};
