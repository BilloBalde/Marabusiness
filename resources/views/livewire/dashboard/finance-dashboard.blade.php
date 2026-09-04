<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistics Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getStatsOverview() as $stat)
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="p-2 rounded-full {{ $stat->getColor() === 'success' ? 'bg-green-100' : ($stat->getColor() === 'primary' ? 'bg-blue-100' : 'bg-yellow-100') }}">
                                @if($stat->getIcon() === 'heroicon-o-currency-dollar')
                                    <svg class="w-6 h-6 {{ $stat->getColor() === 'success' ? 'text-green-600' : ($stat->getColor() === 'primary' ? 'text-blue-600' : 'text-yellow-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($stat->getIcon() === 'heroicon-o-banknotes')
                                    <svg class="w-6 h-6 {{ $stat->getColor() === 'success' ? 'text-green-600' : ($stat->getColor() === 'primary' ? 'text-blue-600' : 'text-yellow-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($stat->getIcon() === 'heroicon-o-credit-card')
                                    <svg class="w-6 h-6 {{ $stat->getColor() === 'success' ? 'text-green-600' : ($stat->getColor() === 'primary' ? 'text-blue-600' : 'text-yellow-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 {{ $stat->getColor() === 'success' ? 'text-green-600' : ($stat->getColor() === 'primary' ? 'text-blue-600' : 'text-yellow-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                @endif
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">{{ $stat->getLabel() }}</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $stat->getValue() }}</p>
                            <p class="text-xs text-gray-500">{{ $stat->getDescription() }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Date Range and Filters --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex flex-wrap gap-4 items-center">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <select wire:model.live="dateRange" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                
                @if($dateRange === 'custom')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" wire:model.live="startDate" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" wire:model.live="endDate" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                @endif
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Vendor</label>
                    <select wire:model.live="selectedVendorId" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Vendors</option>
                        @foreach(\App\Models\Vendor::all() as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Commission Settings --}}
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Commission Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Commission Type</label>
                    <select wire:model="commissionType" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Commission Rate
                        @if($commissionType === 'percentage')
                            (%)
                        @else
                            (Fixed Amount)
                        @endif
                    </label>
                    <input type="number" wire:model="commissionRate" step="0.01" min="0" 
                           class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                </div>
            </div>
            <div class="mt-4">
                <button wire:click="saveCommissionSettings" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save Commission Settings
                </button>
            </div>
        </div>

        {{-- Pending Payouts --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Pending Payouts</h3>
                <span class="text-sm text-gray-500">
                    {{ $this->getVendorsWithBalance()->count() }} vendors with pending balance
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway Fees</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wire Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->getVendorsWithBalance() as $payout)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $payout['vendor']->store_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $payout['vendor']->user->email }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $this->formatCurrency($payout['total_amount']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $this->formatCurrency($payout['commission']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $this->formatCurrency($payout['gateway_fees']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $this->formatCurrency($payout['wire_fee']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600">
                                    {{ $this->formatCurrency($payout['net_amount']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    <button wire:click="initiatePayout({{ $payout['vendor']->id }})" 
                                            class="text-blue-600 hover:text-blue-900">
                                        Initiate Payout
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No pending payouts at this time.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Payouts --}}
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Payouts</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->getRecentPayouts() as $payout)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payout->created_at->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $payout->vendor->store_name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payout->reference_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    @if($payout->payout_method === 'bank_wire')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Bank Wire
                                        </span>
                                    @elseif($payout->payout_method === 'orange_money')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            Orange Money
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Stripe Transfer
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $this->formatCurrency($payout->net_amount) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($payout->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Completed
                                        </span>
                                    @elseif($payout->status === 'processing')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Processing
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    @if($payout->status === 'pending')
                                        <button wire:click="completePayout({{ $payout->id }})" 
                                                class="text-green-600 hover:text-green-900">
                                            Mark as Completed
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Payout Modal --}}
    @if($showingPayoutModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50">
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">Initiate Payout</h3>
                            
                            @php
                                $vendor = \App\Models\Vendor::find($payoutVendorId);
                                $pendingData = $this->getVendorsWithBalance()->firstWhere('vendor.id', $payoutVendorId);
                            @endphp
                            
                            @if($vendor && $pendingData)
                                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-2">{{ $vendor->store_name }}</h4>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div class="text-gray-600">Total Amount:</div>
                                        <div class="font-medium">{{ $this->formatCurrency($pendingData['total_amount']) }}</div>
                                        
                                        <div class="text-gray-600">Commission:</div>
                                        <div class="text-red-600">-{{ $this->formatCurrency($pendingData['commission']) }}</div>
                                        
                                        <div class="text-gray-600">Gateway Fees:</div>
                                        <div class="text-red-600">-{{ $this->formatCurrency($pendingData['gateway_fees']) }}</div>
                                        
                                        <div class="text-gray-600">Wire Fee:</div>
                                        <div class="text-red-600">-{{ $this->formatCurrency($pendingData['wire_fee']) }}</div>
                                        
                                        <div class="text-gray-600 font-medium">Net Payout:</div>
                                        <div class="font-bold text-green-600">{{ $this->formatCurrency($pendingData['net_amount']) }}</div>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Payout Method</label>
                                        <select wire:model="payoutMethod" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full">
                                            <option value="bank_wire">Bank Wire Transfer</option>
                                            <option value="orange_money">Orange Money</option>
                                            <option value="stripe_transfer">Stripe Transfer</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                        <textarea wire:model="payoutNotes" rows="3" 
                                                  class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full"></textarea>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button wire:click="processPayout" type="button" 
                                    class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                                Confirm Payout
                            </button>
                            <button wire:click="$set('showingPayoutModal', false)" type="button" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>