<?php

namespace App\Support;

use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * The one place the application asks Stripe about a Checkout Session.
 *
 * A thin seam rather than a static call inside each page: tests replace it with a
 * fake, so the payment-recording rules can be exercised without the network.
 */
class StripeSessions
{
    public function retrieve(string $sessionId): object
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return Session::retrieve($sessionId);
    }
}
