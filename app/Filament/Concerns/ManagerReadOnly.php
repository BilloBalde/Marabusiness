<?php

namespace App\Filament\Concerns;

/**
 * Un manager voit cette ressource mais ne peut rien y changer — décidé pour
 * les 8 ressources financières/expédition qu'aucune Policy ne protégeait
 * (CommissionSetting, PaymentGatewayFee, WireTransferFee, FinancialTransaction,
 * VendorPayout, Paiement, ShippingCarrierRate, ShippingZone), après qu'un audit
 * a montré qu'un manager pouvait, par exemple, marquer un versement vendeur
 * "effectué" ou un paiement à la livraison "payé" sans que rien n'ait été
 * réellement encaissé. Seul un admin agit désormais ; le manager garde la
 * visibilité, utile au quotidien.
 *
 * Un trait plutôt que huit copies des mêmes méthodes, et pas de classe Policy :
 * ce projet n'en a aucune ailleurs, et ce serait le seul endroit à introduire
 * un mécanisme différent pour un besoin qu'un trait couvre déjà.
 */
trait ManagerReadOnly
{
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
