<?php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource\Pages\ViewOrder as BaseViewOrder;
use App\Filament\Vendor\Resources\OrderResource;
use App\Models\BulkRfqMessage;
use App\Models\Order;
use App\Models\Vendor;
use App\Services\OrderNegotiation;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use RuntimeException;

/**
 * The vendor's side of a price negotiation.
 *
 * Deliberately here rather than on a separate "Negotiations" page: the vendor is
 * being asked about a basket they can already see, with its lines, its shipping
 * and its buyer. The RFQ feature failed partly because its requests lived on a
 * screen hidden from navigation that nobody ever opened.
 */
class ViewOrder extends BaseViewOrder
{
    protected static string $resource = OrderResource::class;

    /**
     * Sur une commande en négociation, les deux actions passent devant.
     *
     * Elles étaient ajoutées à la suite de « Preview Invoice » et « Download
     * PDF » : sur une commande dont le prix n'existe pas encore, la facture est
     * la dernière chose utile, et c'est pourtant elle qui occupait la première
     * place. Sur une commande ordinaire, l'ordre habituel est conservé.
     */
    protected function getHeaderActions(): array
    {
        // Les deux actions sont toujours déclarées — ce sont leurs visible() qui
        // décident de les montrer. Les retirer du tableau ferait disparaître
        // l'action au lieu de la masquer, ce qui n'est pas la même chose : une
        // action absente ne peut être ni testée, ni appelée par son nom.
        $negotiation = [
            $this->setPriceAction(),
            $this->replyAction(),
        ];

        $inherited = parent::getHeaderActions();

        return $this->record->isNegotiating()
            ? array_merge($negotiation, $inherited)
            : array_merge($inherited, $negotiation);
    }

    private function vendor(): ?Vendor
    {
        return auth()->user()?->vendor;
    }

    /**
     * Ce qui est en jeu, visible sans ouvrir de fenêtre.
     *
     * La ressource ne définit pas d'infolist : la page « Voir » affiche le
     * formulaire en lecture seule, et aucun de ses champs ne parle du prix
     * souhaité ni du prix proposé. Sans cette ligne, un vendeur ouvrant une
     * commande en négociation ne voit rien de la discussion tant qu'il n'a pas
     * cliqué sur une action — donc ne sait pas laquelle de ses commandes attend
     * quelque chose de lui.
     */
    public function getSubheading(): string | Htmlable | null
    {
        $order = $this->record;

        if (! $order->isNegotiating() && $order->negotiation_status === null) {
            return parent::getSubheading();
        }

        $currency = $order->vendor?->currency?->code ?? '';
        $rfq = $order->negotiation;
        $parts = [];

        if ($order->pre_negotiation_total !== null) {
            $parts[] = 'Prix de départ : ' . number_format((float) $order->pre_negotiation_total, 2) . " {$currency}";
        }

        if ($rfq?->target_price) {
            $parts[] = 'Souhait du client : ' . number_format((float) $rfq->target_price, 2) . " {$currency}";
        }

        if ($order->hasLiveOffer()) {
            $parts[] = 'Votre prix : ' . number_format((float) $order->negotiated_total, 2) . " {$currency}"
                . ' (valable jusqu\'au ' . $order->negotiated_expires_at->format('d/m/Y') . ')';
        } elseif ($order->offerHasExpired()) {
            $parts[] = 'Votre prix a expiré — vous pouvez en proposer un autre';
        } elseif ($order->isNegotiating()) {
            $parts[] = 'En attente de votre prix';
        }

        if ($order->negotiation_status === Order::NEGOTIATION_AGREED) {
            $parts[] = 'Prix accepté par le client : ' . number_format((float) $order->grand_total, 2) . " {$currency}";
        }

        return $parts === [] ? parent::getSubheading() : implode(' · ', $parts);
    }

    /**
     * Answering the buyer without leaving the order.
     *
     * The thread is shown inside the same modal the vendor types into — reading
     * the question and answering it are one action, not two screens.
     */
    private function replyAction(): Action
    {
        return Action::make('repondreNegociation')
            ->label('Répondre au client')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('gray')
            ->visible(fn () => $this->record->isNegotiating())
            ->modalHeading('Discussion sur le prix')
            ->modalDescription(fn () => $this->threadSummary())
            ->form([
                Forms\Components\Textarea::make('message')
                    ->label('Votre message')
                    ->required()
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (array $data): void {
                $rfq = $this->record->negotiation;

                if (! $rfq) {
                    return;
                }

                $vendor = $this->vendor();

                if (! $vendor) {
                    return;
                }

                BulkRfqMessage::create([
                    'bulk_rfq_id' => $rfq->id,
                    'sender_type' => Vendor::class,
                    // L'id de la boutique, pas celui de son utilisateur : c'est
                    // ce que sender_type désigne, et ce que le morphTo de
                    // BulkRfqMessage ira chercher. Les trois écritures du dépôt
                    // posaient l'id utilisateur, d'où le mot « Vendor » à la
                    // place du nom du magasin côté acheteur.
                    'sender_id' => $vendor->id,
                    'message' => $data['message'],
                ]);

                Notification::make()->title('Message envoyé')->success()->send();
            });
    }

    /**
     * Naming the agreed price.
     *
     * Nothing is applied to the order here — the buyer still has to take it. That
     * is what lets an expired or refused price simply fall away, leaving the
     * order at the price it already had.
     */
    private function setPriceAction(): Action
    {
        return Action::make('fixerPrixConvenu')
            ->label('Fixer le prix convenu')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn () => $this->record->isNegotiating())
            ->modalHeading('Fixer le prix convenu')
            ->modalDescription(fn () => $this->priceContext())
            ->form([
                Forms\Components\TextInput::make('total')
                    ->label('Total convenu, livraison comprise')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->default(fn () => (float) $this->record->grand_total)
                    ->suffix(fn () => $this->record->vendor?->currency?->code)
                    ->helperText('Ce montant remplacera le total de la commande si le client l\'accepte.'),

                Forms\Components\TextInput::make('valid_days')
                    ->label('Valable pendant (jours)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(30)
                    ->default(3)
                    ->helperText('Passé ce délai le prix retombe et la discussion reprend.'),

                Forms\Components\Textarea::make('note')
                    ->label('Message accompagnant le prix')
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                $vendor = $this->vendor();

                if (! $vendor) {
                    return;
                }

                try {
                    (new OrderNegotiation())->price(
                        $this->record,
                        $vendor,
                        (float) $data['total'],
                        (int) $data['valid_days'],
                        $data['note'] ?? null,
                    );
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                $this->record->refresh();

                Notification::make()
                    ->title('Prix envoyé au client')
                    ->body('La commande deviendra réglable dès qu\'il l\'aura accepté.')
                    ->success()
                    ->send();
            });
    }

    /** What the buyer asked for, so the vendor answers a question and not a blank. */
    private function priceContext(): string
    {
        $rfq = $this->record->negotiation;
        $currency = $this->record->vendor?->currency?->code ?? '';

        $lines = ['Total actuel : ' . number_format((float) $this->record->grand_total, 2) . " {$currency}"];

        if ($rfq?->target_price) {
            $lines[] = 'Prix souhaité par le client : ' . number_format((float) $rfq->target_price, 2) . " {$currency}";
        }

        return implode(' — ', $lines);
    }

    private function threadSummary(): string
    {
        $rfq = $this->record->negotiation;

        if (! $rfq) {
            return '';
        }

        return $rfq->messages()
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(fn ($m) => $m->message)
            ->implode("\n\n");
    }
}
