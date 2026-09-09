<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Paiement;
use App\Models\Vendor;
use App\Models\FinancialTransaction; // Add this
use App\Services\Shipping\CartShippingResolver;
use App\Support\ShippingCarrierFilter;
use App\Services\FinanceCalculator; // Add this
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use Stripe\Checkout\Session;
use Stripe\Stripe;

#[Title('Checkout Page - MARA BUSINESS')]
class CheckoutPage extends Component
{
    use WithFileUploads;

    private $financeCalculator;

    // Address fields
    public $first_name;
    public $last_name;
    public $city;
    public $phone;
    public $street_address;
    public $state;
    public $zip_code;
    // Replaces the postal code for vendors priced by locality.
    public $locality_id = null;
    public $country = 'Guinea';
    public $selectedCurrency = '';

    // Saved addresses
    public $savedAddresses = [];
    public $selected_address_id = null;
    public $save_address = true; // optional: save entered address to profile

    
    // Geolocation fields
    public $latitude;
    public $longitude;
    
    // Payment and shipping
    public $payment_method;
    public $shipping_carrier = 'local';
    public $image;
    public $amount;
    public $selected_ids = [];
    
    // Shipping calculator - remove type declaration or initialize in constructor
    private $shippingCalculator;
    
    // Shipping results cache
    public $shippingResults = [];
    public $availableCarriers = [];
    public $grandTotalUSD = 0;
    
    // Loading states
    public $calculatingShipping = false;
    public $placingOrder = false;

    public function mount()
    {
        $selected = request()->query('selected');
        
        if ($selected) {
            $this->selected_ids = array_filter(explode(',', $selected));
        }
        
        // Initialize shipping calculator
        $this->shippingCalculator = new CartShippingResolver();
        //$this->financeCalculator = new FinanceCalculator();
        
        // Pre-fill user address if logged in
        $user = Auth::user();
        if ($user) {

            $this->savedAddresses = Address::where('user_id', $user->id)
            ->whereNull('order_id')
            ->orderByDesc('is_default')
            ->latest()
            ->get()
            ->toArray();

            // auto-select default / first address
            $default = Address::where('user_id', $user->id)
                ->whereNull('order_id')
                ->orderByDesc('is_default')
                ->first();
            if ($default) {
                $this->applyAddress($default->id);
            } else {
                // fallback: prefill from user profile
                $this->first_name = $user->first_name ?? '';
                $this->last_name  = $user->last_name ?? '';
                $this->phone      = $user->phone ?? '';
                $this->city       = $user->city ?? '';
                $this->state      = $user->state ?? '';
                $this->country    = $user->country ?? 'Guinea';
            }
        }
        
        // Validate cart
        $selectedItems = $this->getSelectedCartItems();
        if (empty($selectedItems)) {
            return redirect()->route('cart');
        }
    }

    public function applyAddress(int $addressId): void
    {
        $address = Address::where('user_id', Auth::id())
            ->whereNull('order_id')
            ->findOrFail($addressId);

        $this->selected_address_id = $address->id;

        $this->first_name      = $address->first_name;
        $this->last_name       = $address->last_name;
        $this->phone           = $address->phone;
        $this->street_address  = $address->street_address;
        $this->city            = $address->city;
        $this->state           = $address->state;
        $this->zip_code        = $address->zip_code;
        $this->locality_id     = $address->locality_id;
        $this->country         = $address->country ?? 'Guinea';
        $this->latitude        = $address->latitude;
        $this->longitude       = $address->longitude;

        // Shipping depends on destination -> recalc
        $this->calculateShipping();
    }

    public function clearSelectedAddress(): void
    {
        $this->selected_address_id = null;
    }

    protected function getFinanceCalculator()
    {
        if (!$this->financeCalculator) {
            $this->financeCalculator = new FinanceCalculator();
        }
        return $this->financeCalculator;
    }

    // OR use a boot method to initialize
    public function boot()
    {
        $this->shippingCalculator = new CartShippingResolver();
    }

    // OR use a getter method
    public function getShippingCalculator()
    {
        if (!$this->shippingCalculator) {
            $this->shippingCalculator = new CartShippingResolver();
        }
        return $this->shippingCalculator;
    }

    protected function getSelectedCartItems(): array
    {
        $cart = CartManagement::getCartItemsFromCookie();

        if (empty($this->selected_ids)) {
            return [];
        }

        // Filter by vendor_product_id
        return array_filter($cart, fn ($item) => in_array($item['vendor_product_id'], $this->selected_ids));
    }

    public function getGroupedCartItems()
    {
        $cart = $this->getSelectedCartItems();

        if (empty($cart)) {
            return collect();
        }

        return collect($cart)->groupBy('vendor_id')->map(function ($items) {
            $vendor = Vendor::with('currency')->find($items->first()['vendor_id']);

            return [
                'vendor'       => $vendor,
                'currency'     => strtoupper(trim($vendor?->currency?->code ?? 'USD')),
                'rate_to_usd'  => $vendor?->currency?->rate_to_usd ?? 1,
                'items'        => $items,
                'subtotal'     => $items->sum('total_amount'),
            ];
        });
    }
    
    /** -------------------------------------------------------------
     *   Calculate shipping based on address and items
     * ------------------------------------------------------------- */
    public function calculateShipping()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
        ]);
        
        $this->calculatingShipping = true;
        
        $destinationAddress = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'locality_id' => $this->locality_id,
            'country' => $this->country,
            'country_code' => $this->getCountryCode($this->country),
            'street_address' => $this->street_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
        
        // FIX: Transform the grouped items into the format ShippingCalculator expects
        $groupedItems = [];
        
        foreach ($this->getGroupedCartItems() as $vendorId => $group) {
            // Extract just the items array for each vendor
            $items = [];
            
            foreach ($group['items'] as $item) {
                // Make sure we have product_id in the item
                $items[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'vendor_product_id' => $item['vendor_product_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    // Add any other fields that ShippingCalculator might need
                ];
            }
            
            $groupedItems[$vendorId] = $items;
        }
        
        try {
            // Calculate shipping using the service
            $calculator = $this->getShippingCalculator();
            $shippingResult = $calculator->calculateCartShipping(
                $groupedItems,  // Now this is in the correct format
                $destinationAddress,
                $this->shipping_carrier
            );
            
            // Only "Local Courier" is offered: DHL/UPS/FedEx/Chronopost/CMA never
            // modelled a real air/sea distinction, they just carried different
            // constants in the same formula.
            $this->availableCarriers = ShippingCarrierFilter::onlyLocal($shippingResult['carriers'] ?? []);
            $this->shippingResults = $shippingResult;
            
            // Auto-select cheapest carrier if current carrier not available
            if (!empty($this->availableCarriers)) {
                if (!isset($this->availableCarriers[$this->shipping_carrier])) {
                    reset($this->availableCarriers);
                    $this->shipping_carrier = key($this->availableCarriers);
                }
            } else {
                // No carriers available, fallback to local
                $this->shipping_carrier = 'local';
                $this->availableCarriers['local'] = [
                    'name' => 'Local Delivery',
                    'carrier' => 'local',
                    'total_cost_usd' => 0,
                    'vendors' => [],
                    'is_available' => true,
                ];
            }
            
            session()->flash('shipping-success', 'Shipping costs calculated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Shipping calculation error: ' . $e->getMessage());
            session()->flash('shipping-error', 'Error calculating shipping: ' . $e->getMessage());
            
            // Fallback to basic shipping
            $this->availableCarriers = [
                'local' => [
                    'name' => 'Local Delivery',
                    'carrier' => 'local',
                    'total_cost_usd' => 0,
                    'vendors' => [],
                    'is_available' => true,
                ]
            ];
        } finally {
            $this->calculatingShipping = false;
        }
    }
    
    /** -------------------------------------------------------------
     *   Helper to get country code
     * ------------------------------------------------------------- */
    private function getCountryCode(string $country): string
    {
        $countryCodes = [
            'Guinea' => 'GN',
            'Senegal' => 'SN',
            'Ivory Coast' => 'CI',
            'Mali' => 'ML',
            'France' => 'FR',
            'United States' => 'US',
            'Canada' => 'CA',
            'United Kingdom' => 'GB',
        ];
        
        return $countryCodes[$country] ?? strtoupper(substr($country, 0, 2));
    }
    
    /** -------------------------------------------------------------
     *   Get shipping cost for selected carrier
     * ------------------------------------------------------------- */
    public function getSelectedShipping(): array
    {
        $selectedCarrier = $this->availableCarriers[$this->shipping_carrier] ?? null;
        
        if (!$selectedCarrier && !empty($this->availableCarriers)) {
            reset($this->availableCarriers);
            $this->shipping_carrier = key($this->availableCarriers);
            $selectedCarrier = $this->availableCarriers[$this->shipping_carrier];
        }
        
        return $selectedCarrier ?? [
            'name' => 'Local Delivery',
            'carrier' => 'local',
            'total_cost_usd' => 0,
            'description' => 'Standard delivery',
            'vendors' => [],
        ];
    }
    
    /** -------------------------------------------------------------
     *   Convert USD to vendor currency
     * ------------------------------------------------------------- */
    protected function convertUsdToVendorCurrency($usdAmount, $vendorRateToUsd)
    {
        if ($vendorRateToUsd <= 0) {
            return $usdAmount;
        }
        return $usdAmount / $vendorRateToUsd;
    }

    /** -------------------------------------------------------------
     *   PLACE ORDER
     * ------------------------------------------------------------- */
    public function placeOrder()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'city'       => 'required|string|max:255',
            'phone'      => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'state'      => 'required|string|max:255',
            'zip_code'   => 'nullable|string|max:255',
            'country'    => 'required|string|max:255',
            'payment_method' => 'required|string|in:cash,stripe,cod,om',
            'shipping_carrier' => 'required|string|max:50',
            'amount'     => $this->payment_method === 'om' ? 'required|numeric|min:1' : 'nullable',
            'image'      => $this->payment_method === 'om' ? 'required|image|max:4096' : 'nullable',
            'latitude'   => 'nullable|numeric|between:-90,90',
            'longitude'  => 'nullable|numeric|between:-180,180',
        ]);

        $this->placingOrder = true;

        try {
            $groups = $this->getGroupedCartItems();
            
            if ($groups->isEmpty()) {
                session()->flash('error', 'Your cart is empty.');
                return redirect()->route('cart');
            }
            
            // Get selected shipping costs
            $selectedShipping = $this->getSelectedShipping();
            $shippingBreakdown = $selectedShipping['vendors'] ?? [];
            
            $user = Auth::user();
            $redirect_url = '';
            $orders = [];
            $sourceAddress = null;

            if ($this->selected_address_id) {
                $sourceAddress = Address::where('user_id', $user->id)
                    ->whereNull('order_id')
                    ->find($this->selected_address_id);
            } elseif ($this->save_address) {
                // Copy the typed address into the profile, once per checkout. The per-order
                // shipping addresses created below carry an order_id and stay out of the book.
                $isFirstSavedAddress = ! Address::where('user_id', $user->id)
                    ->whereNull('order_id')
                    ->exists();

                Address::create([
                    'user_id'        => $user->id,
                    'first_name'     => $this->first_name,
                    'last_name'      => $this->last_name,
                    'phone'          => $this->phone,
                    'street_address' => $this->street_address,
                    'city'           => $this->city,
                    'state'          => $this->state,
                    'zip_code'       => $this->zip_code,
                    'locality_id'    => $this->locality_id,
                    'country'        => $this->country,
                    'latitude'       => $this->latitude,
                    'longitude'      => $this->longitude,
                    'is_default'     => $isFirstSavedAddress,
                ]);
            }
            
            /** Create orders for each vendor */
            foreach ($groups as $vendorId => $group) {
                $vendor = $group['vendor'];
                $rate = $group['rate_to_usd'];
                $currency = $group['currency'];
                
                // Get shipping cost for this vendor
                $vendorShippingUSD = $shippingBreakdown[$vendorId]['cost'] ?? 0;
                $vendorShippingLocal = $this->convertUsdToVendorCurrency($vendorShippingUSD, $rate);
                
                $vendorSubtotal = $group['subtotal'];
                $vendorTotal = $vendorSubtotal + $vendorShippingLocal;

                $vendorSubtotalUSD = $vendorSubtotal * $rate;
                $vendorTotalUSD    = $vendorSubtotalUSD + $vendorShippingUSD;
                
                //dd($vendorSubtotal, $vendorShippingLocal, $vendorTotal, $vendorSubtotalUSD, $vendorShippingUSD, $vendorTotalUSD);
                // Create order
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'grand_total' => $vendorTotal,
                    'total_remaining' => $vendorTotal,
                    'grand_total_usd' => $vendorTotalUSD,
                    'shipping_amount_usd' => $vendorShippingUSD,
                    'rate_to_usd' => $rate,
                    'payment_method' => $this->payment_method,
                    'payment_status' => 'pending',
                    'status' => 'new',
                    'shipping_carrier' => $this->shipping_carrier,
                    'shipping_amount' => $vendorShippingLocal,
                    'notes' => "Marketplace order — Vendor: {$vendor->store_name}",
                    'currency_id' => $vendor->currency_id ?? null,
                ]);

                $orders[] = $order;
                
                /** CREATE ADDRESS WITH ZONE */
                $addressData = [
                    'order_id'        => $order->id,
                    'user_id'         => $user->id, // if your table has it
                    'first_name'      => $sourceAddress?->first_name ?? $this->first_name,
                    'last_name'       => $sourceAddress?->last_name ?? $this->last_name,
                    'city'            => $sourceAddress?->city ?? $this->city,
                    'phone'           => $sourceAddress?->phone ?? $this->phone,
                    'street_address'  => $sourceAddress?->street_address ?? $this->street_address,
                    'state'           => $sourceAddress?->state ?? $this->state,
                    'zip_code'        => $sourceAddress?->zip_code ?? $this->zip_code,
                    'locality_id'     => $sourceAddress?->locality_id ?? $this->locality_id,
                    'country'         => $sourceAddress?->country ?? $this->country,
                    'latitude'        => $sourceAddress?->latitude ?? $this->latitude,
                    'longitude'       => $sourceAddress?->longitude ?? $this->longitude,
                ];

                
                // Add zone if available from shipping calculation
                if (isset($shippingBreakdown[$vendorId]['zone'])) {
                    $addressData['zone'] = $shippingBreakdown[$vendorId]['zone'];
                }
                
                Address::create($addressData);
                
                /** SAVE ORDER ITEMS WITH VARIATIONS */
                foreach ($group['items'] as $item) {
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

                $this->createFinancialTransactionsForOrder($order, $vendorTotal);
                
                /** PAYMENT HANDLING */
                if ($this->payment_method === 'stripe') {
                    $stripeItems = [];
                    foreach ($group['items'] as $item) {
                        $itemRate = $item['rate_to_usd'] ?? $rate;
                        $unitUsd = $item['unit_amount'] * $itemRate;
                        
                        $stripeItems[] = [
                            'price_data' => [
                                'currency' => 'usd',
                                'unit_amount' => intval(round($unitUsd * 100)),
                                'product_data' => [
                                    'name' => $item['product_name'] ?? 'Product',
                                ],
                            ],
                            'quantity' => $item['quantity'],
                        ];
                    }
                    
                    // Add shipping as a line item if > 0
                    if ($vendorShippingUSD > 0) {
                        $stripeItems[] = [
                            'price_data' => [
                                'currency' => 'usd',
                                'unit_amount' => intval(round($vendorShippingUSD * 100)),
                                'product_data' => [
                                    'name' => "Shipping - {$selectedShipping['name']}",
                                ],
                            ],
                            'quantity' => 1,
                        ];
                    }
                    // Calculate total USD amount for Stripe
                    $totalUsdAmount = 0;
                    foreach ($group['items'] as $item) {
                        $itemRate = $item['rate_to_usd'] ?? $rate;
                        $totalUsdAmount += ($item['unit_amount'] * $itemRate) * $item['quantity'];
                    }
                    $totalUsdAmount += $vendorShippingUSD;
                    
                    $stripeAmount = intval(round($totalUsdAmount * 100));
                    
                    // Validate Stripe amount limits
                    if ($stripeAmount <= 0) {
                        $this->addError('amount', 'Invalid payment amount.');
                        $this->placingOrder = false;
                        return;
                    }
                    
                    if ($stripeAmount > 99999999) {
                        $this->addError('amount', 'Payment amount exceeds Stripe limit. Please contact support.');
                        $this->placingOrder = false;
                        return;
                    }
                    Stripe::setApiKey(config('services.stripe.secret'));

                    try {
                        $session = Session::create([
                            'payment_method_types' => ['card'],
                            'customer_email' => $user->email,
                            'line_items' => $stripeItems,
                            'mode' => 'payment',
                            'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id,
                            'cancel_url' => url('/checkout'),
                            'metadata' => [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'user_id' => $user->id,
                                'vendor_id' => $vendorId,
                                'total_usd' => $totalUsdAmount,
                            ],
                        ]);
                        
                        Log::info('Stripe session created for checkout', [
                            'session_id' => $session->id,
                            'order_id' => $order->id,
                            'user_id' => $user->id,
                            'total_usd' => $totalUsdAmount,
                        ]);
                        // Save Stripe session ID to order but DON'T create payment record
                        $order->update([
                            'stripe_session_id' => $session->id,
                            // DO NOT update total_paid or total_remaining here
                            // DO NOT create a Paiement record here
                        ]);
                        
                        // Update financial transaction status
                        $this->updateFinancialTransactionStatus($order, 'pending', 'Stripe payment initiated');
                        
                        // Set redirect URL to Stripe Checkout
                        $redirect_url = $session->url;
                        
                    } catch (\Stripe\Exception\InvalidRequestException $e) {
                        Log::error('Stripe session creation failed', [
                            'error' => $e->getMessage(),
                            'order_id' => $order->id,
                        ]);
                        
                        $this->addError('payment_method', 'Payment gateway error: ' . $e->getMessage());
                        $this->placingOrder = false;
                        return;
                    }

                } elseif ($this->payment_method === 'om') {
                    //$vendorGroup = reset($this->groups);
                    //$exactAmount = $vendorGroup['total'];
                    //dd(number_format($this->amount, 2));
                    
                    if (number_format($this->amount, 2) != number_format($vendorTotal, 2)) {
                        $this->addError('amount', 
                            "Orange Money payment must be exactly " . 
                            number_format($vendorTotal, 2) . 
                            " GNF"
                        );
                        return;
                    }
                    $pay = Paiement::create([
                        'order_id' => $order->id,
                        'amount' => $this->amount,
                        'image' => $this->image ? $this->image->store('payments', 'public_uploads') : null,
                        'payment_method' => 'om',
                        'currency' => $currency,
                        'payment_status' => $this->amount >= $vendorTotal ? 'paid' : 'partial',
                        'transaction_id' => Order::generateTransactionNumber(),
                    ]);
                    
                    // An Orange Money transfer the buyer says they made. It is recorded,
                    // but the order stays unpaid until the vendor confirms it arrived —
                    // syncPaymentTotals() only counts confirmed money.
                    $order->syncPaymentTotals();

                    $this->handleOMPaymentFinancialTransactions($order, $this->amount, $currency);
                    
                    $redirect_url = route('success');
                } else {
                    // Cash or COD
                    $order->update([
                        'payment_status' => 'pending',
                    ]);
                    $this->updateFinancialTransactionStatus($order, 'pending', 'Cash/COD payment pending');
                    $redirect_url = route('success');
                }
                
                /** UPDATE STOCK */
                foreach ($group['items'] as $item) {
                    // Check if this is a variation
                    if (!empty($item['variation_id'])) {
                        // Update variation stock
                        $variation = \App\Models\VendorProductVariation::where('vendor_product_id', $item['vendor_product_id'])
                            ->where('id', $item['variation_id'])
                            ->first();
                        
                        if ($variation) {
                            $variation->stock = max(0, $variation->stock - $item['quantity']);
                            $variation->save();
                            
                            // Also update parent vendor product stock (sum of all variations)
                            $totalVariationStock = \App\Models\VendorProductVariation::where('vendor_product_id', $item['vendor_product_id'])
                                ->sum('stock');
                            
                            $vp = \App\Models\VendorProduct::find($item['vendor_product_id']);
                            if ($vp) {
                                $vp->stock = $totalVariationStock;
                                $vp->save();
                            }
                        }
                    } else {
                        $vp = \App\Models\VendorProduct::where('vendor_id', $vendorId)
                            ->where('product_id', $item['product_id'])
                            ->first();
                        
                        if ($vp) {
                            $vp->stock = max(0, $vp->stock - $item['quantity']);
                            $vp->save();
                        }
                    }
                }
            }
            
            /** SEND ORDER CONFIRMATION EMAILS */
            /* foreach ($orders as $order) {
                Mail::to($user->email)->send(new \App\Mail\OrderPlaced($order));
            } */
            $emailErrors = [];
            foreach ($orders as $order) {
                try {
                    Mail::to($user->email)->send(new \App\Mail\OrderPlaced($order));
                    Log::info('Order confirmation email sent successfully', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_email' => $user->email,
                    ]);
                } catch (\Exception $e) {
                    // Log the error but don't stop the order process
                    Log::error('Failed to send order confirmation email', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_email' => $user->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    $emailErrors[] = "Failed to send confirmation email for Order #{$order->order_number}: " . $e->getMessage();
                }
            }

            try {
                $this->sendVendorNotifications($orders);
            } catch (\Exception $e) {
                Log::error('Failed to send vendor notifications', [
                    'error' => $e->getMessage(),
                ]);
                $emailErrors[] = "Vendor notifications failed: " . $e->getMessage();
            }
            /** CLEAR SELECTED ITEMS FROM CART */
            if (!empty($this->selected_ids)) {
                foreach ($this->selected_ids as $vendorProductId) {
                    // Find and remove cart items by vendor_product_id
                    $cartItems = CartManagement::getCartItemsFromCookie();
                    foreach ($cartItems as $key => $item) {
                        if ($item['vendor_product_id'] == $vendorProductId) {
                            CartManagement::removeCartItem($item['cart_key']);
                        }
                    }
                }
            }

            // Store email errors in session to show to user if any
            if (!empty($emailErrors)) {
                session()->flash('email-errors', $emailErrors);
            }
            
            $this->placingOrder = false;
            
            return redirect($redirect_url ?? route('success'));
            
        } catch (\Exception $e) {
            $this->placingOrder = false;
            Log::error('Checkout Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            session()->flash('error', 'Checkout failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /** -------------------------------------------------------------
     *   CREATE FINANCIAL TRANSACTIONS FOR NEW ORDER
     * ------------------------------------------------------------- */
    private function createFinancialTransactionsForOrder($order, $orderAmount)
    {
        $financeCalculator = $this->getFinanceCalculator(); // USE GETTER HERE
        $vendor = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';
        
        // Calculate all fees using FinanceCalculator
        $breakdown = $financeCalculator->calculateOrderBreakdown($order);
        
        // 1. Order Revenue Transaction (GROSS AMOUNT)
        FinancialTransaction::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'transaction_type' => FinancialTransaction::TYPE_ORDER,
            'amount' => $breakdown['gross_amount'],
            'currency' => $currency,
            'description' => "Order #{$order->order_number} - Gross revenue",
            'reference_number' => $order->order_number . '-REV',
            'gateway_fee' => 0, // Will be calculated separately
            'commission_fee' => 0, // Will be calculated separately
            'wire_fee' => 0, // Will be calculated separately
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
                'amount' => $breakdown['commission'] * -1, // Negative amount (deduction)
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
        
        // 3. Gateway Fee Transaction (if any)
        if ($breakdown['gateway_fee'] > 0) {
            FinancialTransaction::create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'transaction_type' => FinancialTransaction::TYPE_GATEWAY_FEE,
                'amount' => $breakdown['gateway_fee'] * -1, // Negative amount (deduction)
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
        
        // 4. Wire Fee Transaction (if any - this will be calculated at payout time)
        // We'll create this when payout is initiated
        
        Log::info('Financial transactions created for order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'gross_amount' => $breakdown['gross_amount'],
            'commission' => $breakdown['commission'],
            'gateway_fee' => $breakdown['gateway_fee'],
        ]);
    }
    
    /** -------------------------------------------------------------
     *   UPDATE FINANCIAL TRANSACTION STATUS
     * ------------------------------------------------------------- */
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
    
    /** -------------------------------------------------------------
     *   HANDLE OM PAYMENT FINANCIAL TRANSACTIONS
     * ------------------------------------------------------------- */
    private function handleOMPaymentFinancialTransactions($order, $amountPaid, $currency)
    {
        $breakdown = $this->financeCalculator->calculateOrderBreakdown($order);
        $totalAmount = $order->grand_total;
        
        // Calculate percentages
        $paymentPercentage = ($amountPaid / $totalAmount) * 100;
        
        // Update order revenue transaction based on payment
        $revenueTransaction = FinancialTransaction::where('order_id', $order->id)
            ->where('transaction_type', FinancialTransaction::TYPE_ORDER)
            ->first();
            
        if ($revenueTransaction) {
            // If partial payment, adjust the transaction
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
                // Full payment
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
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
            }
        }
    }
    
    /** -------------------------------------------------------------
     *   Send notifications to vendors (optional)
     * ------------------------------------------------------------- */
    private function sendVendorNotifications($orders)
    {
        foreach ($orders as $order) {
            if ($order->vendor && $order->vendor->email) {
                try {
                    Mail::to($order->vendor->email)->send(new \App\Mail\VendorOrderNotification($order));
                    Log::info('Vendor notification sent', [
                        'order_id' => $order->id,
                        'vendor_id' => $order->vendor_id,
                        'vendor_email' => $order->vendor->email,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send vendor notification', [
                        'order_id' => $order->id,
                        'vendor_id' => $order->vendor_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
    /** -------------------------------------------------------------
     *   Get geolocation
     * ------------------------------------------------------------- */
    public function getLocation()
    {
        $this->dispatch('get-browser-location');
    }
    
    public function setLocation($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        
        // Recalculate shipping when location changes
        if ($this->first_name && $this->last_name && $this->city) {
            $this->calculateShipping();
        }
    }
    
    /** -------------------------------------------------------------
     *   Recalculate shipping when address changes
     * ------------------------------------------------------------- */
    public function updated($property)
    {
        // Recalculate shipping when address fields are filled
        if (in_array($property, ['first_name', 'last_name', 'city', 'state', 'zip_code', 'locality_id', 'country', 'latitude', 'longitude'])) {
            if ($this->first_name && $this->last_name && $this->city && $this->country) {
                $this->calculateShipping();
            }
        }
    }
    
    /**
     * A locality only makes sense within the country it was picked for. Without this,
     * switching from Guinea to Senegal after choosing "Ratoma" would silently keep
     * Ratoma selected — a Guinean commune priced (or not) as if it were Senegalese.
     */
    public function updatedCountry(): void
    {
        $this->locality_id = null;
    }

    public function updatedShippingCarrier($value)
    {
        $this->shipping_carrier = $value;
    }

    /**
     * Localities the buyer can pick, labelled "Commune (Préfecture)" — scoped to the
     * country already chosen in the address form above, so a Guinean commune never
     * shows up as an option while shipping to Senegal or vice versa. Delivery zone
     * pricing is global (see App\Models\Locality::TYPE_COUNTRY), but only Guinea has
     * cities seeded today — another country legitimately returns an empty list here,
     * handled by needsLocalityPicker() rather than treated as an error.
     */
    public function localityOptions(): array
    {
        $country = \App\Models\Locality::where('type', \App\Models\Locality::TYPE_COUNTRY)
            ->where('country_code', $this->getCountryCode($this->country))
            ->first();

        if (! $country) {
            return [];
        }

        return \App\Models\Locality::selectable()
            ->where(function ($query) use ($country) {
                // Two hops from the country covers Guinea's shape (country > region >
                // commune/prefecture); a country with cities attached one level up is
                // already covered by the direct-children branch.
                $regionIds = \App\Models\Locality::where('parent_id', $country->id)->pluck('id');

                $query->where('parent_id', $country->id)
                    ->orWhereIn('parent_id', $regionIds);
            })
            ->with('parent')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($locality) => [$locality->id => $locality->full_name])
            ->all();
    }

    /**
     * The locality field only matters when at least one vendor of the cart prices
     * by locality; otherwise the buyer is not asked for it.
     */
    protected function needsLocality($groups): bool
    {
        foreach ($groups as $group) {
            if ($group['vendor']?->usesLocalityShipping()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shipping by locality is priced marketplace-wide, but only Guinea has cities
     * seeded so far. A buyer shipping to a country with no localities yet is not
     * forced through a required field they cannot fill — the vendor's own
     * default_shipping_amount already covers this case in the calculator.
     */
    protected function hasLocalitiesForCountry(): bool
    {
        return $this->localityOptions() !== [];
    }

    /**
     * When every vendor prices by locality there is nothing to choose: the carrier
     * selector is hidden and the single computed amount applies.
     */
    protected function allVendorsUseLocality($groups): bool
    {
        if (count($groups) === 0) {
            return false;
        }

        foreach ($groups as $group) {
            if (! $group['vendor']?->usesLocalityShipping()) {
                return false;
            }
        }

        return true;
    }

    public function render()
    {
        $selectedItems = $this->getSelectedCartItems();
        
        if (empty($selectedItems)) {
            return redirect()->route('cart');
        }
        
        $groups = $this->getGroupedCartItems();
        $selectedShipping = $this->getSelectedShipping();

        // Check if multiple vendors
        $hasMultipleVendors = count($groups) > 1;
        
        // If multiple vendors, force COD payment method
        if ($hasMultipleVendors && $this->payment_method !== 'cod') {
            $this->payment_method = 'cod';
        }
        
        // Calculate totals for display
        $displayGroups = [];
        $grandTotalUSD = 0;
        $totalShippingUSD = $selectedShipping['total_cost_usd'] ?? 0;
        $subtotalUSD = 0;
        
        foreach ($groups as $vendorId => $group) {
            $this->selectedCurrency = $group['currency'];
            $rate = $group['rate_to_usd'];
            Log::info('Item info:', [
                'group' => $group,
            ]);
            //dd($rate);
            $subtotal = $group['subtotal'];
            $subtotalUSD += $subtotal * $rate;
            
            // Get shipping for this vendor
            $vendorShippingUSD = $selectedShipping['vendors'][$vendorId]['cost'] ?? 0;
            $vendorShippingLocal = $this->convertUsdToVendorCurrency($vendorShippingUSD, $rate);
            
            $vendorTotalLocal = $subtotal + $vendorShippingLocal;
            $vendorTotalUSD = ($subtotal * $rate) + $vendorShippingUSD;
            
            $grandTotalUSD += $vendorTotalUSD;
            
            $displayGroups[$vendorId] = [
                'vendor' => $group['vendor'],
                'currency' => $group['currency'],
                'items' => $group['items'],
                'subtotal' => $subtotal,
                'subtotal_usd' => $subtotal * $rate,
                'shipping' => $vendorShippingLocal,
                'shipping_usd' => $vendorShippingUSD,
                'total' => $vendorTotalLocal,
                'total_usd' => $vendorTotalUSD,
                'rate_to_usd' => $rate,
                'zone' => $selectedShipping['vendors'][$vendorId]['zone'] ?? 'Unknown',
                'weight' => $selectedShipping['vendors'][$vendorId]['total_weight'] ?? 0,
                'cbm' => $selectedShipping['vendors'][$vendorId]['total_cbm'] ?? 0,
                'items_count' => $selectedShipping['vendors'][$vendorId]['total_items'] ?? 0,
                'delivery_days' => $selectedShipping['vendors'][$vendorId]['delivery_days'] ?? 3,
            ];
        }
        
        return view('livewire.checkout-page', [
            'groups' => $displayGroups,
            'subtotal_usd' => $subtotalUSD,
            'grand_total_usd' => $grandTotalUSD,
            'total_shipping_usd' => $totalShippingUSD,
            'availableCarriers' => $this->availableCarriers,
            'selectedCarrier' => $selectedShipping,
            'selected_count' => count($selectedItems),
            'has_location' => !empty($this->latitude) && !empty($this->longitude),
            'has_shipping_calculated' => !empty($this->availableCarriers),
            'calculatingShipping' => $this->calculatingShipping,
            'placingOrder' => $this->placingOrder,
            'has_multiple_vendors' => $hasMultipleVendors, // Add this
            'selectedCurrency' => $this->selectedCurrency,
            'localities' => $this->localityOptions(),
            'needs_locality' => $this->needsLocality($groups),
            'has_localities_for_country' => $this->hasLocalitiesForCountry(),
            'carrier_choice_applies' => ! $this->allVendorsUseLocality($groups),
        ]);
    }
}