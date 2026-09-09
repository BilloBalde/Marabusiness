<?php

namespace App\Services;

use App\Mail\RfqOfferAccepted;
use App\Mail\RfqOfferRejected;
use App\Models\Address;
use App\Models\BulkRfq;
use App\Models\BulkRfqOffer;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Turns a vendor's accepted quote into a real order.
 *
 * This was the missing half of the RFQ feature: a buyer could request a quote and a
 * vendor could send one, but nothing in the codebase moved an offer forward — no
 * acceptance, no order, no way for the negotiated price to ever be paid.
 *
 * The order it produces is deliberately an ordinary unpaid order (status "new",
 * payment_status "pending") so the buyer settles it through the existing payment
 * screen, rather than this service growing a second payment path of its own.
 */
class RfqOfferConverter
{
    public function __construct(private ?FinanceCalculator $finance = null)
    {
        $this->finance ??= new FinanceCalculator();
    }

    /**
     * @throws RuntimeException when the offer cannot legitimately be accepted.
     */
    public function accept(BulkRfqOffer $offer, User $buyer): Order
    {
        $rfq = $offer->rfq;

        $this->guard($offer, $rfq, $buyer);

        $vendor = $rfq->vendor()->with('currency')->firstOrFail();
        $amounts = $this->convertToVendorCurrency($offer, $vendor);

        $order = DB::transaction(function () use ($offer, $rfq, $buyer, $vendor, $amounts) {
            $order = Order::create([
                'order_number'        => Order::generateOrderNumber(),
                'user_id'             => $buyer->id,
                'vendor_id'           => $vendor->id,
                'grand_total'         => $amounts['grand_total'],
                'total_remaining'     => $amounts['grand_total'],
                'total_paid'          => 0,
                'grand_total_usd'     => $amounts['grand_total_usd'],
                'shipping_amount'     => $amounts['shipping'],
                'shipping_amount_usd' => $amounts['shipping_usd'],
                'rate_to_usd'         => $amounts['vendor_rate'],
                'payment_method'      => 'pending',
                'payment_status'      => 'pending',
                'status'              => 'new',
                'notes'               => "Commande issue du devis #{$offer->id} (demande de prix #{$rfq->id}) — "
                    . "{$offer->moq} unités à {$offer->unit_price} {$offer->currency}, "
                    . "incoterm {$offer->shipping_terms}, délai {$offer->lead_time_days} jours.",
            ]);

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $rfq->product_id,
                'quantity'     => $offer->moq,
                'unit_amount'  => $amounts['unit_amount'],
                'total_amount' => $amounts['goods_total'],
            ]);

            $this->createDeliveryAddress($order, $rfq, $buyer);

            $offer->update(['status' => BulkRfqOffer::STATUS_ACCEPTED]);
            $rfq->update(['status' => 'accepted']);

            // Any other quote on the same request is out of the running once one is
            // taken, otherwise a second vendor could still be shown as "pending".
            BulkRfqOffer::where('bulk_rfq_id', $rfq->id)
                ->whereKeyNot($offer->id)
                ->update(['status' => BulkRfqOffer::STATUS_REJECTED]);

            $rfq->messages()->create([
                'sender_type' => User::class,
                'sender_id'   => $buyer->id,
                'message'     => "Devis accepté. Commande {$order->order_number} créée pour "
                    . number_format((float) $offer->unit_price, 2) . " {$offer->currency} l'unité × {$offer->moq}.",
            ]);

            // Reuses the same commission / gateway / wire-fee breakdown as a normal
            // checkout instead of writing a second version of it here.
            $this->finance->createFinancialTransactionsForOrder($order->fresh('vendor'));

            return $order;
        });

        // After the commit, never inside it: a mail failure must not roll back an order
        // that is already valid, and the vendor's copy is not worth losing the sale over.
        $this->notifyVendor(
            $vendor,
            new RfqOfferAccepted($offer->fresh(), $order->fresh(['vendor.currency', 'user'])),
            'acceptation'
        );

        return $order;
    }

    /**
     * Turning a quote down. The request reopens when this was the last quote standing,
     * so the vendor can send a revised price — that is the whole point of a negotiation,
     * and the vendor's quote screen only accepts a request that is back to "pending".
     *
     * @throws RuntimeException when the offer cannot legitimately be rejected.
     */
    public function reject(BulkRfqOffer $offer, User $buyer, ?string $reason = null): void
    {
        $rfq = $offer->rfq;

        $this->guard($offer, $rfq, $buyer);

        $reason = trim((string) $reason) ?: null;

        $canRequote = DB::transaction(function () use ($offer, $rfq, $buyer, $reason) {
            $offer->update(['status' => BulkRfqOffer::STATUS_REJECTED]);

            $stillOpen = BulkRfqOffer::where('bulk_rfq_id', $rfq->id)
                ->where('status', BulkRfqOffer::STATUS_PENDING)
                ->exists();

            if (! $stillOpen) {
                $rfq->update(['status' => 'pending']);
            }

            $rfq->messages()->create([
                'sender_type' => User::class,
                'sender_id'   => $buyer->id,
                'message'     => $reason
                    ? "Devis refusé. Motif : {$reason}"
                    : 'Devis refusé.',
            ]);

            return ! $stillOpen;
        });

        $this->notifyVendor(
            $offer->vendor,
            new RfqOfferRejected($offer->fresh(), $reason, $canRequote),
            'refus'
        );
    }

    /**
     * The vendor account is reached through its owning user. A missing address or a
     * failing mail server is logged and swallowed — the decision itself is already
     * recorded, and the vendor also sees it in the conversation.
     */
    private function notifyVendor(?Vendor $vendor, $mailable, string $context): void
    {
        $email = $vendor?->user?->email;

        if (! $email) {
            Log::warning("Notification RFQ ({$context}) non envoyée : la boutique n'a pas d'e-mail.", [
                'vendor_id' => $vendor?->id,
            ]);

            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            Log::error("Échec de l'envoi de la notification RFQ ({$context}).", [
                'vendor_id' => $vendor?->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function guard(BulkRfqOffer $offer, ?BulkRfq $rfq, User $buyer): void
    {
        if (! $rfq) {
            throw new RuntimeException("Ce devis n'est rattaché à aucune demande de prix.");
        }

        if ($rfq->user_id !== $buyer->id) {
            throw new RuntimeException("Cette demande de prix n'est pas la vôtre.");
        }

        if (! $offer->isPending()) {
            throw new RuntimeException('Ce devis a déjà été traité.');
        }

        if ($rfq->status === 'accepted') {
            throw new RuntimeException('Un devis a déjà été accepté pour cette demande.');
        }

        if (in_array($rfq->status, ['cancelled', 'rejected'], true)) {
            throw new RuntimeException('Cette demande de prix est clôturée.');
        }

        if (! $rfq->product_id) {
            throw new RuntimeException('Cette demande ne référence aucun produit : commande impossible.');
        }
    }

    /**
     * A quote is priced in whatever currency the vendor chose, which is not necessarily
     * the currency their shop bills in. Everything is routed through USD — the pivot the
     * rest of the application already uses — so the order is stored in the vendor's own
     * currency like every other order.
     *
     * @return array<string, float>
     */
    private function convertToVendorCurrency(BulkRfqOffer $offer, Vendor $vendor): array
    {
        $offerRate = $this->rateToUsd($offer->currency);
        $vendorRate = (float) ($vendor->currency?->rate_to_usd ?? 1);

        if ($vendorRate <= 0) {
            throw new RuntimeException("La devise de la boutique {$vendor->store_name} n'a pas de taux de change utilisable.");
        }

        $goodsUsd = (float) $offer->unit_price * (int) $offer->moq * $offerRate;
        $shippingUsd = (float) $offer->shipping_cost * $offerRate;

        $goodsLocal = $goodsUsd / $vendorRate;

        return [
            'vendor_rate'     => $vendorRate,
            'goods_total'     => round($goodsLocal, 2),
            'unit_amount'     => round($goodsLocal / max(1, (int) $offer->moq), 2),
            'shipping'        => round($shippingUsd / $vendorRate, 2),
            'shipping_usd'    => round($shippingUsd, 2),
            'grand_total'     => round(($goodsUsd + $shippingUsd) / $vendorRate, 2),
            'grand_total_usd' => round($goodsUsd + $shippingUsd, 2),
        ];
    }

    private function rateToUsd(?string $code): float
    {
        $currency = Currency::where('code', $code)->first();

        if (! $currency) {
            throw new RuntimeException(
                "Le devis est libellé en {$code}, une devise que la plateforme ne connaît pas. "
                . 'Demandez au vendeur un devis dans une devise gérée.'
            );
        }

        return (float) $currency->rate_to_usd;
    }

    /**
     * Orders carry their own delivery address row. The buyer's default address book
     * entry is copied when there is one; otherwise the shipping details captured on the
     * request itself are used, so the order is never left without an address.
     */
    private function createDeliveryAddress(Order $order, BulkRfq $rfq, User $buyer): void
    {
        $saved = Address::where('user_id', $buyer->id)
            ->whereNull('order_id')
            ->orderByDesc('is_default')
            ->first();

        [$firstName, $lastName] = $this->splitName($buyer->name);

        Address::create([
            'order_id'       => $order->id,
            'user_id'        => $buyer->id,
            'first_name'     => $saved->first_name ?? $firstName,
            'last_name'      => $saved->last_name ?? $lastName,
            'phone'          => $saved->phone ?? ($buyer->phone ?? '—'),
            'street_address' => $saved->street_address ?? ($rfq->shipping_port ?: '—'),
            'city'           => $saved->city ?? ($rfq->shipping_city ?: '—'),
            'state'          => $saved->state ?? ($rfq->shipping_city ?: '—'),
            'zip_code'       => $saved->zip_code ?? null,
            'country'        => $saved->country ?? ($rfq->shipping_country ?: 'Guinea'),
            'locality_id'    => $saved->locality_id ?? null,
            'is_default'     => false,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2) ?: [];

        return [$parts[0] ?? 'Client', $parts[1] ?? '—'];
    }
}
