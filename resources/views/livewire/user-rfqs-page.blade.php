<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Requests for Quotation (RFQs)</h1>
            <p class="text-gray-600 mt-2">Manage your wholesale quote requests and communicate with vendors</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pending</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $rfqs->where('status', 'pending')->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600">
                        <i class="fas fa-file-invoice-dollar text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Quoted</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $rfqs->where('status', 'quoted')->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Accepted</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $rfqs->where('status', 'accepted')->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-gray-100 text-gray-600">
                        <i class="fas fa-ban text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total RFQs</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $rfqs->total() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-xl shadow mb-6 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by product name..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="flex items-center space-x-4">
                    <select 
                        wire:model.live="status"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Status</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                    <!-- New RFQ Button -->
                    <a href="/products" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        <i class="fas fa-plus mr-2"></i> New RFQ
                    </a>
                </div>
            </div>
        </div>

        <!-- RFQs Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @if($rfqs->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-invoice-dollar text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No RFQs Yet</h3>
                    <p class="text-gray-500 mb-6">Submit your first wholesale quote request to get started</p>
                    <a href="/products" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        <i class="fas fa-shopping-cart mr-2"></i> Browse Products
                    </a>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Vendor
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Price Target
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Submitted
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($rfqs as $rfq)
                                <tr class="hover:bg-gray-50">
                                    <!-- Product -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($rfq->product && $rfq->product->images && count($rfq->product->images) > 0)
                                                <img 
                                                    src="{{ url('uploads/' . $rfq->product->images[0]) }}" 
                                                    alt="{{ $rfq->product->name }}"
                                                    class="w-10 h-10 rounded-lg object-cover mr-3"
                                                >
                                            @else
                                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                                    <i class="fas fa-box text-gray-400"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $rfq->product->name ?? 'Product' }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    RFQ #{{ $rfq->id }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Vendor -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $rfq->vendor->store_name ?? 'Vendor' }}</div>
                                        <div class="text-xs text-gray-500">{{ $rfq->vendor->city ?? '' }}</div>
                                    </td>
                                    
                                    <!-- Quantity -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ number_format($rfq->quantity) }} units
                                        </div>
                                    </td>
                                    
                                    <!-- Price Target -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($rfq->target_price)
                                            <div class="text-sm text-gray-900">
                                                {{ number_format($rfq->target_price, 2) }} {{ $rfq->currency }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">Not specified</span>
                                        @endif
                                    </td>
                                    
                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'quoted' => 'bg-green-100 text-green-800',
                                                'accepted' => 'bg-blue-100 text-blue-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($rfq->status) }}
                                        </span>
                                    </td>
                                    
                                    <!-- Submitted -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $rfq->created_at->format('M d, Y') }}
                                        <div class="text-xs">{{ $rfq->created_at->format('h:i A') }}</div>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <!-- View/Details -->
                                            <button 
                                                wire:click="$dispatch('open-rfq-modal', {rfqId: {{ $rfq->id }} })"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="View Details"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Chat -->
                                            <a 
                                                href="{{ route('rfq.chat', $rfq) }}"
                                                class="text-green-600 hover:text-green-900"
                                                title="Chat with Vendor"
                                            >
                                                <i class="fas fa-comment"></i>
                                            </a>
                                            
                                            <!-- Cancel (only for pending/quoted) -->
                                            @if(in_array($rfq->status, ['pending', 'quoted']))
                                                <button 
                                                    wire:click="cancelRfq({{ $rfq->id }})"
                                                    wire:confirm="Are you sure you want to cancel this RFQ?"
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Cancel RFQ"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-4 p-4">
                    @foreach($rfqs as $rfq)
                        <div class="bg-gray-50 rounded-lg p-4 border">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $rfq->product->name ?? 'Product' }}</h3>
                                    <p class="text-xs text-gray-500">RFQ #{{ $rfq->id }}</p>
                                </div>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'quoted' => 'bg-green-100 text-green-800',
                                        'accepted' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($rfq->status) }}
                                </span>
                            </div>
                            
                            <!-- Details -->
                            <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                                <div>
                                    <p class="text-gray-500">Vendor</p>
                                    <p class="font-medium">{{ $rfq->vendor->store_name ?? 'Vendor' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Quantity</p>
                                    <p class="font-medium">{{ number_format($rfq->quantity) }} units</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Target Price</p>
                                    <p class="font-medium">
                                        @if($rfq->target_price)
                                            {{ number_format($rfq->target_price, 2) }} {{ $rfq->currency }}
                                        @else
                                            <span class="text-gray-400">Not specified</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Submitted</p>
                                    <p class="font-medium">{{ $rfq->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex justify-end space-x-3 pt-3 border-t">
                                <button 
                                    wire:click="$dispatch('open-rfq-modal', {rfqId: {{ $rfq->id }} })"
                                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                >
                                    <i class="fas fa-eye mr-1"></i> Details
                                </button>
                                <a 
                                    href="{{ route('rfq.chat', $rfq) }}"
                                    class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                                >
                                    <i class="fas fa-comment mr-1"></i> Chat
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $rfqs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- RFQ Details Modal -->
    <div x-data="{ 
        show: false, 
        rfq: null,
        loading: true,
        quotes: []
    }" 
    x-on:open-rfq-modal.window="
        show = true;
        loading = true;
        rfq = $event.detail.rfqId;
        fetch(`/api/rfq/${$event.detail.rfqId}`)
            .then(response => response.json())
            .then(data => {
                rfq = data.rfq;
                quotes = data.quotes || [];
                loading = false;
            });
    ">
        <!-- Modal -->
        <div x-show="show" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background -->
                <div x-on:click="show = false" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Loading -->
                    <template x-if="loading">
                        <div class="p-12 text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-4"></i>
                            <p class="text-gray-600">Loading RFQ details...</p>
                        </div>
                    </template>

                    <!-- Content -->
                    <template x-if="!loading && rfq">
                        <div>
                            <!-- Header -->
                            <div class="bg-gray-50 px-6 py-4 border-b">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            RFQ #<span x-text="rfq.id"></span>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full" 
                                                  :class="{
                                                      'bg-yellow-100 text-yellow-800': rfq.status === 'pending',
                                                      'bg-green-100 text-green-800': rfq.status === 'quoted',
                                                      'bg-blue-100 text-blue-800': rfq.status === 'accepted',
                                                      'bg-red-100 text-red-800': rfq.status === 'rejected',
                                                      'bg-gray-100 text-gray-800': rfq.status === 'cancelled'
                                                  }">
                                                <span x-text="rfq.status.charAt(0).toUpperCase() + rfq.status.slice(1)"></span>
                                            </span>
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Submitted on <span x-text="new Date(rfq.created_at).toLocaleDateString()"></span>
                                        </p>
                                    </div>
                                    <button x-on:click="show = false" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-6">
                                <!-- Product Info -->
                                <div class="mb-8">
                                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Product Information</h4>
                                    <div class="flex items-start space-x-4">
                                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <template x-if="rfq.product && rfq.product.images && rfq.product.images.length > 0">
                                                <img :src="`/uploads/${rfq.product.images[0]}`" class="w-full h-full object-cover rounded-lg">
                                            </template>
                                            <template x-if="!rfq.product || !rfq.product.images || rfq.product.images.length === 0">
                                                <i class="fas fa-box text-2xl text-gray-400"></i>
                                            </template>
                                        </div>
                                        <div>
                                            <h5 class="font-medium text-gray-900" x-text="rfq.product?.name || 'Product'"></h5>
                                            <p class="text-sm text-gray-500" x-text="rfq.product?.description ? rfq.product.description.substring(0, 100) + '...' : 'No description'"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- RFQ Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Order Details</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Quantity Requested:</dt>
                                                <dd class="text-sm font-medium text-gray-900">
                                                    <span x-text="new Intl.NumberFormat().format(rfq.quantity)"></span> units
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Target Price:</dt>
                                                <dd class="text-sm font-medium text-gray-900">
                                                    <template x-if="rfq.target_price">
                                                        <span x-text="`${new Intl.NumberFormat('en-US', { style: 'currency', currency: rfq.currency }).format(rfq.target_price)}`"></span>
                                                    </template>
                                                    <template x-if="!rfq.target_price">
                                                        <span class="text-gray-400">Not specified</span>
                                                    </template>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Customization Needed:</dt>
                                                <dd class="text-sm font-medium text-gray-900">
                                                    <span x-text="rfq.needs_customization ? 'Yes' : 'No'"></span>
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Shipping Details</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Country:</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="rfq.shipping_country || 'Not specified'"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">City:</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="rfq.shipping_city || 'Not specified'"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Port:</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="rfq.shipping_port || 'Not specified'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <!-- Customization Notes -->
                                <template x-if="rfq.customization_notes">
                                    <div class="mb-8">
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Customization Notes</h4>
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <p class="text-gray-700" x-text="rfq.customization_notes"></p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Quotes Section -->
                                <template x-if="quotes.length > 0">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Vendor Quotes</h4>
                                        <div class="space-y-4">
                                            <template x-for="quote in quotes" :key="quote.id">
                                                <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                                    <div class="flex justify-between items-start mb-3">
                                                        <div>
                                                            <h5 class="font-medium text-gray-900">Quote from <span x-text="rfq.vendor?.store_name"></span></h5>
                                                            <p class="text-sm text-gray-500">
                                                                Submitted on <span x-text="new Date(quote.created_at).toLocaleDateString()"></span>
                                                            </p>
                                                        </div>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                            Quoted
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                        <div>
                                                            <p class="text-gray-500">Unit Price</p>
                                                            <p class="font-medium text-lg text-green-600">
                                                                <span x-text="new Intl.NumberFormat('en-US', { style: 'currency', currency: quote.currency }).format(quote.unit_price)"></span>
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p class="text-gray-500">MOQ</p>
                                                            <p class="font-medium">
                                                                <span x-text="new Intl.NumberFormat().format(quote.moq)"></span> units
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p class="text-gray-500">Lead Time</p>
                                                            <p class="font-medium" x-text="`${quote.lead_time_days} days`"></p>
                                                        </div>
                                                        <div>
                                                            <p class="text-gray-500">Shipping Terms</p>
                                                            <p class="font-medium" x-text="quote.shipping_terms"></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <template x-if="quote.vendor_notes">
                                                        <div class="mt-4 pt-4 border-t">
                                                            <p class="text-gray-500 text-sm mb-2">Vendor Notes:</p>
                                                            <p class="text-gray-700" x-text="quote.vendor_notes"></p>
                                                        </div>
                                                    </template>
                                                    
                                                    <div class="mt-4 pt-4 border-t flex justify-end space-x-3">
                                                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                                            <i class="fas fa-check mr-2"></i> Accept Quote
                                                        </button>
                                                        <a :href="`/rfq/${rfq.id}/chat`" 
                                                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                                            <i class="fas fa-comment mr-2"></i> Chat with Vendor
                                                        </a>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="quotes.length === 0 && rfq.status === 'pending'">
                                    <div class="text-center py-8 bg-gray-50 rounded-lg">
                                        <i class="fas fa-clock text-3xl text-yellow-500 mb-3"></i>
                                        <h5 class="font-medium text-gray-900 mb-2">Waiting for Vendor Quote</h5>
                                        <p class="text-gray-500">The vendor has been notified and will submit a quote soon.</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between">
                                <div class="text-sm text-gray-500">
                                    Need help? <a href="#" class="text-blue-600 hover:text-blue-800">Contact Support</a>
                                </div>
                                <div class="space-x-3">
                                    <button x-on:click="show = false" 
                                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                        Close
                                    </button>
                                    <a :href="`/rfq/${rfq.id}/chat`" 
                                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                        <i class="fas fa-comment mr-2"></i> Chat with Vendor
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Alpine.js if not already included -->
    <script src="//unpkg.com/alpinejs" defer></script>
</div>