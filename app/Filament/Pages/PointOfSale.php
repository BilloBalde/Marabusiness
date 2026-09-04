<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Models\VendorProductWholesale;
use App\Models\Address;
use App\Services\ShippingCalculator;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function canAccess(): bool
    {
        // Hide from everyone
        return false;
        
        // OR: Hide from specific roles but allow others
        // $user = Auth::user();
        // return $user && $user->hasRole('pos-operator'); // Only specific role
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public static function getNavigationGroup(): ?string
    {
        return static::canAccess() ? __('filament.groups.sales') : null;
    }
    
    public static function getNavigationLabel(): string
    {
        return static::canAccess() ? __('filament.nav.pos') : '';
    }

    public function getHeading(): string
    {
        return __('filament.nav.pos');
    }

    /* public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.sales');
    } */

    /* public static function getNavigationLabel(): string
    {
        //return __('filament.nav.pos');
        return '';
    } */
    
    protected static ?string $navigationTarget = '_blank';
    protected static string $view = 'filament.pages.point-of-sale';
    public bool $showCreateCustomerModal = false;

    public string $customerName = '';
    public string $customerEmail = '';
    public string $customerPhone = '';

    // Address fields for shipping
    public string $first_name = '';
    public string $last_name = '';
    public string $city = '';
    public string $phone = '';
    public string $street_address = '';
    public string $state = '';
    public string $zip_code = '';
    public string $country = 'Guinea';
    public ?float $latitude = null;
    public ?float $longitude = null;

    public ?int $vendorId = null;
    public ?int $customerId = null;
    public string $notes = '';
    public string $productSearch = '';
    public ?string $categoryFilter = null;
    public string $paymentMethod = 'cash';
    public string $shippingCarrier = 'local';
    public float $advancePaid = 0;
    public array $cart = [];
    public array $vendors = [];
    public array $customers = [];
    public array $products = [];
    public array $categories = [];
    public bool $vendorLocked = false;
    public bool $isVendorPanel = false;
    
    // Shipping
    public array $availableCarriers = [];
    public array $shippingResults = [];
    public bool $calculatingShipping = false;
    public bool $hasShippingCalculated = false;
    public float $shippingCost = 0;
    private ?ShippingCalculator $shippingCalculator = null;

    public function mount(): void
    {
        $user = Auth::user();

        // Check if user is a vendor (has vendor relationship)
        if ($user && $user->vendor) {
            $this->vendorLocked = true;
            $this->isVendorPanel = true;
            $vendor = $user->vendor;
            
            // Vendor is automatically selected - no dropdown
            $this->vendors = [$vendor->id => $vendor->store_name];
            $this->vendorId = $vendor->id;
            
        } else {
            // Admin or manager - can select any vendor
            $this->isVendorPanel = false;
            $this->vendors = Vendor::orderBy('store_name')->pluck('store_name', 'id')->toArray();
            $this->vendorId = array_key_first($this->vendors);
        }

        // Get only users with 'customer' role
        $this->customers = User::role('customer')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
            
        $this->customerId = array_key_first($this->customers);
        
        // Pre-fill customer details if selected
        $this->updateCustomerDetails();

        $this->loadVendorProducts();
    }

    public function updatedCustomerId($value): void
    {
        $this->updateCustomerDetails();
    }
    
    private function updateCustomerDetails(): void
    {
        if ($this->customerId) {
            $customer = User::find($this->customerId);
            if ($customer) {
                $this->first_name = $customer->first_name ?? '';
                $this->last_name = $customer->last_name ?? '';
                $this->phone = $customer->phone ?? '';
                $this->city = $customer->city ?? '';
                $this->state = $customer->state ?? '';
                $this->country = $customer->country ?? 'Guinea';
                
                // Try to get user's last address
                $lastAddress = Address::whereHas('order', function($query) use ($customer) {
                    $query->where('user_id', $customer->id);
                })->latest()->first();
                
                if ($lastAddress) {
                    $this->street_address = $lastAddress->street_address;
                    $this->zip_code = $lastAddress->zip_code;
                    $this->latitude = $lastAddress->latitude;
                    $this->longitude = $lastAddress->longitude;
                }
            }
        }
    }

    public function updatedVendorId($value): void
    {
        if ($this->vendorLocked) {
            // Vendor cannot change vendor selection
            return;
        }

        // Reset everything related to products and cart
        $this->cart = [];
        $this->categoryFilter = null;
        $this->productSearch = '';
        $this->advancePaid = 0;
        $this->shippingCost = 0;
        $this->hasShippingCalculated = false;
        $this->availableCarriers = [];
        
        // Reload products for the new vendor
        $this->loadVendorProducts();
        
        // Dispatch event to scroll products to top
        $this->dispatch('pos-scroll-products');
    }

    public function updatedProductSearch(): void
    {
        $this->dispatch('pos-scroll-products');
    }

    public function createCustomerModal()
    {
        $this->resetCustomerForm();
        $this->showCreateCustomerModal = true;
    }

    public function resetCustomerForm()
    {
        $this->customerName = '';
        $this->customerEmail = '';
        $this->customerPhone = '';
    }

    public function saveCustomer()
    {
        $this->validate([
            'customerName' => 'required|string|max:255',
            'customerEmail' => 'nullable|email|max:255|unique:users,email',
            'customerPhone' => 'nullable|string|max:20',
        ]);

        $customer = User::create([
            'name' => $this->customerName,
            'email' => $this->customerEmail ?: fake()->unique()->safeEmail(),
            'phone' => $this->customerPhone,
            'password' => bcrypt('password123'),
            'email_verified_at' => Carbon::now()
        ]);

        $customer->assignRole('customer');

        // Refresh list - only customers with 'customer' role
        $this->customers = User::role('customer')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
            
        $this->customerId = $customer->id;
        
        // Update customer details
        $this->updateCustomerDetails();

        $this->showCreateCustomerModal = false;

        Notification::make()
            ->title('Customer added successfully.')
            ->success()
            ->send();
    }

    protected function rules(): array
    {
        return [
            'vendorId' => ['required', 'exists:vendors,id'],
            'customerId' => ['required', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'paymentMethod' => ['required', 'string'],
            'shippingCarrier' => ['required', 'string'],
            'advancePaid' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function loadVendorProducts(): void
    {
        if (!$this->vendorId) {
            $this->products = [];
            $this->categories = [];
            return;
        }

        // Load products only from this vendor's vendor_product table with wholesale tiers
        $vendorProducts = VendorProduct::where('vendor_id', $this->vendorId)
            ->with([
                'product' => function($query) {
                    $query->with('category')
                        ->where('is_active', 1)
                        ->orderBy('name');
                },
                'wholesaleTiers' => function($query) {
                    $query->orderBy('min_qty', 'asc');
                }
            ])
            ->whereHas('product', function($query) {
                $query->where('is_active', 1);
            })
            ->get();

        $this->products = $vendorProducts->map(function (VendorProduct $vendorProduct) {
            $product = $vendorProduct->product;
            
            if (!$product) {
                return null;
            }

            // Get sale price if available
            $salePrice = $vendorProduct->sale_price ? (float) $vendorProduct->sale_price : null;
            
            // Get wholesale prices from the relationship
            $wholesalePrices = [];
            $minWholesaleQty = null;
            
            if ($vendorProduct->wholesaleTiers->isNotEmpty()) {
                foreach ($vendorProduct->wholesaleTiers as $tier) {
                    $wholesalePrices[$tier->min_qty] = (float) $tier->price;
                }
                
                // Get the minimum quantity for wholesale
                $minWholesaleQty = $vendorProduct->wholesaleTiers->min('min_qty');
            }

            // Determine display price (sale price takes priority)
            $displayPrice = $salePrice ?? (float) $vendorProduct->price;

            return [
                'id' => $product->id,
                'vendor_product_id' => $vendorProduct->id,
                'name' => $product->name,
                'price' => (float) $vendorProduct->price, // Regular price
                'sale_price' => $salePrice,
                'display_price' => $displayPrice, // Price to show (sale or regular)
                'wholesale_prices' => $wholesalePrices, // Wholesale tiers as array
                'min_wholesale_qty' => $minWholesaleQty,
                'stock' => $vendorProduct->stock ?? 0,
                'image' => $product->images[0] ?? null,
                'category' => $product->category->name ?? 'Uncategorized',
                'weight' => $product->weight ?? 0,
                'dimensions' => $product->dimensions ?? null,
                'has_sale' => !empty($salePrice),
                'has_wholesale' => !empty($wholesalePrices),
            ];
        })->filter()->values()->toArray();

        $this->categories = collect($this->products)
            ->pluck('category')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function getFilteredProductsProperty(): array
    {
        return collect($this->products)
            ->when($this->categoryFilter, fn ($collection) => $collection->where('category', $this->categoryFilter))
            ->when($this->productSearch, fn ($collection) => $collection->filter(
                fn ($product) => str($product['name'])->lower()->contains(str()->lower($this->productSearch))
            ))
            ->values()
            ->all();
    }

    private function getBestWholesalePrice(array $wholesalePrices, int $quantity): ?float
    {
        if (empty($wholesalePrices)) {
            return null;
        }
        
        // Sort by quantity threshold (ascending)
        ksort($wholesalePrices);
        
        $bestPrice = null;
        foreach ($wholesalePrices as $minQty => $price) {
            if ($quantity >= $minQty) {
                $bestPrice = (float) $price;
            } else {
                break; // Prices are sorted, so we can break when quantity is less than threshold
            }
        }
        
        return $bestPrice;
    }

    public function addProduct(int $vendorProductId): void
    {
        $product = collect($this->products)->firstWhere('vendor_product_id', $vendorProductId);

        if (!$product) {
            return;
        }

        // Check stock availability
        if ($product['stock'] <= 0) {
            Notification::make()
                ->title('Out of stock')
                ->body("{$product['name']} is out of stock.")
                ->danger()
                ->send();
            return;
        }

        $index = collect($this->cart)->search(fn ($line) => $line['vendor_product_id'] === $vendorProductId);

        if ($index === false) {
            // Determine price for quantity 1
            $displayPrice = $product['display_price'];
            $wholesalePrice = $this->getBestWholesalePrice($product['wholesale_prices'], 1);
            
            // Use wholesale price if available for quantity 1, otherwise use display price
            $price = $wholesalePrice ?? $displayPrice;
            $isWholesale = !empty($wholesalePrice);
            $isSale = $product['has_sale'] && empty($wholesalePrice); // Only consider sale if not wholesale

            $this->cart[] = [
                'product_id' => $product['id'], // CRITICAL: Ensure product_id is set
                'vendor_product_id' => $product['vendor_product_id'],
                'name' => $product['name'],
                'price' => $price,
                'regular_price' => $product['price'],
                'sale_price' => $product['sale_price'],
                'display_price' => $product['display_price'],
                'wholesale_prices' => $product['wholesale_prices'],
                'min_wholesale_qty' => $product['min_wholesale_qty'],
                'quantity' => 1,
                'stock' => $product['stock'],
                'image' => $product['image'],
                'is_wholesale' => $isWholesale,
                'is_sale' => $isSale,
                'has_wholesale' => $product['has_wholesale'],
                'has_sale' => $product['has_sale'],
            ];
            
            Notification::make()
                ->title('Product added')
                ->body("{$product['name']} added to cart.")
                ->success()
                ->send();
        } else {
            $this->incrementItem($vendorProductId);
        }
    }

    public function updateQuantity(int $vendorProductId, int $newQuantity): void
    {
        if ($newQuantity < 1) {
            $this->removeItem($vendorProductId);
            return;
        }

        foreach ($this->cart as &$line) {
            if ($line['vendor_product_id'] === $vendorProductId) {
                if ($newQuantity <= $line['stock']) {
                    $oldQuantity = $line['quantity'];
                    $line['quantity'] = $newQuantity;
                    
                    // Update price based on new quantity (check for wholesale)
                    if ($line['has_wholesale']) {
                        $wholesalePrice = $this->getBestWholesalePrice($line['wholesale_prices'], $newQuantity);
                        
                        if ($wholesalePrice) {
                            $line['price'] = $wholesalePrice;
                            $line['is_wholesale'] = true;
                            $line['is_sale'] = false;
                        } else {
                            // No longer eligible for wholesale, revert to sale or regular price
                            $line['price'] = $line['display_price'];
                            $line['is_wholesale'] = false;
                            $line['is_sale'] = $line['has_sale'];
                        }
                    }
                    
                    if ($newQuantity > $oldQuantity) {
                        Notification::make()
                            ->title('Quantity updated')
                            ->body("{$line['name']} quantity increased to {$newQuantity}.")
                            ->success()
                            ->send();
                    }
                } else {
                    Notification::make()
                        ->title('Stock limit reached')
                        ->body("Cannot set quantity to {$newQuantity}. Only {$line['stock']} available.")
                        ->warning()
                        ->send();
                    $line['quantity'] = $line['stock']; // Set to max stock
                }
                break;
            }
        }
        unset($line);
    }

    public function incrementItem(int $vendorProductId): void
    {
        foreach ($this->cart as &$line) {
            if ($line['vendor_product_id'] === $vendorProductId) {
                $this->updateQuantity($vendorProductId, $line['quantity'] + 1);
                break;
            }
        }
        unset($line);
    }

    public function decrementItem(int $vendorProductId): void
    {
        foreach ($this->cart as &$line) {
            if ($line['vendor_product_id'] === $vendorProductId) {
                $this->updateQuantity($vendorProductId, $line['quantity'] - 1);
                break;
            }
        }
        unset($line);
    }

    public function removeItem(int $vendorProductId): void
    {
        $productName = '';
        foreach ($this->cart as $index => $line) {
            if ($line['vendor_product_id'] === $vendorProductId) {
                $productName = $line['name'];
                unset($this->cart[$index]);
                break;
            }
        }
        $this->cart = array_values($this->cart);
        
        if ($productName) {
            Notification::make()
                ->title('Product removed')
                ->body("{$productName} removed from cart.")
                ->success()
                ->send();
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->advancePaid = 0;
        $this->shippingCost = 0;
        $this->hasShippingCalculated = false;
        $this->availableCarriers = [];
        
        Notification::make()
            ->title('Cart cleared')
            ->body('All items removed from cart.')
            ->success()
            ->send();
    }

    public function getTotalQuantityProperty(): int
    {
        return (int) collect($this->cart)->sum('quantity');
    }

    public function getSubtotalCostProperty(): float
    {
        return (float) collect($this->cart)
            ->sum(fn ($line) => $line['quantity'] * $line['price']);
    }
    
    public function getTotalCostProperty(): float
    {
        return $this->subtotalCost + $this->shippingCost;
    }
    
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
        
        // Initialize shipping calculator if not already initialized
        if (!$this->shippingCalculator) {
            $this->shippingCalculator = new ShippingCalculator();
        }
        
        // Prepare cart items for shipping calculation
        $cartItems = [];
        foreach ($this->cart as $item) {
            // Find the product in products array
            $product = collect($this->products)->firstWhere('vendor_product_id', $item['vendor_product_id']);
            //dd($product);
            if ($product) {
                $cartItems[] = [
                    'vendor_product_id' => $item['vendor_product_id'],
                    'product_id' => $product['id'] ?? 0, // FIXED: Always use product['id'] not item['product_id']
                    'product_name' => $item['name'] ?? $product['name'] ?? 'Product',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_amount' => $item['price'] ?? $product['display_price'] ?? 0,
                    'total_amount' => ($item['quantity'] ?? 1) * ($item['price'] ?? $product['display_price'] ?? 0),
                    'weight' => $product['weight'] ?? 0,
                    'dimensions' => $product['dimensions'] ?? null,
                    'vendor_id' => $this->vendorId,
                ];
            }
        }
        
        if (empty($cartItems)) {
            Notification::make()
                ->title('Add products to cart first.')
                ->danger()
                ->send();
            $this->calculatingShipping = false;
            return;
        }
        
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
        
        // Group items by vendor (in POS it's always one vendor)
        $groupedItems = [
            $this->vendorId => [
                'vendor' => Vendor::with('currency')->find($this->vendorId),
                'items' => $cartItems,
                'subtotal' => $this->subtotalCost,
            ]
        ];
        
        try {
            //dd($groupedItems);
            $shippingResult = $this->shippingCalculator->calculateCartShipping(
                $groupedItems,
                $destinationAddress,
                $this->shippingCarrier
            );
            
            $this->availableCarriers = $shippingResult['carriers'] ?? [];
            
            // Auto-select cheapest carrier if current carrier not available
            if (!empty($this->availableCarriers)) {
                if (!isset($this->availableCarriers[$this->shippingCarrier])) {
                    reset($this->availableCarriers);
                    $this->shippingCarrier = key($this->availableCarriers);
                }
                
                // Get selected shipping cost
                $selectedShipping = $this->availableCarriers[$this->shippingCarrier] ?? [];
                $this->shippingCost = $selectedShipping['total_cost_usd'] ?? 0;
            } else {
                // No carriers available, fallback to local
                $this->shippingCarrier = 'local';
                $this->availableCarriers['local'] = [
                    'name' => 'Local Delivery',
                    'carrier' => 'local',
                    'total_cost_usd' => 0,
                    'vendors' => [$this->vendorId => ['cost' => 0]],
                    'is_available' => true,
                ];
                $this->shippingCost = 0;
            }
            
            $this->hasShippingCalculated = true;
            
            Notification::make()
                ->title('Shipping calculated successfully.')
                ->success()
                ->send();
            
        } catch (\Exception $e) {
            Log::error('POS Shipping calculation error: ' . $e->getMessage());
            Notification::make()
                ->title('Error calculating shipping')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            // Fallback
            $this->availableCarriers = [
                'local' => [
                    'name' => 'Local Delivery',
                    'carrier' => 'local',
                    'total_cost_usd' => 0,
                    'vendors' => [$this->vendorId => ['cost' => 0]],
                    'is_available' => true,
                ]
            ];
            $this->shippingCarrier = 'local';
            $this->shippingCost = 0;
            $this->hasShippingCalculated = true;
        } finally {
            $this->calculatingShipping = false;
        }
    }
    
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
    
    public function getSelectedShipping(): array
    {
        $selectedCarrier = $this->availableCarriers[$this->shippingCarrier] ?? null;
        
        if (!$selectedCarrier && !empty($this->availableCarriers)) {
            reset($this->availableCarriers);
            $this->shippingCarrier = key($this->availableCarriers);
            $selectedCarrier = $this->availableCarriers[$this->shippingCarrier];
        }
        
        return $selectedCarrier ?? [
            'name' => 'Local Delivery',
            'carrier' => 'local',
            'total_cost_usd' => 0,
            'description' => 'Standard delivery',
            'vendors' => [],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        if (empty($this->cart)) {
            Notification::make()
                ->title('Add at least one product to the cart.')
                ->danger()
                ->send();
            return;
        }
        
        // Calculate shipping if not done
        if (!$this->hasShippingCalculated) {
            $this->calculateShipping();
            if (!$this->hasShippingCalculated) {
                return;
            }
        }

        $subtotal = $this->subtotalCost;
        $shipping = $this->shippingCost;
        $totalCost = $subtotal + $shipping;
        
        $totalPaid = min($this->advancePaid, $totalCost);
        $totalRemaining = max($totalCost - $totalPaid, 0);

        DB::beginTransaction();

        try {
            $vendor = Vendor::with('currency')->find($this->vendorId);
            $customer = User::find($this->customerId);
            
            $order = Order::create([
                'user_id' => $this->customerId,
                'vendor_id' => $this->vendorId,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'processing',
                'payment_method' => $this->paymentMethod,
                'payment_status' => $totalPaid >= $totalCost ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending'),
                'grand_total' => $totalCost,
                'shipping_amount' => $shipping,
                'shipping_carrier' => $this->shippingCarrier,
                'notes' => $this->notes,
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
                'currency_id' => $vendor->currency_id ?? null,
            ]);

            // Create address with zone
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
            
            // Add zone from shipping calculation if available
            $selectedShipping = $this->getSelectedShipping();
            if (isset($selectedShipping['vendors'][$this->vendorId]['zone'])) {
                $addressData['zone'] = $selectedShipping['vendors'][$this->vendorId]['zone'];
            }
            
            Address::create($addressData);

            foreach ($this->cart as $line) {
                // Get product from products array to ensure we have the correct product_id
                $product = collect($this->products)->firstWhere('vendor_product_id', $line['vendor_product_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['id'] ?? 0, // FIXED: Always use product['id']
                    'quantity' => $line['quantity'],
                    'unit_amount' => $line['price'],
                    'total_amount' => $line['quantity'] * $line['price'],
                ]);

                // Reduce stock in vendor_product
                VendorProduct::where('id', $line['vendor_product_id'])
                    ->decrement('stock', $line['quantity']);
            }

            if ($totalPaid > 0) {
                Paiement::create([
                    'order_id' => $order->id,
                    'amount' => $totalPaid,
                    'image' => null,
                    'payment_method' => $this->paymentMethod,
                    'currency' => $vendor->currency->code ?? config('app.currency', 'USD'),
                    'payment_status' => $totalPaid >= $totalCost ? 'paid' : 'partial',
                    'transaction_id' => Order::generateTransactionNumber(),
                ]);
            }

            DB::commit();

            Notification::make()
                ->title('Order created successfully!')
                ->body("Order #{$order->order_number} has been created for {$customer->name}.")
                ->success()
                ->send();

            $this->clearCart();
            $this->notes = '';
            
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('POS Order Error: ' . $exception->getMessage());
            
            Notification::make()
                ->title('Unable to create order')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function formatCurrency(float $amount): string
    {
        $vendor = Vendor::query()->with('currency')->find($this->vendorId);
        $currency = $vendor->currency->code ?? config('app.currency', 'USD');
        
        return sprintf('%s %s', $currency, number_format($amount, 2));
    }

    public function shippingCarrierOptions(): array
    {
        return [
            'local' => 'Local Courier',
            'chrono' => 'Chronopost',
            'dhl' => 'DHL Express',
            'ups' => 'UPS',
            'fedex' => 'FedEx',
            'other' => 'Other',
        ];
    }

    public function paymentMethodOptions(): array
    {
        return [
            'cash' => 'Cash',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'cod' => 'Cash on Delivery',
            'om' => 'Orange Money',
        ];
    }
    
    public function updated($property)
    {
        // Recalculate shipping when address fields change
        if (in_array($property, [
            'first_name', 'last_name', 'city', 'state', 'zip_code', 
            'country', 'latitude', 'longitude', 'street_address'
        ])) {
            if ($this->first_name && $this->last_name && $this->city && 
                $this->country && !empty($this->cart)) {
                $this->hasShippingCalculated = false;
            }
        }
        
        // Recalculate shipping when carrier changes
        if ($property === 'shippingCarrier' && $this->hasShippingCalculated) {
            $selectedShipping = $this->getSelectedShipping();
            $this->shippingCost = $selectedShipping['total_cost_usd'] ?? 0;
        }
    }
}