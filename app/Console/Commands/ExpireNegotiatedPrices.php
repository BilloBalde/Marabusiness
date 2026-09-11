<?php

namespace App\Console\Commands;

use App\Models\BulkRfqMessage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clears prices the buyer let lapse.
 *
 * This is bookkeeping, not enforcement. OrderNegotiation::accept() re-reads
 * negotiated_expires_at inside its own transaction and refuses an expired price
 * whether or not this ever ran, and Order::hasLiveOffer() drives every button
 * the buyer sees. That matters here more than usual: nothing calls
 * `php artisan schedule:run` on a timer in production — Render's persistent disk
 * holds the sqlite database and is bound to the single web service, so a
 * separate Cron Job service cannot see it (the same constraint routes/console.php
 * records for shipments:sync). Anything that depended on this command having run
 * would be broken by default on that host.
 *
 * What it does add: the order stops advertising a price that is gone, and the
 * thread says so, instead of both quietly showing a stale figure until someone
 * clicks it.
 */
class ExpireNegotiatedPrices extends Command
{
    protected $signature = 'negotiations:expire {order? : limit to one order id}';

    protected $description = 'Reopens negotiations whose agreed price has run out';

    public function handle(): int
    {
        $query = Order::query()
            ->where('status', Order::STATUS_NEGOTIATING)
            ->where('negotiation_status', Order::NEGOTIATION_PRICED)
            ->whereNotNull('negotiated_expires_at')
            ->where('negotiated_expires_at', '<', now());

        if ($id = $this->argument('order')) {
            $query->whereKey($id);
        }

        $orders = $query->with('negotiation')->get();

        if ($orders->isEmpty()) {
            $this->info('Aucun prix expiré.');

            return self::SUCCESS;
        }

        $expired = 0;

        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $order->update([
                        'negotiated_total' => null,
                        'negotiated_expires_at' => null,
                        'negotiation_status' => Order::NEGOTIATION_OPEN,
                    ]);

                    if ($rfq = $order->negotiation) {
                        BulkRfqMessage::create([
                            'bulk_rfq_id' => $rfq->id,
                            'sender_type' => User::class,
                            'sender_id' => $order->user_id,
                            'message' => "Le prix proposé a expiré. La discussion reste ouverte.",
                        ]);

                        $rfq->update(['status' => 'pending']);
                    }
                });

                $expired++;
            } catch (\Throwable $e) {
                // One bad order must not stop the rest.
                report($e);
                $this->error("Commande {$order->id} : {$e->getMessage()}");
            }
        }

        $this->info("{$expired} prix expiré(s).");

        return self::SUCCESS;
    }
}
