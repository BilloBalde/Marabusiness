@php
    use Illuminate\Support\Facades\Storage;
@endphp

@push('styles')
<style>
    /* Theme tokens (light default) */
    :root {
        --pos-page-bg: linear-gradient(135deg, #f3f4f6 0%, #ffffff 100%);
        --pos-surface: #ffffff;
        --pos-surface-2: #f8fafc;
        --pos-card: #ffffff;
        --pos-border: #e2e8f0;
        --pos-border-2: #e5e7eb;
        --pos-muted: #f1f5f9;
        --pos-text: #0f172a;
        --pos-text-subtle: #475569;
        --pos-shadow: 0 15px 50px rgba(15, 23, 42, 0.08);
    }

    .dark {
        --pos-page-bg: linear-gradient(135deg, #0f172a 0%, #111827 100%);
        --pos-surface: #0b1220;
        --pos-surface-2: #0f172a;
        --pos-card: #111827;
        --pos-border: #1f2937;
        --pos-border-2: #1f2937;
        --pos-muted: #0f172a;
        --pos-text: #e2e8f0;
        --pos-text-subtle: #94a3b8;
        --pos-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    }

    body {
        background: var(--pos-page-bg);
        color: var(--pos-text);
    }

    .pos-wrapper {
        max-width: 1650px;
        margin: 0 auto;
        padding: 1.5rem;
        margin-top: 1.5rem;
        color: var(--pos-text);
    }

    .pos-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(400px, 1.3fr);
        gap: 1.5rem;
    }

    .pos-panel {
        background: var(--pos-surface);
        border-radius: 1.15rem;
        padding: 1.5rem;
        box-shadow: var(--pos-shadow);
        border: 1px solid var(--pos-border);
    }

    .pos-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        max-height: calc(100vh - 340px);
        overflow-y: auto;
        padding-right: 0.5rem;
    }

    .pos-product-card {
        border-radius: 1rem;
        background: var(--pos-card);
        border: 1px solid var(--pos-border-2);
        padding: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .pos-product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(15, 23, 42, 0.08);
    }

    .pos-product-card img {
        border-radius: 0.9rem;
        width: 100%;
        height: 150px;
        object-fit: cover;
        background: var(--pos-muted);
    }

    .pos-cart-panel {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        position: sticky;
        top: 1.5rem;
    }

    .pos-cart-list {
        max-height: 55vh;
        overflow-y: auto;
        border-radius: 0.75rem;
        border: 1px solid var(--pos-border);
        padding: 0.75rem;
        background: var(--pos-surface-2);
    }

    .pos-cart-item {
        border-bottom: 1px solid var(--pos-border);
        padding: 0.85rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.85rem;
    }

    .pos-cart-item:last-child {
        border-bottom: none;
    }

    .pos-cart-item img {
        width: 54px;
        height: 54px;
        object-fit: cover;
        border-radius: 0.65rem;
    }

    .category-pill {
        border-radius: 999px;
        padding: 0.45rem 0.9rem;
        font-size: 0.85rem;
        border: 1px solid var(--pos-border);
        background: var(--pos-surface-2);
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--pos-text);
    }

    .category-pill.active {
        background: var(--fi-color-primary-600);
        color: #0f172a;
        border-color: transparent;
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
    }

    @media (max-width: 1200px) {
        .pos-shell {
            grid-template-columns: 1fr;
        }
        .pos-cart-panel {
            position: static;
        }
        .pos-product-grid {
            max-height: unset;
        }
    }

    /* ------------------------------
       OPTION C: BEAUTIFY INPUTS
    --------------------------------*/

    input[type="text"],
    input[type="search"],
    input[type="number"],
    input[type="date"],
    textarea,
    select {
        appearance: none;
        width: 100%;
        padding: 0.55rem 0.75rem;
        border-radius: 0.65rem;
        border: 1px solid var(--pos-border);
        background: var(--pos-surface);
        font-size: 0.875rem;
        color: var(--pos-text);
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    input:hover,
    select:hover,
    textarea:hover {
        border-color: #9ca3af;
    }

    input:focus,
    textarea:focus,
    select:focus {
        border-color: var(--fi-color-primary-600, #4f46e5);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
        outline: none;
    }

    label,
    .fi-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--pos-text-subtle);
        margin-bottom: 0.25rem;
        display: inline-block;
    }

    textarea {
        resize: vertical;
        min-height: 45px;
    }

    /* ------------------------------
       FIX SELECT DOUBLE ARROW
    --------------------------------*/
    .pos-select {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;

        background-image:
            url("data:image/svg+xml,%3Csvg fill='none' stroke='%236b7280' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 1rem;

        padding-right: 2.5rem !important;
    }

    .pos-select::-ms-expand {
        display: none;
    }

    .pos-number-input {
        width: 55px;
        text-align: center;
    }
    .dark .text-gray-500 { color: var(--pos-text-subtle) !important; }
    .dark .text-gray-800 { color: var(--pos-text) !important; }
    .dark .bg-white { background-color: var(--pos-surface) !important; }
    .dark .bg-gray-100 { background-color: var(--pos-muted) !important; }
    .dark .text-gray-900 { color: var(--pos-text) !important; }
    .dark .text-sm { color: inherit; }
</style>
@endpush

<x-filament::page>
    <div class="pos-wrapper" id="pos-root">
        <div class="pos-shell">

            {{-- PRODUCTS SECTION --}}
            <div class="pos-panel pos-products">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="fi-label">Vendor</label>
                        @if ($vendorLocked)
                            <div class="px-4 py-2 mt-1 bg-gray-100 rounded-lg border border-gray-200 text-sm">
                                {{ $vendors[$vendorId] ?? 'Vendor' }}
                            </div>
                        @else
                            <select wire:model.live="vendorId" class="pos-select mt-1">
                                @foreach ($vendors as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div>
                        <label class="fi-label">Customer</label>
                        <div class="flex items-center gap-2 mt-1">
                            <select wire:model.live="customerId" class="pos-select mt-1 w-full">
                                @foreach ($customers as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <x-filament::button size="sm" color="gray" wire:click="createCustomerModal">+</x-filament::button>
                        </div>
                    </div>
                </div>

                {{-- ADDRESS SECTION --}}
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="fi-label">First Name</label>
                        <input type="text" wire:model="first_name" class="mt-1 w-full" required />
                    </div>
                    
                    <div>
                        <label class="fi-label">Last Name</label>
                        <input type="text" wire:model="last_name" class="mt-1 w-full" required />
                    </div>
                </div>
                
                <div class="mt-2 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="fi-label">Phone</label>
                        <input type="text" wire:model="phone" class="mt-1 w-full" required />
                    </div>
                    
                    <div>
                        <label class="fi-label">City</label>
                        <input type="text" wire:model="city" class="mt-1 w-full" required />
                    </div>
                </div>
                
                <div class="mt-2">
                    <label class="fi-label">Street Address</label>
                    <input type="text" wire:model="street_address" class="mt-1 w-full" required />
                </div>
                
                <div class="mt-2 grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="fi-label">State</label>
                        <input type="text" wire:model="state" class="mt-1 w-full" required />
                    </div>
                    
                    <div>
                        <label class="fi-label">ZIP Code</label>
                        <input type="text" wire:model="zip_code" class="mt-1 w-full" />
                    </div>
                    
                    <div>
                        <label class="fi-label">Country</label>
                        <input type="text" wire:model="country" class="mt-1 w-full" required />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="fi-label">Search Products</label>
                        <input type="search"
                               wire:model.debounce.400ms="productSearch"
                               placeholder="Search by name"
                               class="mt-1 w-full" />
                    </div>

                    <div>
                        <label class="fi-label">Notes</label>
                        <textarea wire:model.defer="notes" rows="1"
                                  class="mt-1 w-full"
                                  placeholder="Add notes for the order"></textarea>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button"
                            wire:click="$set('categoryFilter', null)"
                            class="category-pill {{ $categoryFilter ? '' : 'active' }}">
                        All Categories
                    </button>

                    @foreach ($categories as $category)
                        <button type="button"
                                wire:click="$set('categoryFilter', '{{ $category }}')"
                                class="category-pill {{ $categoryFilter === $category ? 'active' : '' }}">
                            {{ $category }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 pos-product-grid" id="pos-product-grid">
                    @forelse ($this->filteredProducts as $product)
                        <div class="pos-product-card">
                            <img src="{{ $product['image']
                                ? Storage::disk('public_uploads')->url($product['image'])
                                : url('uploads/default.png') }}"
                                 alt="{{ $product['name'] }}">

                            <div class="font-semibold text-sm truncate">{{ $product['name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $product['category'] }}</div>

                            <div class="flex items-center justify-between text-sm">
                                <span>Price:</span>
                                <span class="font-semibold">{{ $this->formatCurrency($product['display_price']) }}</span>
                            </div>

                            @if($product['has_sale'])
                                <div class="text-xs text-red-600">
                                    <s>{{ $this->formatCurrency($product['price']) }}</s>
                                    <span class="ml-1">Sale!</span>
                                </div>
                            @endif

                            @if($product['has_wholesale'] && !empty($product['wholesale_prices']))
                                <div class="text-xs text-green-600">
                                    Wholesale available
                                    @php
                                        // Get the minimum quantity for wholesale
                                        $minQty = min(array_keys($product['wholesale_prices']));
                                        $minPrice = $product['wholesale_prices'][$minQty] ?? null;
                                    @endphp
                                    @if($minPrice)
                                        (from {{ $minQty }}: {{ $this->formatCurrency($minPrice) }})
                                    @endif
                                </div>
                            @endif

                            <div class="text-xs text-gray-500">Stock: {{ $product['stock'] }}</div>

                            {{-- Use vendor_product_id instead of product_id --}}
                            @if($product['stock'] <= 0)
                                <x-filament::button color="primary" disabled>
                                    Out of Stock
                                </x-filament::button>
                            @else
                                <x-filament::button color="primary" wire:click="addProduct({{ $product['vendor_product_id'] }})">
                                    Add to Cart
                                </x-filament::button>
                            @endif
                        </div>

                    @empty
                        <div class="col-span-full text-center text-gray-500">
                            No products found for this vendor.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- CART & SHIPPING SECTION --}}
            <div class="pos-panel pos-cart-panel">

                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Cart</h3>

                    @if (count($cart) > 0)
                        <button type="button"
                            class="text-sm text-danger-600"
                            wire:click="clearCart">
                            Clear
                        </button>
                    @endif
                </div>

                <div class="pos-cart-list">
                    @forelse ($cart as $line)
                        <div class="pos-cart-item">

                            <div class="flex items-center gap-3">
                                <img src="{{ $line['image']
                                    ? Storage::disk('public_uploads')->url($line['image'])
                                    : url('uploads/default.png') }}"
                                     alt="{{ $line['name'] }}">
                                <div>
                                    <div class="font-semibold text-sm">{{ $line['name'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $this->formatCurrency($line['price']) }}
                                        @if($line['is_wholesale'])
                                            <span class="text-green-600 ml-1">(Wholesale)</span>
                                        @elseif($line['is_sale'])
                                            <span class="text-red-600 ml-1">(Sale)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::button size="xs" color="gray"
                                    wire:click="decrementItem({{ $line['vendor_product_id'] }})">-</x-filament::button>

                                <input type="number" 
                                    min="1" 
                                    max="{{ $line['stock'] }}"
                                    style="width: 5em;"
                                    wire:change="updateQuantity({{ $line['vendor_product_id'] }}, $event.target.value)"
                                    class="pos-number-input"
                                    value="{{ $line['quantity'] }}">

                                <x-filament::button size="xs" color="gray"
                                    wire:click="incrementItem({{ $line['vendor_product_id'] }})">+</x-filament::button>
                            </div>

                            <div class="text-sm font-semibold">
                                {{ $this->formatCurrency($line['quantity'] * $line['price']) }}
                            </div>

                            <button type="button"
                                class="text-danger-500 text-xs"
                                wire:click="removeItem({{ $line['vendor_product_id'] }})">
                                Remove
                            </button>

                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-500 m-0">Cart is empty.</p>
                    @endforelse
                </div>

                {{-- SHIPPING CALCULATION --}}
                @if(count($cart) > 0)
                    <div class="border-t pt-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-md font-semibold">Shipping</h4>
                            @if(!$hasShippingCalculated)
                                <x-filament::button size="sm" color="gray" wire:click="calculateShipping" :disabled="$calculatingShipping">
                                    @if($calculatingShipping)
                                        Calculating...
                                    @else
                                        Calculate Shipping
                                    @endif
                                </x-filament::button>
                            @endif
                        </div>
                        
                        @if($hasShippingCalculated && !empty($availableCarriers))
                            <div class="mb-3">
                                <label class="fi-label">Select Carrier</label>
                                <select wire:model.live="shippingCarrier" class="pos-select mt-1 w-full">
                                    @foreach ($availableCarriers as $carrierCode => $carrier)
                                        @if($carrier['is_available'] ?? true)
                                            <option value="{{ $carrierCode }}">
                                                {{ $carrier['name'] }} - {{ $this->formatCurrency($carrier['total_cost_usd'] ?? 0) }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        
                        @if(!$hasShippingCalculated && count($cart) > 0)
                            <div class="text-sm text-gray-500 mb-3">
                                Add address details and click "Calculate Shipping"
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-2 border-t pt-3">
                    <div class="flex justify-between text-sm">
                        <span>Total Quantity</span>
                        <span class="font-semibold">{{ $this->totalQuantity }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span>Subtotal</span>
                        <span class="font-semibold">{{ $this->formatCurrency($this->subtotalCost) }}</span>
                    </div>

                    @if($hasShippingCalculated)
                        <div class="flex justify-between text-sm">
                            <span>Shipping</span>
                            <span class="font-semibold">{{ $this->formatCurrency($this->shippingCost) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-base border-t pt-2">
                        <span>Total Cost</span>
                        <span class="font-semibold">{{ $this->formatCurrency($this->totalCost) }}</span>
                    </div>

                    <div class="flex justify-between text-sm text-success-600">
                        <span>Advance Paid</span>
                        <span class="font-semibold">{{ $this->formatCurrency($advancePaid) }}</span>
                    </div>

                    <div class="flex justify-between text-sm text-danger-600">
                        <span>Remaining</span>
                        <span class="font-semibold">{{ $this->formatCurrency(max($this->totalCost - $advancePaid, 0)) }}</span>
                    </div>
                </div>

                <div class="grid gap-3">
                    <div>
                        <label class="fi-label">Payment Method</label>
                        <select wire:model="paymentMethod" class="pos-select mt-1 w-full">
                            @foreach ($this->paymentMethodOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="fi-label">Advance Paid</label>
                        <input type="number" min="0" step="0.01"
                               wire:model.defer="advancePaid"
                               class="mt-1 w-full" />
                    </div>
                </div>

                <x-filament::button 
                    wire:click="submit" 
                    class="w-full"
                    :disabled="count($cart) === 0 || !$hasShippingCalculated">
                    Complete Sale
                </x-filament::button>

            </div>
        </div>
    </div>
    
    @if($showCreateCustomerModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[9999]">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Add Customer</h2>

                <div class="space-y-3">
                    <div>
                        <label class="fi-label">Customer Name</label>
                        <input type="text" wire:model="customerName" class="mt-1 w-full" />
                    </div>

                    <div>
                        <label class="fi-label">Email</label>
                        <input type="text" wire:model="customerEmail" class="mt-1 w-full" />
                    </div>
                    
                    <div>
                        <label class="fi-label">Phone</label>
                        <input type="text" wire:model="customerPhone" class="mt-1 w-full" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button color="gray" wire:click="$set('showCreateCustomerModal', false)">Cancel</x-filament::button>
                    <x-filament::button wire:click="saveCustomer">Save</x-filament::button>
                </div>
            </div>
        </div>
    @endif

</x-filament::page>

@push('scripts')
<script>
    window.addEventListener('pos-scroll-products', () => {
        const grid = document.getElementById('pos-product-grid');
        if (grid) grid.scrollTop = 0;
    });
</script>
@endpush
