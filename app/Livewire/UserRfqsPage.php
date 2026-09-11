<?php

// app/Livewire/UserRfqsPage.php
namespace App\Livewire;

use App\Models\BulkRfq;
use App\Models\Order;
use App\Services\OrderNegotiation;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('Mes négociations - MARA BUSINESS')]
class UserRfqsPage extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    
    public function render()
    {
        // 'order' eager-loaded alongside the rest: a checkout negotiation has no
        // product of its own — it covers a whole vendor basket — so the order is
        // what the row has to show. Without it every row would lazy-load one.
        $rfqs = BulkRfq::with(['vendor', 'product', 'latestOffer', 'order.items'])
            ->where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                // Searching only the product name found nothing for a basket
                // negotiation, which carries none. The order number and the shop
                // name are what a buyer actually remembers.
                $query->where(function ($q) {
                    $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('vendor', fn ($v) => $v->where('store_name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('livewire.user-rfqs-page', [
            'rfqs' => $rfqs,
            'shopNegotiations' => $this->shopNegotiationCount(),
            'statuses' => [
                'pending' => 'En attente du vendeur',
                'quoted' => 'Prix proposé',
                'accepted' => 'Prix accepté',
                'rejected' => 'Refusée',
                'cancelled' => 'Annulée',
            ]
        ]);
    }

    /**
     * Les négociations qui attendent l'utilisateur en tant que BOUTIQUE.
     *
     * Cette page ne liste que les négociations où l'on est client
     * (user_id = auth()->id()). Un vendeur qui suit le lien « Mes négociations »
     * du menu y arrive et voit une page vide — ce qui est exact et parfaitement
     * trompeur : les demandes adressées à sa boutique existent, mais elles vivent
     * dans son espace vendeur. Le compte sert à le lui dire au lieu de le laisser
     * conclure que la fonctionnalité ne marche pas.
     *
     * Renvoie null quand l'utilisateur n'a pas de boutique : rien à afficher.
     */
    private function shopNegotiationCount(): ?int
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (! $vendorId) {
            return null;
        }

        return Order::where('vendor_id', $vendorId)
            ->where('status', Order::STATUS_NEGOTIATING)
            ->count();
    }
    
    public function cancelRfq($rfqId)
    {
        $rfq = BulkRfq::with('order')->findOrFail($rfqId);

        if ($rfq->user_id !== auth()->id()) {
            session()->flash('error', "Vous n'êtes pas autorisé à faire cela.");

            return;
        }

        // Une négociation ouverte depuis le panier a une commande derrière elle.
        // La passer en 'cancelled' ici seulement laisserait cette commande en
        // statut 'negotiating' pour toujours : ni payable, ni annulée, et sans
        // aucun bouton pour en sortir. OrderNegotiation::cancel() ferme les deux
        // et écrit dans le fil, comme le fait le bouton de la page de discussion.
        if ($rfq->order) {
            try {
                app(OrderNegotiation::class)->cancel($rfq->order, auth()->user());
                session()->flash('success', 'Négociation et commande annulées.');
            } catch (\Throwable $e) {
                report($e);
                session()->flash('error', $e->getMessage());
            }

            return;
        }

        $rfq->update(['status' => 'cancelled']);
        session()->flash('success', 'Demande annulée.');
    }
}
