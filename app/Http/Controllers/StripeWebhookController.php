<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Stripe telling us, directly, that a Checkout Session was paid.
 *
 * Until this existed the only thing that credited a card payment was the buyer's browser
 * landing back on the success page. A buyer who paid and closed the tab — or whose phone
 * never opened the mobile deep link — was charged and left with an order that stayed
 * unpaid. The recorder is shared with that page, so whichever arrives first records the
 * money and the other finds it already there.
 *
 * Authenticated by Stripe's signature over the raw body, not by a login.
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripePaymentRecorder $recorder): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            // Refusing loudly beats accepting unsigned calls: Stripe retries a 5xx, so
            // the events are not lost once the secret is configured.
            Log::error('Stripe webhook received but STRIPE_WEBHOOK_SECRET is not set');

            return response()->json(['message' => 'Webhook not configured'], 503);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
                $secret
            );
        } catch (SignatureVerificationException | \UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // async_payment_succeeded: delayed methods report their money later than
        // "completed"; the recorder itself ignores a session that is not paid yet.
        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            try {
                $recorder->record($event->data->object);
            } catch (\Throwable $e) {
                // A 5xx makes Stripe deliver the event again, which is what we want for a
                // failure that may be temporary. The recorder is idempotent.
                Log::error('Stripe webhook: recording the payment failed', [
                    'event' => $event->id ?? null,
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['message' => 'Could not record the payment'], 500);
            }
        }

        return response()->json(['received' => true]);
    }
}
