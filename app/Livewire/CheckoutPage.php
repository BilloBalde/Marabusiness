<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Paiement;
use App\Models\Vendor;
use App\Services\ShippingCalculator;
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

    // Address fields
    public $first_name;
    public $last_name;
    public $city;
    public $phone;
    public $street_address;
    public $state;
    public $zip_code;
    public $country = 'Guinea';
    public $selectedCurrency = '';
    
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
        $this->shippingCalculator = new ShippingCalculator();
        
        // Pre-fill user address if logged in
        $user = Auth::user();
        if ($user) {
            $this->first_name = $user->first_name ?? '';
            $this->last_name = $user->last_name ?? '';
            $this->phone = $user->phone ?? '';
            $this->city = $user->city ?? '';
            $this->state = $user->state ?? '';
            $this->country = $user->country ?? 'Guinea';
            
            // Try to get user's last address
            $lastAddress = Address::whereHas('order', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->latest()->first();
            
            if ($lastAddress) {
                $this->street_address = $lastAddress->street_address;
                $this->zip_code = $lastAddress->zip_code;
                $this->latitude = $lastAddress->latitude;
                $this->longitude = $lastAddress->longitude;
            }
        }
        
        // Validate cart
        $selectedItems = $this->getSelectedCartItems();
        if (empty($selectedItems)) {
            return redirect()->route('cart');
        }
    }

    // OR use a boot method to initialize
    public function boot()
    {
        $this->shippingCalculator = new ShippingCalculator();
    }

    // OR use a getter method
    public function getShippingCalculator()
    {
        if (!$this->shippingCalculator) {
            $this->shippingCalculator = new ShippingCalculator();
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
            'zip_code' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);
        
        $this->calculatingShipping = true;
        
        $destinationAddress = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
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
            
            $this->availableCarriers = $shippingResult['carriers'] ?? [];
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
            'zip_code'   => 'required|string|max:255',
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
                
                // Create order
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'grand_total' => $vendorTotal,
                    'total_remaining' => $vendorTotal,
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
                    'order_id' => $order->id,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'city' => $this->city,
                    'phone' => $this->phone,
                    'street_address' => $this->street_address,
                    'state' => $this->state,
                    'zip_code' => $this->zip_code,
                    'country' => $this->country,
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
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
                    
                    Stripe::setApiKey(env('STRIPE_SECRET'));
                    
                    $session = Session::create([
                        'payment_method_types' => ['card'],
                        'customer_email' => $user->email,
                        'line_items' => $stripeItems,
                        'mode' => 'payment',
                        'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => route('cancel'),
                    ]);
                    
                    $redirect_url = $session->url;
                    $order->update([
                        'total_paid' => $vendorTotal,
                        'total_remaining' => 0,
                        'payment_status' => 'paid',
                    ]);
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
                    
                    // For a NEW order at checkout, this is the first payment
                    $newPaid = $pay->amount;
                    $remaining = max(0, $vendorTotal - $newPaid);
                    
                    $paymentStatus = 'pending';
                    if ($newPaid >= $vendorTotal) {
                        $paymentStatus = 'paid';
                    } elseif ($newPaid > 0) {
                        $paymentStatus = 'partial';
                    }
                    
                    $order->update([
                        'total_paid' => $newPaid,
                        'total_remaining' => $remaining,
                        'payment_status' => $paymentStatus,
                    ]);
                    
                    $redirect_url = route('success');
                } else {
                    // Cash or COD
                    $order->update([
                        'payment_status' => 'pending',
                    ]);
                    $redirect_url = route('success');
                }
                
                /** UPDATE STOCK */
                foreach ($group['items'] as $item) {
                    $vp = \App\Models\VendorProduct::where('vendor_id', $vendorId)
                        ->where('product_id', $item['product_id'])
                        ->first();
                    
                    if ($vp) {
                        $vp->stock = max(0, $vp->stock - $item['quantity']);
                        $vp->save();
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
        if (in_array($property, ['first_name', 'last_name', 'city', 'state', 'zip_code', 'country', 'latitude', 'longitude'])) {
            if ($this->first_name && $this->last_name && $this->city && $this->country) {
                $this->calculateShipping();
            }
        }
    }
    
    public function updatedShippingCarrier($value)
    {
        $this->shipping_carrier = $value;
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
        ]);
    }
}