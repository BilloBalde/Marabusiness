<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CartManagement;
use App\Http\Controllers\Api\Concerns\CalculatesCheckoutShipping;
use App\Http\Controllers\Controller;
use App\Models\BulkRfqMessage;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrderNegotiation;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * La négociation de prix, côté application mobile.
 *
 * Toute la logique vit dans App\Services\OrderNegotiation, que le checkout web
 * appelle aussi : ce contrôleur ne fait que traduire une requête HTTP en appel de
 * service, et une exception en code de statut. Écrire ici une seconde version des
 * règles — qui a le droit de fixer un prix, ce qu'un prix expiré autorise — c'est
 * exactement ce qui a fait diverger les deux `convertUsdToVendorCurrency` et les
 * cinq assistants d'URL d'images de ce dépôt.
 */
class NegotiationController extends Controller
{
    use CalculatesCheckoutShipping;

    public function __construct(private OrderNegotiation $negotiation)
    {
    }

    /**
     * Ouvre une négociation sur la part d'un vendeur dans le panier.
     *
     * Mêmes entrées que /checkout/place-order, moins le moyen de paiement : il n'y
     * a rien à régler tant qu'aucun prix n'est convenu.
     */
    public function open(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'integer',
            'address' => 'required|array',
            'address.first_name' => 'required|string',
            'address.last_name' => 'required|string',
            'address.city' => 'required|string',
            'address.phone' => 'required|string',
            'address.street_address' => 'required|string',
            'address.state' => 'required|string',
            'address.zip_code' => 'nullable|string',
            'address.locality_id' => 'nullable|integer|exists:localities,id',
            'address.country' => 'required|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'shipping_carrier' => 'required|string',
            'target_price' => 'nullable|numeric|min:0',
            'message' => 'required|string|max:2000',
        ]);

        $user = Auth::user();
        $cart = CartManagement::getCartItemsFromCookie();

        // Les articles de CE vendeur seulement : une négociation porte sur une
        // boutique, parce que c'est la seule personne qui peut concéder quoi que ce
        // soit sur ces lignes.
        $items = array_values(array_filter(
            $cart,
            fn ($item) => in_array($item['vendor_product_id'], $request->selected_ids, true)
                && (int) $item['vendor_id'] === (int) $request->vendor_id
        ));

        if ($items === []) {
            return response()->json([
                'success' => false,
                'message' => "Aucun article de cette boutique dans votre sélection.",
            ], 422);
        }

        $vendor = Vendor::with('currency')->find($request->vendor_id);

        // Les frais de port sont calculés ici, avec le même code que le checkout.
        // Les accepter depuis le téléphone reviendrait à laisser le client fixer un
        // montant qui finira sur sa facture.
        $shipping = $this->calculateFullShipping($request, $items);
        $shippingUsd = (float) ($shipping['vendors'][$vendor->id]['cost'] ?? 0);
        $zone = $shipping['vendors'][$vendor->id]['zone'] ?? null;
        $rate = $vendor->currency->rate_to_usd ?? null;
        $shippingLocal = Money::fromUsd($shippingUsd, $rate === null ? null : (float) $rate);

        try {
            $order = $this->negotiation->open(
                buyer: $user,
                vendor: $vendor,
                items: $items,
                shippingLocal: $shippingLocal,
                shippingUsd: $shippingUsd,
                targetPrice: $request->filled('target_price') ? (float) $request->target_price : null,
                message: $request->message,
                address: [
                    'first_name' => $request->address['first_name'],
                    'last_name' => $request->address['last_name'],
                    'city' => $request->address['city'],
                    'phone' => $request->address['phone'],
                    'street_address' => $request->address['street_address'],
                    'state' => $request->address['state'],
                    'zip_code' => $request->address['zip_code'] ?? null,
                    'locality_id' => $request->address['locality_id'] ?? null,
                    'country' => $request->address['country'],
                    'latitude' => $request->address['latitude'] ?? null,
                    'longitude' => $request->address['longitude'] ?? null,
                ],
                shippingCarrier: $request->shipping_carrier,
                zone: $zone,
            );
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Ces articles sont passés du panier à la commande.
        $this->removeFromCart($user, $items);

        return response()->json([
            'success' => true,
            'message' => "Votre demande a été envoyée à la boutique.",
            'negotiation' => $this->present($order->fresh(['negotiation', 'items.product', 'vendor.currency'])),
        ], 201);
    }

    /** Les négociations du client, la plus récente d'abord. */
    public function index(Request $request)
    {
        $orders = Order::with(['negotiation', 'items.product', 'vendor.currency'])
            ->where('user_id', Auth::id())
            ->where(function ($query) {
                // Une négociation conclue reste consultable : le client doit pouvoir
                // relire ce sur quoi il s'est engagé après avoir accepté.
                $query->where('status', Order::STATUS_NEGOTIATING)
                    ->orWhereNotNull('pre_negotiation_total');
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Order $order) => $this->present($order));

        return response()->json(['success' => true, 'negotiations' => $orders]);
    }

    /** Le détail d'une négociation, fil de discussion compris. */
    public function show(Request $request, $orderId)
    {
        $order = $this->buyersOrder($orderId);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Négociation introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'negotiation' => $this->present($order),
            'messages' => $this->thread($order),
        ]);
    }

    /** Un message du client dans le fil. */
    public function sendMessage(Request $request, $orderId)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $order = $this->buyersOrder($orderId);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Négociation introuvable.'], 404);
        }

        if (! $order->negotiation) {
            return response()->json(['success' => false, 'message' => "Cette commande n'a pas de fil de discussion."], 422);
        }

        if (! $order->isNegotiating()) {
            return response()->json([
                'success' => false,
                'message' => "Cette négociation est terminée.",
            ], 409);
        }

        BulkRfqMessage::create([
            'bulk_rfq_id' => $order->negotiation->id,
            'sender_type' => User::class,
            'sender_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'messages' => $this->thread($order->fresh('negotiation')),
        ], 201);
    }

    /** Le client accepte le prix proposé : la commande devient payable. */
    public function accept(Request $request, $orderId)
    {
        return $this->settle($orderId, fn (Order $order, User $buyer) => $this->negotiation->accept($order, $buyer));
    }

    /** Le client refuse : la discussion repart, la boutique peut proposer autre chose. */
    public function refuse(Request $request, $orderId)
    {
        $request->validate(['reason' => 'nullable|string|max:2000']);

        return $this->settle(
            $orderId,
            fn (Order $order, User $buyer) => $this->negotiation->refuse($order, $buyer, $request->reason)
        );
    }

    /** Le client abandonne : la commande est annulée. */
    public function cancel(Request $request, $orderId)
    {
        $request->validate(['reason' => 'nullable|string|max:2000']);

        return $this->settle(
            $orderId,
            fn (Order $order, User $buyer) => $this->negotiation->cancel($order, $buyer, $request->reason)
        );
    }

    /**
     * Les trois sorties partagent la même forme : retrouver la commande, appeler le
     * service, et traduire son refus en 409 plutôt qu'en 500. Le service porte les
     * règles (prix expiré, stock disparu, propriétaire) ; les répéter ici, c'est
     * s'exposer à ce que les deux versions se contredisent.
     */
    private function settle($orderId, callable $action)
    {
        $order = $this->buyersOrder($orderId);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Négociation introuvable.'], 404);
        }

        try {
            $order = $action($order, Auth::user());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'success' => true,
            'negotiation' => $this->present($order->fresh(['negotiation', 'items.product', 'vendor.currency'])),
        ]);
    }

    private function buyersOrder($orderId): ?Order
    {
        return Order::with(['negotiation.messages', 'items.product', 'vendor.currency'])
            ->where('user_id', Auth::id())
            ->find($orderId);
    }

    private function removeFromCart(User $user, array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['cart_key'])) {
                CartManagement::removeCartItem($item['cart_key']);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Order $order): array
    {
        $currency = $order->vendor?->currency;

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'rfq_id' => $order->negotiation?->id,
            'status' => $order->status,
            'negotiation_status' => $order->negotiation_status,
            'vendor' => [
                'id' => $order->vendor?->id,
                'store_name' => $order->vendor?->store_name,
            ],
            'currency' => $currency?->code,
            'currency_symbol' => $currency?->symbol,
            'target_price' => $order->negotiation?->target_price !== null
                ? (float) $order->negotiation->target_price
                : null,
            'original_total' => $order->pre_negotiation_total !== null
                ? (float) $order->pre_negotiation_total
                : null,
            'proposed_total' => $order->negotiated_total !== null ? (float) $order->negotiated_total : null,
            'grand_total' => (float) $order->grand_total,
            'shipping_amount' => (float) $order->shipping_amount,
            'expires_at' => $order->negotiated_expires_at?->toIso8601String(),
            // Les deux drapeaux dont l'écran a besoin, calculés là où la règle vit,
            // pour que le téléphone n'ait pas à comparer des dates lui-même.
            'has_live_offer' => $order->hasLiveOffer(),
            'offer_expired' => $order->offerHasExpired(),
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'quantity' => (int) $item->quantity,
                'unit_amount' => (float) $item->unit_amount,
                'total_amount' => (float) $item->total_amount,
            ])->values(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function thread(Order $order): array
    {
        if (! $order->negotiation) {
            return [];
        }

        return $order->negotiation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (BulkRfqMessage $message) => [
                'id' => $message->id,
                // sender_type porte la classe : c'est ce qui distingue la boutique du
                // client, l'id seul ne suffit pas (ce sont deux tables différentes).
                'from_buyer' => $message->sender_type === User::class,
                'message' => $message->message,
                'sent_at' => $message->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
