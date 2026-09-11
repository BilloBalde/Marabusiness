<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Comble le rate_to_usd manquant sur les commandes historiques.
 *
 * Order::syncPaymentTotals() convertit désormais chaque paiement confirmé vers
 * la devise de la commande via son rate_to_usd (voir la migration du modèle
 * Order dans le même lot). Sur ce dépôt, 141 commandes sur 187 — pas
 * seulement d'anciennes, certaines datées de quelques mois — portent un
 * rate_to_usd NULL : la conversion n'a alors rien vers quoi convertir, et
 * Money::convert() laisse le montant passer tel quel, ce qui peut fausser le
 * solde d'une commande dont un paiement a été enregistré dans une devise
 * différente.
 *
 * Le code de création de commande actuel (CheckoutPage, CheckoutController,
 * OrderNegotiation) pose déjà correctement ce champ — vérifié sur les
 * dernières commandes du dépôt. Ce trou est donc une dette de données
 * antérieure à ce que ce champ soit toujours renseigné, pas un bug qui se
 * reproduit aujourd'hui.
 *
 * Le taux repris est celui de la devise ACTUELLE du vendeur, faute de mieux :
 * la commande ne portait jamais son propre taux, donc aucune valeur figée à
 * l'époque n'existe à retrouver. C'est une approximation, pas une
 * reconstruction — à ce titre, seules les commandes réellement muettes sur
 * ce point sont touchées, jamais une valeur déjà présente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rates = DB::table('vendors')
            ->join('currencies', 'currencies.id', '=', 'vendors.currency_id')
            ->pluck('currencies.rate_to_usd', 'vendors.id');

        DB::table('orders')
            ->whereNull('rate_to_usd')
            ->whereNotNull('vendor_id')
            ->orderBy('id')
            ->select('id', 'vendor_id')
            ->chunkById(200, function ($orders) use ($rates) {
                foreach ($orders as $order) {
                    $rate = $rates->get($order->vendor_id);

                    if ($rate === null || $rate <= 0) {
                        continue;
                    }

                    DB::table('orders')->where('id', $order->id)->update(['rate_to_usd' => $rate]);
                }
            });
    }

    public function down(): void
    {
        // Une approximation reconstruite n'a rien d'original à restaurer en
        // arrière : revenir en arrière remettrait NULL, ce qui recrée
        // exactement le trou que cette migration comble.
    }
};
