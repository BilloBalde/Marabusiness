<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// shipments:sync existed and worked but was never scheduled anywhere — every
// shipment's tracking status sat frozen at whatever it was when created. Every
// 30 minutes is frequent enough for a buyer checking their order to see real
// movement, without hammering the carrier's API for shipments that are not
// moving between checks. withoutOverlapping() guards against a slow carrier
// response still running when the next tick fires.
//
// This definition alone does not run anything: nothing calls
// `php artisan schedule:run` on a timer in production. A Render Cron Job would
// be the usual way to do that, but Render's persistent disk — where the sqlite
// database actually lives — is bound to this one web service; a separate Cron
// Job service could not see the same database file to sync into. A manual
// "Synchroniser le suivi" button was added instead
// (ShipmentResource\Pages\ListShipments), which runs in this same process and
// has no such problem. This schedule stays defined so it starts working the
// moment either of those constraints changes (a real network-reachable database,
// or a scheduler mechanism that runs inside this same service).
Schedule::command('shipments:sync')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('shipments:sync scheduled run failed.');
    });

// Negotiated prices that ran out. Hourly is enough: a price is valid for days,
// so the worst a late sweep costs is an order showing a lapsed figure for a few
// minutes longer.
//
// The same caveat as above applies, and matters less here on purpose. Nothing
// runs this on a timer in production, so the expiry is enforced where it is
// actually used instead: OrderNegotiation::accept() re-reads the expiry inside
// its transaction and refuses a lapsed price, and Order::hasLiveOffer() drives
// what the buyer is offered. This command tidies up and tells the thread; it is
// not what makes an expired price unusable.
Schedule::command('negotiations:expire')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('negotiations:expire scheduled run failed.');
    });
