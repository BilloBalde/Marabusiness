<?php

namespace App\Services;

use App\Models\Address;
use App\Models\BulkRfq;
use App\Models\BulkRfqMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Price negotiation between a buyer and one vendor, on a whole basket.
 *
 * Written as one service because the web checkout and the mobile API both need
 * every step of it. Two copies of `convertUsdToVendorCurrency` and five copies of
 * an image-URL helper already taught this codebase what happens when the same
 * rule is written twice: they drift, and only one of them gets fixed.
 *
 * Shape of the flow:
 *
 *   open()   buyer asks       → order created, status 'negotiating', unpayable
 *   price()  vendor answers   → negotiated_total set, with an expiry
 *   accept() buyer agrees     → order becomes an ordinary payable order
 *   refuse() buyer declines   → back to discussion, vendor may price again
 *   cancel() buyer gives up   → order cancelled
 *
 * The conversation itself reuses BulkRfq and BulkRfqMessage, and so the existing
 * /rfq/{rfq}/chat screen works for these threads with no changes. BulkRfqOffer is
 * deliberately not used: its unit_price is NOT NULL and pairs with a MOQ, which
 * suits a single-product quote and not a basket.
 */
class OrderNegotiation
{
    public function __construct(private ?FinanceCalculator $finance = null)
    {
        $this->finance = $finance ?? new FinanceCalculator();
    }

    /**
     * Opens a negotiation on one vendor's share of a basket.
     *
     * @param array<int, array<string, mixed>> $items cart rows for this vendor, as
     *        CartManagement returns them
     * @param array<string, mixed> $address the delivery address fields
     */
    public function open(
        User $buyer,
        Vendor $vendor,
        array $items,
        float $shippingLocal,
        float $shippingUsd,
        ?float $targetPrice,
        string $message,
        array $address = [],
        ?string $shippingCarrier = null,
        ?string $zone = null,
    ): Order {
        if ($items === []) {
            throw new RuntimeException("Aucun article de cette boutique dans votre panier.");
        }

        $rate = (float) ($vendor->currency->rate_to_usd ?? 1);
        $currency = $vendor->currency->code ?? 'USD';

        $subtotal = array_sum(array_map(
            fn ($item) => (float) ($item['total_amount'] ?? 0),
            $items
        ));

        $total = $subtotal + $shippingLocal;

        return DB::transaction(function () use (
            $buyer, $vendor, $items, $subtotal, $shippingLocal, $shippingUsd,
            $total, $rate, $currency, $targetPrice, $message, $address, $shippingCarrier, $zone
        ) {
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $buyer->id,
                'vendor_id' => $vendor->id,
                'grand_total' => $total,
                'total_remaining' => $total,
                'total_paid' => 0,
                'grand_total_usd' => Money::toUsd($total, $rate),
                'shipping_amount' => $shippingLocal,
                'shipping_amount_usd' => $shippingUsd,
                'rate_to_usd' => $rate,
                // No payment method yet: the buyer picks one once a price is agreed.
                'payment_method' => 'pending',
                'payment_status' => 'pending',
                'status' => Order::STATUS_NEGOTIATING,
                'negotiation_status' => Order::NEGOTIATION_OPEN,
                // Kept so a refusal can fall back to the ordinary price, and so the
                // invoice can show what was conceded.
                'pre_negotiation_total' => $total,
                'shipping_carrier' => $shippingCarrier,
                'notes' => "Négociation de prix — Boutique : {$vendor->store_name}",
            ]);

            if ($address !== []) {
                Address::create(array_merge($address, [
                    'order_id' => $order->id,
                    'user_id' => $buyer->id,
                ] + ($zone ? ['zone' => $zone] : [])));
            }

            foreach ($items as $item) {
                $this->createOrderItem($order, $item);
            }

            $rfq = BulkRfq::create([
                'user_id' => $buyer->id,
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                // Both nullable, and left null on purpose: a basket spans several
                // products, so naming one of them here would be a lie.
                'product_id' => null,
                'vendor_product_id' => null,
                'status' => 'pending',
                'quantity' => array_sum(array_map(fn ($i) => (int) ($i['quantity'] ?? 0), $items)),
                'target_price' => $targetPrice,
                'currency' => $currency,
                'shipping_city' => $address['city'] ?? null,
                'shipping_country' => $address['country'] ?? null,
                'needs_customization' => false,
            ]);

            $this->say($rfq, $buyer, $message);

            // No financial transactions here. Nothing is agreed, and writing
            // revenue and commission rows against a provisional figure would put
            // a number in the books that nobody has accepted.

            return $order->fresh();
        });
    }

    /**
     * The vendor names a price.
     *
     * @param int $validForDays how long the buyer has to take it
     */
    public function price(Order $order, Vendor $vendor, float $total, int $validForDays, ?string $note = null): Order
    {
        $this->assertVendorOwns($order, $vendor);
        $this->assertNegotiating($order);

        if ($total <= 0) {
            throw new RuntimeException("Le prix convenu doit être supérieur à zéro.");
        }

        return DB::transaction(function () use ($order, $vendor, $total, $validForDays, $note) {
            $order->update([
                'negotiated_total' => $total,
                'negotiated_expires_at' => now()->addDays(max(1, $validForDays)),
                'negotiation_status' => Order::NEGOTIATION_PRICED,
            ]);

            $currency = $order->vendor?->currency?->code ?? '';
            $body = "Prix proposé : " . number_format($total, 2) . " {$currency}."
                . " Valable jusqu'au " . $order->fresh()->negotiated_expires_at->format('d/m/Y') . '.';

            if ($note) {
                $body .= "\n" . $note;
            }

            if ($rfq = $order->negotiation) {
                $this->say($rfq, $vendor, $body);
                $rfq->update(['status' => 'quoted']);
            }

            return $order->fresh();
        });
    }

    /**
     * The buyer takes the price. The order becomes an ordinary payable order.
     */
    public function accept(Order $order, User $buyer): Order
    {
        $this->assertBuyerOwns($order, $buyer);
        $this->assertNegotiating($order);

        if ($order->negotiation_status !== Order::NEGOTIATION_PRICED) {
            throw new RuntimeException("Aucun prix n'a encore été proposé pour cette commande.");
        }

        if ($order->offerHasExpired()) {
            throw new RuntimeException("Ce prix a expiré. Demandez au vendeur d'en proposer un nouveau.");
        }

        $total = (float) $order->negotiated_total;

        return DB::transaction(function () use ($order, $buyer, $total) {
            // Stock was deliberately not held during the discussion, so it may
            // have gone. An ordinary order decrements at placement; acceptance is
            // the equivalent moment for this one.
            $shortfalls = $this->stockShortfalls($order);

            if ($shortfalls !== []) {
                throw new RuntimeException(
                    "Stock insuffisant : " . implode(', ', $shortfalls)
                    . ". Le prix reste valable, mais la commande ne peut plus être servie telle quelle."
                );
            }

            $goods = $total - (float) $order->shipping_amount;
            $this->repriceItems($order, $goods);
            $this->decrementStock($order);

            $order->update([
                'grand_total' => $total,
                'total_remaining' => $total,
                'grand_total_usd' => Money::toUsd($total, (float) $order->rate_to_usd),
                'status' => 'new',
                'negotiation_status' => Order::NEGOTIATION_AGREED,
            ]);

            if ($rfq = $order->negotiation) {
                $this->say($rfq, $buyer, "Prix accepté. La commande peut être réglée.");
                $rfq->update(['status' => 'accepted']);
            }

            // Only now, on a figure both sides agreed to, does anything reach the
            // books.
            $this->finance->createFinancialTransactionsForOrder($order->fresh('vendor'));

            return $order->fresh();
        });
    }

    /**
     * The buyer turns the price down. The discussion reopens rather than ending —
     * the vendor can name another.
     */
    public function refuse(Order $order, User $buyer, ?string $reason = null): Order
    {
        $this->assertBuyerOwns($order, $buyer);
        $this->assertNegotiating($order);

        return DB::transaction(function () use ($order, $buyer, $reason) {
            $order->update([
                'negotiated_total' => null,
                'negotiated_expires_at' => null,
                'negotiation_status' => Order::NEGOTIATION_OPEN,
            ]);

            if ($rfq = $order->negotiation) {
                $this->say($rfq, $buyer, $reason
                    ? "Prix refusé : {$reason}"
                    : "Prix refusé.");
                $rfq->update(['status' => 'pending']);
            }

            return $order->fresh();
        });
    }

    /**
     * The buyer gives up. Nothing was paid and nothing was booked, so the order
     * simply closes.
     */
    public function cancel(Order $order, User $buyer, ?string $reason = null): Order
    {
        $this->assertBuyerOwns($order, $buyer);
        $this->assertNegotiating($order);

        return DB::transaction(function () use ($order, $buyer, $reason) {
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'negotiation_status' => null,
            ]);

            if ($rfq = $order->negotiation) {
                $this->say($rfq, $buyer, "Négociation annulée par le client.");
                $rfq->update(['status' => 'cancelled']);
            }

            return $order->fresh();
        });
    }

    /**
     * Spreads the agreed goods total across the order's lines.
     *
     * Not cosmetic. EditOrder::recalculateOrderTotals() recomputes
     * grand_total as sum(order_items.total_amount) + shipping_amount every time
     * anyone saves the order in Filament. Writing the negotiated figure onto the
     * order while leaving the lines at their catalogue price means the first
     * back-office save silently reverts the discount.
     *
     * Each line is scaled by the same ratio, and the rounding residual lands on
     * the largest line so the sum is exactly the agreed total rather than a few
     * minor units off it.
     */
    private function repriceItems(Order $order, float $goodsTotal): void
    {
        $items = $order->items()->get();
        $oldGoods = (float) $items->sum('total_amount');

        if ($oldGoods <= 0 || $items->isEmpty()) {
            throw new RuntimeException("Cette commande n'a aucun montant à répartir.");
        }

        $currency = $order->vendor?->currency?->code;
        $ratio = $goodsTotal / $oldGoods;
        $running = 0.0;

        foreach ($items as $item) {
            $lineTotal = Money::round((float) $item->total_amount * $ratio, $currency);
            $running += $lineTotal;

            $item->update([
                'total_amount' => $lineTotal,
                'unit_amount' => Money::round($lineTotal / max(1, (int) $item->quantity), $currency),
            ]);
        }

        // The residual, on the biggest line — where it is proportionally least
        // visible.
        $residual = Money::round($goodsTotal - $running, $currency);

        if (abs($residual) > 0) {
            $largest = $items->sortByDesc('total_amount')->first()->fresh();

            $adjusted = (float) $largest->total_amount + $residual;
            $largest->update([
                'total_amount' => $adjusted,
                'unit_amount' => Money::round($adjusted / max(1, (int) $largest->quantity), $currency),
            ]);
        }
    }

    /**
     * Lines whose quantity exceeds what is on the shelf right now.
     *
     * Same resolution as CheckoutController::findStockShortfalls(): the variation
     * row when the line names one, the vendor_product row otherwise. order_items
     * carries no vendor_product_id, so the listing is resolved from the order's
     * vendor plus the line's product — the same lookup every other stock path in
     * this codebase uses.
     *
     * @return list<string>
     */
    private function stockShortfalls(Order $order): array
    {
        $shortfalls = [];

        foreach ($order->items()->with('product')->get() as $item) {
            $listing = VendorProduct::where('vendor_id', $order->vendor_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (! $listing) {
                continue;
            }

            if ((int) $item->quantity > (int) $listing->stock) {
                $name = $item->product?->name ?? 'Article';
                $shortfalls[] = "{$name} (demandé {$item->quantity}, disponible {$listing->stock})";
            }
        }

        return $shortfalls;
    }

    /**
     * Takes the goods off the shelf, now that the order is real.
     *
     * Deliberately not done when the negotiation opens: the owner chose not to
     * hold stock during a discussion that may never conclude.
     */
    private function decrementStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $listing = VendorProduct::where('vendor_id', $order->vendor_id)
                ->where('product_id', $item->product_id)
                ->first();

            if ($listing) {
                $listing->update(['stock' => max(0, (int) $listing->stock - (int) $item->quantity)]);
            }
        }
    }

    /**
     * Mirrors the order-item block in CheckoutPage::placeOrder and
     * CheckoutController::placeOrder — same fields, same variation handling.
     */
    private function createOrderItem(Order $order, array $item): void
    {
        $data = [
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_amount' => $item['unit_amount'],
            'total_amount' => $item['total_amount'],
        ];

        if (!empty($item['selected_variations']) || !empty($item['variation_note'])) {
            $variations = $item['selected_variations'] ?? [];

            if (!empty($item['variation_note'])) {
                $variations['note'] = $item['variation_note'];
            }

            $data['variation_json'] = $variations;
        }

        OrderItem::create($data);
    }

    /**
     * Posts into the thread.
     *
     * sender_id is the *user* id for both sides. QuoteBulkRfq writes a vendor id
     * for the same sender_type, which is a real inconsistency in the existing RFQ
     * code — RfqChat::getSenderName() resolves the morph and would read the wrong
     * row. This service stays on the RfqChat convention so both sides of a thread
     * agree with each other.
     */
    /**
     * Records who said what.
     *
     * sender_id holds the id of whatever sender_type names — a vendor id for a
     * Vendor, a user id for a User. That sounds obvious, and all three writers in
     * this codebase got it wrong the same way: they stored the vendor's *user*
     * id under sender_type Vendor. BulkRfqMessage::sender is a morphTo, so
     * RfqChat then resolved Vendor::find($userId) and showed the buyer either a
     * different shop's name or the literal word "Vendor".
     *
     * Fixable without a data migration only because no Vendor-typed row had ever
     * been written: every message in the database came from a buyer.
     */
    private function say(BulkRfq $rfq, User|Vendor $sender, string $message): void
    {
        BulkRfqMessage::create([
            'bulk_rfq_id' => $rfq->id,
            'sender_type' => $sender instanceof Vendor ? Vendor::class : User::class,
            'sender_id' => $sender->id,
            'message' => $message,
        ]);
    }

    private function assertNegotiating(Order $order): void
    {
        if (! $order->isNegotiating()) {
            throw new RuntimeException("Cette commande n'est pas en cours de négociation.");
        }

        if ($order->payment_status === 'paid') {
            throw new RuntimeException("Cette commande est déjà réglée.");
        }
    }

    private function assertBuyerOwns(Order $order, User $buyer): void
    {
        if ($order->user_id !== $buyer->id) {
            throw new RuntimeException("Cette commande ne vous appartient pas.");
        }
    }

    private function assertVendorOwns(Order $order, Vendor $vendor): void
    {
        if ($order->vendor_id !== $vendor->id) {
            throw new RuntimeException("Cette commande n'appartient pas à votre boutique.");
        }
    }
}
