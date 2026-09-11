<?php
// app/Http/Controllers/Api/PaymentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\FinancialTransaction;
use App\Services\FinanceCalculator;
use App\Services\LengoPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends Controller
{
    private $financeCalculator;

    public function __construct()
    {
        $this->financeCalculator = new FinanceCalculator();
    }

    /**
     * Create a payment session (Stripe or LengoPay)
     */
    public function createPaymentSession(Request $request, $orderId)
    {
        \Log::info('🔵 createPaymentSession called', [
            'order_id' => $orderId,
            'payment_method' => $request->payment_method,
            'user_id' => Auth::id()
        ]);
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:cod,om,stripe,lengopay',
        ]);

        if ($validator->fails()) {
            \Log::error('❌ Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            \Log::error('❌ User not authenticated');
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        $order = Order::where('user_id', $user->id)
            ->with('vendor.currency')
            ->findOrFail($orderId);

        if (!$order) {
            \Log::error('❌ Order not found', ['order_id' => $orderId]);
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        \Log::info('✅ Order found', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status
        ]);

        $paymentMethod = $request->payment_method;
        $platform = $request->input('platform', 'web');

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This order is already paid'
            ], 400);
        }

        // A price still under discussion is not a price to collect. Only
        // payment_status was ever checked here, so an order in negotiation would
        // have been payable at whatever provisional figure the basket carried.
        if ($order->isNegotiating()) {
            return response()->json([
                'success' => false,
                'message' => 'Le prix de cette commande est en cours de négociation. Acceptez le prix proposé avant de régler.',
            ], 409);
        }

        $vendor = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';

        // Handle different payment methods
        switch ($paymentMethod) {
            case 'stripe':
                return $this->createStripeSession($order, $platform);
            
            case 'lengopay':
                return $this->createLengoPaySession($order, $platform);
            
            case 'cod':
                \Log::info('💰 Processing COD payment', ['order_id' => $order->id]);

                // Nothing stopped a buyer tapping "pay on delivery" twice: each tap
                // wrote another payment row for the same money, and the vendor saw
                // two claims for one delivery. Same guard as submitOfflinePayment
                // and PaiementModal on the web.
                if ($order->declaredAwaitingConfirmation() > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un paiement est déjà déclaré pour cette commande et attend la validation du vendeur.',
                    ], 409);
                }

                $payment = Paiement::create([
                    'order_id' => $order->id,
                    'amount' => $order->total_remaining,
                    // Cash has no receipt to photograph, so there is no proof image —
                    // 'payments/default.png' was a file that never existed in storage,
                    // and the back office rendered a broken image for every one of
                    // these. Null is what submitOfflinePayment stores for cash, and
                    // what the Filament image column already handles.
                    'image' => null,
                    'payment_method' => 'cod',
                    'currency' => $currency,
                    // Coverage of the order total, not proof of collection — the same
                    // meaning this column carries at every other creation site.
                    // confirmed_at, left null here, is what says the money arrived.
                    'payment_status' => 'paid',
                    'transaction_id' => Order::generateTransactionNumber(),
                    'confirmed_at' => null,
                    'confirmed_by' => null,
                ]);

                // Cash on delivery declared from the mobile app: recorded, but the order
                // is only settled once the vendor confirms the courier collected it.
                $order->syncPaymentTotals();

                // Update financial transactions
                $this->updateFinancialTransactions($order, 'cod', $order->total_remaining, 'paid cash');

                // COD doesn't need a session. The reply used to say "COD payment
                // confirmed", which is exactly the confusion this whole flow exists to
                // avoid: the buyer has declared it, the vendor has not confirmed it.
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement à la livraison enregistré, en attente de validation du vendeur.',
                    'data' => [
                        'order_id' => $order->id,
                        'payment_method' => 'cod',
                        'payment_id' => $payment->id,
                        'confirmed' => false,
                        'redirect_url' => null
                    ]
                ]);
            
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported payment method'
                ], 400);
        }
    }

    /**
     * Submit offline payment (OM with screenshot)
     */
    public function submitOfflinePayment(Request $request, $orderId)
    {
        // Cash on delivery was rejected outright by this endpoint ('in:om'), and a
        // proof image was always required — so a mobile buyer paying the courier in
        // cash had no way at all to declare it, while the same buyer on the web
        // could (PaiementModal). Proof is required for Orange Money, where money
        // actually moves and there is a receipt to show, and optional for cash,
        // where there is nothing to screenshot — the same rule as
        // Paiement::METHODS_REQUIRING_PROOF.
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:' . implode(',', Paiement::OFFLINE_METHODS),
            'amount' => 'required|numeric|min:1',
            'image' => 'required_if:payment_method,om|image|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
            ->with('vendor.currency')
            ->findOrFail($orderId);

        // Same reason as createPaymentSession: nothing is owed until a price is
        // agreed, so nothing can be declared against it either.
        if ($order->isNegotiating()) {
            return response()->json([
                'success' => false,
                'message' => 'Le prix de cette commande est en cours de négociation. Acceptez le prix proposé avant de régler.',
            ], 409);
        }

        // A declaration already waiting on the vendor blocks a second one, exactly
        // as PaiementModal does on the web — otherwise a buyer whose first
        // declaration has not been confirmed yet can declare the same money again,
        // and the vendor sees two claims for one payment.
        if ($order->declaredAwaitingConfirmation() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Un paiement est déjà déclaré pour cette commande et attend la validation du vendeur.',
            ], 409);
        }

        // Validate amount matches exactly
        if (abs($request->amount - $order->total_remaining) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Amount must be exactly ' . $order->total_remaining . ' ' . $order->currency
            ], 400);
        }

        try {
            // Optional for cash on delivery — see the validation rules above.
            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('payments', 'public')
                : null;

            $vendor = $order->vendor;
            $currency = $vendor->currency->code ?? 'USD';
            $rate = $vendor->currency->rate_to_usd ?? 1;

            $paidUsd = $request->amount / $rate;
            $totalUsd = $order->grand_total / $rate;

            // Create payment record. The method was hardcoded to 'om' while the
            // financial transaction below was hardcoded to 'cod' — the two
            // contradicted each other, and both ignored what the buyer actually
            // chose. confirmed_at stays null on purpose: this is the buyer saying
            // they paid, not the vendor confirming the money arrived.
            $payment = Paiement::create([
                'order_id' => $order->id,
                'amount' => $request->amount,
                'image' => $imagePath,
                'payment_method' => $request->payment_method,
                'currency' => $currency,
                'payment_status' => $paidUsd >= $totalUsd ? 'paid' : 'partial',
                'transaction_id' => Order::generateTransactionNumber(),
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]);

            // Offline payment declared from the mobile app; awaits the vendor's
            // confirmation before it counts towards the balance.
            $order->syncPaymentTotals();

            // Update financial transactions
            $this->updateFinancialTransactions($order, $request->payment_method, $request->amount, 'payment declared by buyer');

            return response()->json([
                'success' => true,
                'message' => 'Payment submitted successfully',
                'data' => [
                    'payment' => $payment,
                    'order' => [
                        'id' => $order->id,
                        'total_paid' => $order->total_paid,
                        'total_remaining' => $order->total_remaining,
                        'payment_status' => $order->payment_status,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Offline payment submission failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Stripe payment session
     */
    private function createStripeSession($order, $platform = 'web')
    {
        $amountRemaining = $order->total_remaining;
        $currency = strtolower(
            $order->currency 
            ?? optional($order->vendor?->currency)->code 
            ?? 'usd'
        );
        $rate = $order->vendor?->currency->rate_to_usd ?? 1;
        $usdAmount = $amountRemaining * $rate;

        $stripeAmount = $currency == 'usd' 
            ? intval($amountRemaining * 100) 
            : intval($amountRemaining * $rate * 100);

        // Validate Stripe amount limits
        if ($stripeAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment amount'
            ], 400);
        }

        if ($stripeAmount > 99999999) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds Stripe limit'
            ], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $baseUrl = $platform === 'mobile' ? 'mara://' : config('app.url');
            $successUrl = $baseUrl . '/payment/success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id;
            $cancelUrl  = $baseUrl . '/orders/' . $order->id . '?canceled=true';
            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => Auth::user()->email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "Paiement commande #{$order->order_number}",
                        ],
                        'unit_amount' => $stripeAmount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount_usd' => $usdAmount,
                    'amount_local' => $amountRemaining,
                    'currency' => $currency,
                    'rate' => $rate,
                ],
            ]);

            // Save Stripe session ID to order
            $order->update([
                'stripe_session_id' => $session->id,
                'payment_method' => 'stripe',
            ]);

            // Update financial transactions
            $this->updateFinancialTransactions($order, 'stripe', 0, 'Stripe payment initiated');

            return response()->json([
                'success' => true,
                'message' => 'Stripe session created',
                'data' => [
                    'payment_url' => $session->url,
                    'session_id' => $session->id,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe session creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Stripe error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create LengoPay payment session
     */
    private function createLengoPaySession($order, $platform = 'web')
    {
        $currency = strtoupper(
            $order->currency 
            ?? optional($order->vendor?->currency)->code 
            ?? 'GNF'
        );

        if ($currency !== 'GNF') {
            return response()->json([
                'success' => false,
                'message' => 'LengoPay is available only for GNF payments'
            ], 400);
        }

        $amountRemaining = (float) $order->total_remaining;

        if ($amountRemaining <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing to pay for this order'
            ], 400);
        }

        try {
            $lengo = app(LengoPayService::class);

            // Web and mobile share one absolute https return URL. On Android it is
            // registered as an App Link and main.dart matches the deep link on host +
            // path, so a mara:// scheme would give an empty host and never be caught.
            $returnUrl = rtrim(config('app.frontend_url'), '/') . '/payment/success?order_id=' . $order->id;
            $callbackUrl = route('lengopay.callback');

            $result = $lengo->createPayment(
                amount: $amountRemaining,
                returnUrl: $returnUrl,
                callbackUrl: $callbackUrl,
                currency: 'GNF'
            );

            if (empty($result['payment_url']) || empty($result['pay_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'LengoPay did not return a payment link'
                ], 500);
            }

            // Update order with LengoPay details
            $order->update([
                'payment_method' => 'lengopay',
                'payment_status' => 'pending',
                'lengopay_pay_id' => $result['pay_id'],
                'lengopay_payment_url' => $result['payment_url'],
            ]);

            // Update financial transactions
            $this->updateFinancialTransactions($order, 'lengopay', 0, 'LengoPay payment initiated');

            return response()->json([
                'success' => true,
                'message' => 'LengoPay session created',
                'data' => [
                    'payment_url' => $result['payment_url'],
                    'pay_id' => $result['pay_id'],
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('LengoPay init failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'LengoPay error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update financial transactions
     */
    private function updateFinancialTransactions($order, $paymentMethod, $amountPaid, $description = null)
    {
        $totalAmount = $order->grand_total;
        $paymentPercentage = $amountPaid > 0 ? ($amountPaid / $totalAmount) * 100 : 0;

        // Find or create revenue transaction
        $revenueTransaction = FinancialTransaction::where('order_id', $order->id)
            ->where('transaction_type', FinancialTransaction::TYPE_ORDER)
            ->first();

        if (!$revenueTransaction) {
            $this->createFinancialTransactionsForOrder($order);
            $revenueTransaction = FinancialTransaction::where('order_id', $order->id)
                ->where('transaction_type', FinancialTransaction::TYPE_ORDER)
                ->first();
        }

        if ($revenueTransaction) {
            if ($amountPaid >= $totalAmount) {
                // Full payment received
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'description' => "Order #{$order->order_number} - Payment completed via {$paymentMethod}",
                ]);

                // Mark commission and gateway fees as processed
                FinancialTransaction::where('order_id', $order->id)
                    ->whereIn('transaction_type', [
                        FinancialTransaction::TYPE_COMMISSION,
                        FinancialTransaction::TYPE_GATEWAY_FEE,
                    ])
                    ->update([
                        'status' => FinancialTransaction::STATUS_PROCESSED,
                        'processed_at' => now(),
                    ]);

            } elseif ($amountPaid > 0) {
                // Partial payment
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PENDING,
                    'description' => "Order #{$order->order_number} - Partial payment ({$paymentPercentage}%) via {$paymentMethod}",
                    'metadata' => array_merge($revenueTransaction->metadata ?? [], [
                        'partial_payment_amount' => $amountPaid,
                        'partial_payment_percentage' => $paymentPercentage . '%',
                        'payment_method' => $paymentMethod,
                    ]),
                ]);

            } else {
                // Payment initiated but not received yet
                $revenueTransaction->update([
                    'description' => $description ?? "Order #{$order->order_number} - Payment initiated via {$paymentMethod}",
                ]);
            }
        }
    }

    /**
     * Create financial transactions for an order
     */
    private function createFinancialTransactionsForOrder($order)
    {
        $vendor = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';
        $breakdown = $this->financeCalculator->calculateOrderBreakdown($order);

        // Check if transactions already exist
        if (FinancialTransaction::where('order_id', $order->id)->count() > 0) {
            return;
        }

        // 1. Order Revenue Transaction
        FinancialTransaction::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'transaction_type' => FinancialTransaction::TYPE_ORDER,
            'amount' => $breakdown['gross_amount'],
            'currency' => $currency,
            'description' => "Order #{$order->order_number} - Gross revenue",
            'reference_number' => $order->order_number . '-REV',
            'gateway_fee' => 0,
            'commission_fee' => 0,
            'wire_fee' => 0,
            'net_amount' => $breakdown['gross_amount'],
            'status' => FinancialTransaction::STATUS_PENDING,
            'metadata' => [
                'order_number' => $order->order_number,
                'customer_id' => $order->user_id,
            ],
        ]);

        // 2. Commission Fee Transaction
        if ($breakdown['commission'] > 0) {
            FinancialTransaction::create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'transaction_type' => FinancialTransaction::TYPE_COMMISSION,
                'amount' => $breakdown['commission'] * -1,
                'currency' => $currency,
                'description' => "Commission for Order #{$order->order_number}",
                'reference_number' => $order->order_number . '-COMM',
                'gateway_fee' => 0,
                'commission_fee' => $breakdown['commission'],
                'wire_fee' => 0,
                'net_amount' => $breakdown['commission'] * -1,
                'status' => FinancialTransaction::STATUS_PENDING,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'commission_rate' => $breakdown['breakdown']['commission_percentage'] . '%',
                ],
            ]);
        }
    }

    /**
     * Handle Stripe webhook (for payment confirmation)
     */
    public function handleStripeWebhook(Request $request)
    {
        // This endpoint would be called by Stripe to confirm payment
        // You'll need to implement webhook handling here
    }

    /**
     * Handle LengoPay callback
     */
    public function handleLengoPayCallback(Request $request)
    {
        // This endpoint would be called by LengoPay to confirm payment
        // You'll need to implement callback handling here
    }
}