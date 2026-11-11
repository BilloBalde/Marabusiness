<?php

namespace App\Support;

use App\Mail\VendorOrderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells each shop owner about the order they just received.
 *
 * The web checkout and the mobile API each had their own copy of this loop, and both
 * read `$order->vendor->email`. The vendors table has no email column: the address
 * is the owner's login (`vendor->user->email`). The condition was always false, so
 * no vendor was ever told about a new order — and the template behind it pointed
 * its button at a route that does not exist, so it would have failed once the
 * address was right.
 *
 * Never allowed to break the order it announces.
 */
class VendorOrderNotifier
{
    /** @param iterable<\App\Models\Order> $orders */
    public static function send(iterable $orders): void
    {
        foreach ($orders as $order) {
            $email = $order->vendor?->user?->email;

            if (! $email) {
                Log::warning('Vendor notification skipped: the shop has no owner email', [
                    'order_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                ]);

                continue;
            }

            try {
                Mail::to($email)->send(new VendorOrderNotification($order));

                Log::info('Vendor notification sent', [
                    'order_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to send vendor notification', [
                    'order_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
