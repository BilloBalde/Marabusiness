<?php

namespace App\Livewire;

use App\Models\BulkRfq;
use App\Models\BulkRfqMessage;
use App\Models\BulkRfqOffer;
use App\Models\Vendor;
use App\Services\OrderNegotiation;
use App\Services\RfqOfferConverter;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('RFQ Chat - MARA BUSINESS')]
class RfqChat extends Component
{
    use WithPagination;
    
    public $rfqId;
    public BulkRfq $rfq;
    public $messages = [];
    
    #[Validate('required|string|max:2000')]
    public $newMessage = '';

    public ?int $rejectingOfferId = null;
    public string $rejectionReason = '';
    
    public function mount($rfq = null)
    {
        // Handle both route model binding and direct ID
        if ($rfq instanceof BulkRfq) {
            $this->rfq = $rfq;
        } else {
            $this->rfqId = $rfq ?? request()->route('rfq');
            $this->rfq = BulkRfq::with(['user', 'vendor', 'product'])->findOrFail($this->rfqId);
        }
        
        // Check authorization
        $this->checkAuthorization();
        
        $this->loadMessages();
    }
    
    protected function checkAuthorization()
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403, 'You must be logged in.');
        }
        
        $isBuyer = $this->rfq->user_id === $user->id;
        $isVendor = $user->vendor && $this->rfq->vendor_id === $user->vendor->id;
        
        if (!$isBuyer && !$isVendor) {
            abort(403, 'You are not authorized to view this chat.');
        }
    }
    
    public function loadMessages()
    {
        $this->messages = $this->rfq->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'created_at' => $message->created_at->format('h:i A'),
                    'date' => $message->created_at->format('M d, Y'),
                    'is_from_user' => $this->isFromUser($message),
                    'is_from_vendor' => $this->isFromVendor($message),
                    'sender_name' => $this->getSenderName($message),
                ];
            })
            ->toArray();
    }
    
    protected function isFromUser($message)
    {
        // Use full namespace
        return $message->sender_type === \App\Models\User::class;
    }
    
    protected function isFromVendor($message)
    {
        // Use full namespace
        return $message->sender_type === \App\Models\Vendor::class;
    }
    
    protected function getSenderName($message)
    {
        if ($this->isFromUser($message)) {
            return $message->sender->name ?? 'Client';
        }

        // Repli sur la boutique de la demande elle-même : une ligne écrite avant
        // la correction de sender_id pointe sur un id d'utilisateur et ne résout
        // donc rien. Le fil n'a qu'une boutique, autant la nommer.
        return $message->sender->store_name
            ?? $this->rfq->vendor?->store_name
            ?? 'Boutique';
    }
    
    public function sendMessage()
    {
        $this->validate();
        
        if (empty(trim($this->newMessage))) {
            return;
        }
        
        $user = auth()->user();

        // sender_id doit porter l'id de ce que sender_type désigne : un id de
        // boutique pour Vendor, un id d'utilisateur pour User. C'était l'id de
        // l'utilisateur dans les deux cas, si bien que getSenderName() résolvait
        // Vendor::find($userId) — une autre boutique, ou rien du tout, auquel cas
        // l'acheteur lisait le mot « Vendor » à la place du nom du magasin.
        $vendor = $user->vendor;
        $senderType = $vendor ? \App\Models\Vendor::class : \App\Models\User::class;

        $this->rfq->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $vendor?->id ?? $user->id,
            'message' => trim($this->newMessage),
        ]);
        
        $this->newMessage = '';
        $this->loadMessages();
        
        // Dispatch event to scroll to bottom
        $this->dispatch('scroll-to-bottom');
    }
    
    public function getOtherParty()
    {
        $user = auth()->user();
        
        if ($user->vendor && $this->rfq->vendor_id === $user->vendor->id) {
            // User is vendor, return buyer
            return [
                'name' => $this->rfq->user->name,
                'type' => 'buyer',
                'email' => $this->rfq->user->email,
            ];
        } else {
            // User is buyer, return vendor
            return [
                'name' => $this->rfq->vendor->store_name,
                'type' => 'vendor',
                'email' => $this->rfq->vendor->user->email ?? '',
            ];
        }
    }
    
    /**
     * Quotes were never shown to the buyer anywhere — only the vendor's chat message
     * mentioned them in passing — so there was nothing to act on. They are listed here,
     * next to the conversation they came from.
     */
    public function getOffersProperty()
    {
        return $this->rfq->offers()->latest()->get();
    }

    public function isBuyer(): bool
    {
        return $this->rfq->user_id === auth()->id();
    }

    /**
     * Opens the small reason box under a quote. Kept as plain state rather than a modal
     * so the buyer still sees the quote they are turning down while they type.
     */
    public function startRejecting(int $offerId): void
    {
        $this->rejectingOfferId = $offerId;
        $this->rejectionReason = '';
    }

    public function cancelRejecting(): void
    {
        $this->rejectingOfferId = null;
        $this->rejectionReason = '';
    }

    /**
     * Turning a quote down. The reason is optional: forcing one would only produce
     * empty-looking justifications, and the vendor still learns the price was refused.
     */
    public function rejectOffer(int $offerId): void
    {
        if (! $this->isBuyer()) {
            abort(403, 'Seul l\'acheteur peut refuser un devis.');
        }

        $offer = BulkRfqOffer::where('bulk_rfq_id', $this->rfq->id)->findOrFail($offerId);

        try {
            app(RfqOfferConverter::class)->reject($offer, auth()->user(), $this->rejectionReason);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->cancelRejecting();
        $this->rfq->refresh();
        $this->loadMessages();

        session()->flash('success', 'Devis refusé. Le vendeur en a été informé et peut vous en proposer un autre.');
    }

    /**
     * Accepting turns the quote into a normal unpaid order, which the buyer then pays
     * through the usual order screen.
     */
    public function acceptOffer(int $offerId): void
    {
        if (! $this->isBuyer()) {
            abort(403, 'Seul l\'acheteur peut accepter un devis.');
        }

        $offer = BulkRfqOffer::where('bulk_rfq_id', $this->rfq->id)->findOrFail($offerId);

        try {
            $order = app(RfqOfferConverter::class)->accept($offer, auth()->user());
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->rfq->refresh();
        $this->loadMessages();

        session()->flash('success', "Devis accepté. Votre commande {$order->order_number} a été créée : réglez-la ci-dessous.");

        // The orders list, not the order detail page: the "Payer" button that opens
        // PaiementModal lives on the list, and the detail page has no payment control
        // at all. The new order shows there because its total_remaining is above zero.
        $this->redirectRoute('my-orders', navigate: false);
    }

    /**
     * The order this thread is about, when it came from checkout.
     *
     * Null for the original single-product request, where no order exists until
     * an offer is accepted.
     */
    public function getNegotiatedOrderProperty()
    {
        return $this->rfq->order_id ? $this->rfq->order : null;
    }

    /** The buyer takes the price the vendor named. */
    public function acceptNegotiatedPrice(): void
    {
        if (! $this->isBuyer()) {
            abort(403, "Seul l'acheteur peut accepter un prix.");
        }

        $order = $this->negotiatedOrder;

        if (! $order) {
            return;
        }

        try {
            $order = app(OrderNegotiation::class)->accept($order, auth()->user());
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->rfq->refresh();
            $this->loadMessages();

            return;
        }

        session()->flash('success', "Prix accepté. Votre commande {$order->order_number} peut être réglée.");

        // The orders list, not the detail page: the "Payer" button that opens
        // PaiementModal lives on the list. Same reasoning as acceptOffer above.
        $this->redirectRoute('my-orders', navigate: false);
    }

    /** The buyer turns the price down; the discussion carries on. */
    public function refuseNegotiatedPrice(): void
    {
        if (! $this->isBuyer()) {
            abort(403, "Seul l'acheteur peut refuser un prix.");
        }

        if (! $order = $this->negotiatedOrder) {
            return;
        }

        try {
            app(OrderNegotiation::class)->refuse($order, auth()->user(), $this->rejectionReason ?: null);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->rejectionReason = '';
        $this->rfq->refresh();
        $this->loadMessages();

        session()->flash('success', "Prix refusé. Vous pouvez poursuivre la discussion.");
    }

    /** The buyer gives up. Nothing was paid and nothing was booked. */
    public function cancelNegotiation(): void
    {
        if (! $this->isBuyer()) {
            abort(403, "Seul l'acheteur peut annuler cette commande.");
        }

        if (! $order = $this->negotiatedOrder) {
            return;
        }

        try {
            app(OrderNegotiation::class)->cancel($order, auth()->user());
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', "Négociation annulée.");
        $this->redirectRoute('my-orders', navigate: false);
    }

    public function render()
    {
        return view('livewire.rfq-chat', [
            'otherParty' => $this->getOtherParty(),
            'user' => auth()->user(),
            'offers' => $this->offers,
            'isBuyer' => $this->isBuyer(),
            'negotiatedOrder' => $this->negotiatedOrder,
        ]);
    }
}