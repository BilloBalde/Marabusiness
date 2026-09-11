<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\CartManagement;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Paiement;
use App\Models\Vendor;
use App\Models\FinancialTransaction;
use App\Http\Controllers\Api\Concerns\CalculatesCheckoutShipping;
use App\Support\ShippingCarrierFilter;
use App\Services\FinanceCalculator;
use App\Services\LengoPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use App\Support\Money;

class CheckoutController extends Controller
{
    use CalculatesCheckoutShipping;

    private $financeCalculator;

    public function __construct()
    {
        $this->financeCalculator = new FinanceCalculator();
    }

    public function calculateShipping(Request $request)
    {
        $request->validate([
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
            'shipping_carrier' => 'nullable|string',
        ]);

        $cart = CartManagement::getCartItemsFromCookie();
        
        // Filter selected items
        $selectedItems = array_filter($cart, fn ($item) => 
            in_array($item['vendor_product_id'], $request->selected_ids)
        );

        if (empty($selectedItems)) {
            return response()->json(['message' => 'No items selected'], 400);
        }

        // Group items by vendor - FIX: Transform to the format ShippingCalculator expects
        $groupedItems = [];
        foreach ($selectedItems as $item) {
            $vendorId = $item['vendor_id'];
            if (!isset($groupedItems[$vendorId])) {
                $groupedItems[$vendorId] = [];
            }
            $groupedItems[$vendorId][] = [
                'product_id' => $item['product_id'] ?? null,
                'vendor_product_id' => $item['vendor_product_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'weight' => $item['weight'] ?? 0,  // Add weight
                'length' => $item['length'] ?? 0,  // Add dimensions for CBM
                'width' => $item['width'] ?? 0,
                'height' => $item['height'] ?? 0,
            ];
        }

        $destinationAddress = [
            'first_name' => $request->address['first_name'],
            'last_name' => $request->address['last_name'],
            'city' => $request->address['city'],
            'state' => $request->address['state'],
            'zip_code' => $request->address['zip_code'] ?? null,
            'locality_id' => $request->address['locality_id'] ?? null,
            'country' => $request->address['country'],
            'country_code' => $this->getCountryCode($request->address['country']),
            'street_address' => $request->address['street_address'],
            'latitude' => $request->address['latitude'] ?? null,
            'longitude' => $request->address['longitude'] ?? null,
        ];

        try {
            $shippingResult = $this->shippingCalculator()->calculateCartShipping(
                $groupedItems,
                $destinationAddress,
                $request->shipping_carrier ?? 'local'
            );

            // Only "Local Courier" is quoted: DHL/UPS/FedEx/Chronopost/CMA never
            // modelled a real air/sea distinction, they just carried different
            // constants in the same formula.
            $shippingResult['carriers'] = ShippingCarrierFilter::onlyLocal($shippingResult['carriers'] ?? []);

            // Enhance the response with additional vendor data for Flutter app
            $enhancedCarriers = [];
            foreach ($shippingResult['carriers'] ?? [] as $carrierKey => $carrierData) {
                $enhancedVendors = [];
                foreach ($carrierData['vendors'] ?? [] as $vendorId => $vendorData) {
                    // Find the original items for this vendor to get weight/cbm
                    $vendorItems = $groupedItems[$vendorId] ?? [];
                    $totalWeight = 0;
                    $totalCbm = 0;
                    $totalItems = 0;
                    
                    foreach ($vendorItems as $item) {
                        $quantity = $item['quantity'];
                        $totalItems += $quantity;
                        $totalWeight += ($item['weight'] ?? 0) * $quantity;
                        
                        // Calculate CBM (cubic meters) if dimensions are available
                        $length = $item['length'] ?? 0;
                        $width = $item['width'] ?? 0;
                        $height = $item['height'] ?? 0;
                        $cbmPerItem = ($length * $width * $height) / 1000000; // Convert to cubic meters
                        $totalCbm += $cbmPerItem * $quantity;
                    }
                    
                    $enhancedVendors[$vendorId] = [
                        'vendor_id' => (int) $vendorId,
                        'zone' => $vendorData['zone'] ?? 'Unknown',
                        'cost' => (float) ($vendorData['cost'] ?? 0),
                        'total_weight' => $totalWeight,
                        'total_cbm' => $totalCbm,
                        'total_items' => $totalItems,
                        'delivery_days' => $vendorData['delivery_days'] ?? 3,
                    ];
                }
                
                $enhancedCarriers[$carrierKey] = [
                    'name' => $carrierData['name'] ?? $carrierKey,
                    'description' => $carrierData['description'] ?? null,
                    'total_cost_usd' => (float) ($carrierData['total_cost_usd'] ?? 0),
                    'vendors' => $enhancedVendors,
                    'is_available' => $carrierData['is_available'] ?? true,
                ];
            }

            $response = [
                'carriers' => $enhancedCarriers,
                'selected_carrier' => $shippingResult['selected_carrier'] ?? null,
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Shipping calculation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to calculate shipping',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
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
            'payment_method' => 'required|string|in:cash,stripe,lengopay,cod,om',
            'shipping_carrier' => 'required|string',
            'save_address' => 'boolean',
            'amount' => 'required_if:payment_method,om|numeric|min:1',
            'image' => 'required_if:payment_method,om|image|max:4096',
        ]);

        // 'stripe' is the legacy value already-installed app builds send for LengoPay.
        // This endpoint has never created a Stripe session - the branch below refuses
        // any currency other than GNF - so normalise it and record the real gateway.
        $paymentMethod = $request->payment_method === 'stripe' ? 'lengopay' : $request->payment_method;

        $user = Auth::user();
        $cart = CartManagement::getCartItemsFromCookie();
        
        // Filter selected items
        $selectedItems = array_filter($cart, fn ($item) => 
            in_array($item['vendor_product_id'], $request->selected_ids)
        );

        if (empty($selectedItems)) {
            return response()->json(['message' => 'Pas d\'articles sélectionnés'], 400);
        }

        // Group by vendor
        $groups = [];
        foreach ($selectedItems as $item) {
            $vendorId = $item['vendor_id'];
            if (!isset($groups[$vendorId])) {
                $groups[$vendorId] = [];
            }
            $groups[$vendorId][] = $item;
        }

        DB::beginTransaction();

        try {
            // Stock was last checked when these items went into the cart, and a
            // cart never expires: CartManagement::getCartItemsFromCookie() reads
            // cart_items rows keyed on user_id — the cookie in the name is a
            // leftover — and nothing prunes them, so an item can sit there for
            // months. Nothing between there and here looked
            // again, while updateStock() below writes max(0, stock - quantity) —
            // so selling three of something with one left stored 0 rather than
            // -2, and the oversell left no trace in the data at all. Checked here,
            // inside the transaction and with the rows locked, so two checkouts
            // racing for the same last unit cannot both pass.
            $shortfalls = $this->findStockShortfalls($selectedItems);

            if ($shortfalls !== []) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour certains articles.',
                    'out_of_stock' => $shortfalls,
                ], 409);
            }

            $orders = [];
            $redirectUrl = null;
            $emailErrors = [];

            Log::info('PlaceOrder shipping_carrier:', ['carrier' => $request->shipping_carrier]);

            $shippingResult = $this->calculateFullShipping($request, $selectedItems);
            $shippingBreakdown = $shippingResult['vendors'] ?? [];

            Log::info('Shipping breakdown in placeOrder after fix:', $shippingBreakdown);

            // Pour le vendor concerné (vendor 4 dans votre cas)
            Log::info('Vendor 4 shipping data:', [
                'vendor_4_data' => $shippingBreakdown[4] ?? 'Not found',
                'vendor_id_4' => 4,
                'all_vendors' => array_keys($shippingBreakdown),
            ]);
            $selectedCarrier = [
                'name' => $shippingResult['name'] ?? 'Local Delivery',
                'carrier' => $request->shipping_carrier,
                'total_cost_usd' => $shippingResult['total_cost_usd'] ?? 0,
                'vendors' => $shippingBreakdown,
            ];

            // Save address if requested
            if ($request->save_address && $user) {
                $addressData = [
                    'user_id' => $user->id,
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
                ];
                
                // Check if this should be default
                if (Address::where('user_id', $user->id)->count() === 0) {
                    $addressData['is_default'] = true;
                }
                
                Address::create($addressData);
            }

            foreach ($groups as $vendorId => $items) {
                $vendor = Vendor::with('currency')->find($vendorId);
                if (!$vendor) {
                    throw new \Exception("Vendor not found for ID: {$vendorId}");
                }
                
                $rate = $vendor->currency->rate_to_usd ?? 1;
                $currency = $vendor->currency->code ?? 'USD';
                
                // Calculate subtotal
                $subtotal = collect($items)->sum('total_amount');
                
                // Get shipping for this vendor
                $vendorShippingUSD = $shippingBreakdown[$vendorId]['cost'] ?? 0;
                $vendorShippingLocal = $this->convertUsdToVendorCurrency($vendorShippingUSD, $rate);
                
                $vendorZone = $shippingBreakdown[$vendorId]['zone'] ?? 'Unknown';
                $vendorDeliveryDays = $shippingBreakdown[$vendorId]['delivery_days'] ?? 3;
                
                $total = $subtotal + $vendorShippingLocal;
                $totalUSD = ($subtotal * $rate) + $vendorShippingUSD;

                // Create order
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'grand_total' => $total,
                    'total_remaining' => $total,
                    'grand_total_usd' => $totalUSD,
                    'shipping_amount_usd' => $vendorShippingUSD,
                    'rate_to_usd' => $rate,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending',
                    'status' => 'new',
                    'shipping_carrier' => $request->shipping_carrier,
                    'shipping_amount' => $vendorShippingLocal,
                    'notes' => "Marketplace order — Vendor: {$vendor->store_name}",
                    'currency_id' => $vendor->currency_id,
                ]);

                $orders[] = $order;

                // Create address for order with zone if available
                $addressData = [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
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
                ];
                
                // Add zone if available from shipping calculation
                if (isset($vendorZone)) {
                    $addressData['zone'] = $vendorZone;
                }
                
                Address::create($addressData);

                // Create order items with variations
                foreach ($items as $item) {
                    $orderItemData = [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_amount' => $item['unit_amount'],
                        'total_amount' => $item['total_amount'],
                    ];
                    
                    // Add variations if they exist
                    if (!empty($item['selected_variations']) || !empty($item['variation_note'])) {
                        $variations = [];
                        
                        if (!empty($item['selected_variations'])) {
                            $variations = $item['selected_variations'];
                        }
                        
                        if (!empty($item['variation_note'])) {
                            $variations['note'] = $item['variation_note'];
                        }
                        
                        $orderItemData['variation_json'] = $variations;
                    }
                    
                    OrderItem::create($orderItemData);
                }

                // Create financial transactions
                $this->createFinancialTransactionsForOrder($order, $total);

                // Handle payment
                if ($paymentMethod === 'lengopay') {
                    if (strtoupper($currency) !== 'GNF') {
                        throw new \Exception('LengoPay is available only for GNF payments.');
                    }

                    $lengo = app(LengoPayService::class);

                    $result = $lengo->createPayment(
                        amount: (float) $total,
                        returnUrl: rtrim(config('app.frontend_url'), '/') . '/payment/success?order_id=' . $order->id,
                        callbackUrl: route('lengopay.callback'),
                        currency: 'GNF'
                    );

                    if (empty($result['payment_url']) || empty($result['pay_id'])) {
                        throw new \Exception('LengoPay did not return a payment link.');
                    }

                    // Update order with LengoPay details
                    $order->update([
                        'payment_status' => 'pending',
                        'lengopay_pay_id' => $result['pay_id'],
                        'lengopay_payment_url' => $result['payment_url'],
                    ]);

                    $this->updateFinancialTransactionStatus($order, 'pending', 'LengoPay payment initiated');

                    $redirectUrl = $result['payment_url'];
                    
                } elseif ($paymentMethod === 'om') {
                    // Validate exact amount for Orange Money
                    if (number_format($request->amount, 2) != number_format($total, 2)) {
                        throw new \Exception("Orange Money payment must be exactly " . number_format($total, 2) . " " . $currency);
                    }
                    
                    // Handle Orange Money
                    $path = $request->file('image')->store('payments', 'public_uploads');
                    
                    $pay = Paiement::create([
                        'order_id' => $order->id,
                        'amount' => $request->amount,
                        'image' => $path,
                        'payment_method' => 'om',
                        'currency' => $currency,
                        'payment_status' => $request->amount >= $total ? 'paid' : 'partial',
                        'transaction_id' => Order::generateTransactionNumber(),
                    ]);
                    
                    // The buyer has declared an Orange Money transfer; the vendor still
                    // has to confirm it arrived. syncPaymentTotals() counts confirmed
                    // money only, so the order stays unpaid until then.
                    $order->syncPaymentTotals();

                    $this->handleOMPaymentFinancialTransactions($order, $request->amount, $currency);
                    
                } else {
                    // Cash or COD
                    $this->updateFinancialTransactionStatus($order, 'pending', 'Cash/COD payment pending');
                }

                // Update stock (including variations)
                $this->updateStock($items);
            }

            // Clear selected items from cart
            foreach ($request->selected_ids as $vendorProductId) {
                $cartItems = CartManagement::getCartItemsFromCookie();
                foreach ($cartItems as $item) {
                    if (in_array($item['vendor_product_id'], $request->selected_ids)) {
                        CartManagement::removeCartItem($item['cart_key']);
                    }
                }
            }

            DB::commit();

            // Send emails AFTER successful commit
            try {
                // Send order confirmation emails to customers
                foreach ($orders as $order) {
                    Mail::to($user->email)->send(new \App\Mail\OrderPlaced($order));
                    Log::info('Order confirmation email sent successfully', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_email' => $user->email,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send order confirmation email', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $emailErrors[] = "Failed to send confirmation email: " . $e->getMessage();
            }

            // Send vendor notifications
            try {
                $this->sendVendorNotifications($orders);
            } catch (\Exception $e) {
                Log::error('Failed to send vendor notifications', [
                    'error' => $e->getMessage(),
                ]);
                $emailErrors[] = "Vendor notifications failed: " . $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'orders' => collect($orders)->map(fn($o) => [
                    'id' => $o->id, 
                    'order_number' => $o->order_number
                ]),
                'redirect_url' => $redirectUrl,
                'email_errors' => !empty($emailErrors) ? $emailErrors : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create financial transactions for order
     */
    private function createFinancialTransactionsForOrder($order, $orderAmount)
    {
        $vendor = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';
        
        // Calculate all fees using FinanceCalculator
        $breakdown = $this->financeCalculator->calculateOrderBreakdown($order);
        
        // 1. Order Revenue Transaction (GROSS AMOUNT)
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
                'payment_method' => $order->payment_method,
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
                    'commission_type' => 'percentage',
                ],
            ]);
        }
        
        // 3. Gateway Fee Transaction
        if ($breakdown['gateway_fee'] > 0) {
            FinancialTransaction::create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'transaction_type' => FinancialTransaction::TYPE_GATEWAY_FEE,
                'amount' => $breakdown['gateway_fee'] * -1,
                'currency' => $currency,
                'description' => "Payment gateway fee for Order #{$order->order_number}",
                'reference_number' => $order->order_number . '-GATE',
                'gateway_fee' => $breakdown['gateway_fee'],
                'commission_fee' => 0,
                'wire_fee' => 0,
                'net_amount' => $breakdown['gateway_fee'] * -1,
                'status' => FinancialTransaction::STATUS_PENDING,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'payment_method' => $order->payment_method,
                    'gateway_fee_percentage' => $breakdown['breakdown']['gateway_fee_percentage'] . '%',
                ],
            ]);
        }
        
        Log::info('Financial transactions created for order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Update financial transaction status
     */
    private function updateFinancialTransactionStatus($order, $status, $description = null)
    {
        FinancialTransaction::where('order_id', $order->id)
            ->update([
                'status' => $status,
                'processed_at' => $status === FinancialTransaction::STATUS_PROCESSED ? now() : null,
            ]);
            
        if ($description) {
            Log::info($description, ['order_id' => $order->id]);
        }
    }

    /**
     * Handle OM payment financial transactions
     */
    private function handleOMPaymentFinancialTransactions($order, $amountPaid, $currency)
    {
        $breakdown = $this->financeCalculator->calculateOrderBreakdown($order);
        $totalAmount = $order->grand_total;
        
        $paymentPercentage = ($amountPaid / $totalAmount) * 100;
        
        $revenueTransaction = FinancialTransaction::where('order_id', $order->id)
            ->where('transaction_type', FinancialTransaction::TYPE_ORDER)
            ->first();
            
        if ($revenueTransaction) {
            if ($paymentPercentage < 100) {
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PENDING,
                    'description' => "Partial payment received for Order #{$order->order_number}",
                    'metadata' => array_merge($revenueTransaction->metadata ?? [], [
                        'partial_payment_amount' => $amountPaid,
                        'partial_payment_percentage' => $paymentPercentage . '%',
                    ]),
                ]);
            } else {
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);
                
                FinancialTransaction::where('order_id', $order->id)
                    ->whereIn('transaction_type', [
                        FinancialTransaction::TYPE_COMMISSION,
                        FinancialTransaction::TYPE_GATEWAY_FEE,
                    ])
                    ->update([
                        'status' => FinancialTransaction::STATUS_PROCESSED,
                        'processed_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Send vendor notifications
     */
    private function sendVendorNotifications($orders)
    {
        foreach ($orders as $order) {
            if ($order->vendor && $order->vendor->email) {
                try {
                    Mail::to($order->vendor->email)->send(new \App\Mail\VendorOrderNotification($order));
                    Log::info('Vendor notification sent', [
                        'order_id' => $order->id,
                        'vendor_id' => $order->vendor_id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send vendor notification', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function calculateShippingForOrder($request, $selectedItems)
    {
        $groupedItems = [];
        foreach ($selectedItems as $item) {
            $vendorId = $item['vendor_id'];
            if (!isset($groupedItems[$vendorId])) {
                $groupedItems[$vendorId] = [];
            }
            $groupedItems[$vendorId][] = [
                'product_id' => $item['product_id'],
                'vendor_product_id' => $item['vendor_product_id'],
                'quantity' => $item['quantity'],
            ];
        }

        $destinationAddress = [
            'first_name' => $request->address['first_name'],
            'last_name' => $request->address['last_name'],
            'city' => $request->address['city'],
            'state' => $request->address['state'],
            'zip_code' => $request->address['zip_code'] ?? null,
            'locality_id' => $request->address['locality_id'] ?? null,
            'country' => $request->address['country'],
            'country_code' => $this->getCountryCode($request->address['country']),
            'street_address' => $request->address['street_address'],
            'latitude' => $request->address['latitude'] ?? null,
            'longitude' => $request->address['longitude'] ?? null,
        ];

        return $this->shippingCalculator()->calculateCartShipping(
            $groupedItems,
            $destinationAddress,
            $request->shipping_carrier
        );
    }

    /**
     * Calculate full shipping with vendor details (same as calculateShipping)
     */
    /**
 * Calculate full shipping with vendor details (same as calculateShipping)
 */
    // calculateFullShipping() vit maintenant dans le trait
    // Concerns\CalculatesCheckoutShipping : ouvrir une négociation crée une
    // commande avec de vrais frais de port, et les deux chemins doivent les
    // calculer de la même façon.

    private function prepareStripeItems($items, $shippingUSD, $shippingName, $defaultRate)
    {
        $stripeItems = [];
        
        foreach ($items as $item) {
            $unitUsd = $item['unit_amount'] * ($item['rate_to_usd'] ?? $defaultRate);
            
            $productData = [
                'name' => $item['product_name'] ?? 'Product',
            ];
            
            if (!empty($item['image'])) {
                $productData['images'] = [url('storage/' . $item['image'])];
            }
            
            $stripeItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => intval(round($unitUsd * 100)),
                    'product_data' => $productData,
                ],
                'quantity' => $item['quantity'],
            ];
        }

        if ($shippingUSD > 0) {
            $stripeItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => intval(round($shippingUSD * 100)),
                    'product_data' => [
                        'name' => "Shipping - {$shippingName}",
                    ],
                ],
                'quantity' => 1,
            ];
        }

        return $stripeItems;
    }

    /**
     * Lines whose quantity exceeds what is actually on the shelf right now.
     *
     * Resolves each line exactly the way updateStock() does — variation stock when
     * the line names one, the vendor_product row otherwise — so the check and the
     * decrement can never disagree about which number they are looking at.
     *
     * lockForUpdate() holds the rows for the rest of the transaction: a second
     * checkout for the same last unit waits here instead of reading the same
     * pre-decrement value and passing too.
     *
     * @return list<array{name: string, requested: int, available: int}>
     */
    private function findStockShortfalls($items): array
    {
        $shortfalls = [];

        foreach ($items as $item) {
            $requested = (int) $item['quantity'];

            if (!empty($item['variation_id'])) {
                $available = \App\Models\VendorProductVariation::where('vendor_product_id', $item['vendor_product_id'])
                    ->where('id', $item['variation_id'])
                    ->lockForUpdate()
                    ->value('stock');
            } else {
                $available = \App\Models\VendorProduct::where('vendor_id', $item['vendor_id'])
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->value('stock');
            }

            // A line whose row has vanished is a broken cart entry, not a stock
            // question; leave it to the order-building code to fail loudly.
            if ($available === null) {
                continue;
            }

            if ($requested > (int) $available) {
                $shortfalls[] = [
                    'name' => $item['name'] ?? ($item['product_name'] ?? 'Article'),
                    'requested' => $requested,
                    'available' => (int) $available,
                ];
            }
        }

        return $shortfalls;
    }

    private function updateStock($items)
    {
        foreach ($items as $item) {
            // Check if this is a variation
            if (!empty($item['variation_id'])) {
                // Update variation stock
                $variation = \App\Models\VendorProductVariation::where('vendor_product_id', $item['vendor_product_id'])
                    ->where('id', $item['variation_id'])
                    ->first();
                
                if ($variation) {
                    $variation->stock = max(0, $variation->stock - $item['quantity']);
                    $variation->save();
                    
                    // Also update parent vendor product stock
                    $totalVariationStock = \App\Models\VendorProductVariation::where('vendor_product_id', $item['vendor_product_id'])
                        ->sum('stock');
                    
                    $vp = \App\Models\VendorProduct::find($item['vendor_product_id']);
                    if ($vp) {
                        $vp->stock = $totalVariationStock;
                        $vp->save();
                    }
                }
            } else {
                $vp = \App\Models\VendorProduct::where('vendor_id', $item['vendor_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();
                
                if ($vp) {
                    $vp->stock = max(0, $vp->stock - $item['quantity']);
                    $vp->save();
                }
            }
        }
    }

    // getCountryCode() vit maintenant dans le trait
    // Concerns\CalculatesCheckoutShipping, partagé avec NegotiationController.

    /**
     * Byte-for-byte the same method as CheckoutPage's copy. App\Support\Money
     * owns the rule now, so the web and the API can no longer drift apart.
     */
    private function convertUsdToVendorCurrency($usdAmount, $vendorRateToUsd)
    {
        return Money::fromUsd((float) $usdAmount, $vendorRateToUsd === null ? null : (float) $vendorRateToUsd);
    }
}