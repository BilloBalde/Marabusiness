<div>
    <!-- RFQ Button -->
    <button 
        wire:click="openRfqModal"
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition"
        title="Request wholesale quotation"
    >
        <i class="fas fa-file-invoice-dollar mr-2"></i>
        Request Wholesale Quote
    </button>
    
    <!-- RFQ Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b p-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-file-invoice-dollar text-blue-600 mr-2"></i>
                            Request for Quotation
                        </h2>
                        <button 
                            wire:click="$set('showModal', false)"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    <p class="text-gray-600 mt-2">
                        Send a wholesale quote request to the vendor
                    </p>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6">
                    <form wire:submit.prevent="submitRfq">
                        <!-- Quantity -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Quantity Needed *
                            </label>
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="number"
                                    wire:model="quantity"
                                    min="1"
                                    class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., 100"
                                >
                                <span class="text-gray-500">units</span>
                            </div>
                            @error('quantity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Target Price (Optional) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Target Price (Optional)
                            </label>
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="number"
                                    wire:model="target_price"
                                    min="0"
                                    step="0.01"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="e.g., 25.50"
                                >
                                <select 
                                    wire:model="currency"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GNF">GNF</option>
                                    <!-- Add more currencies -->
                                </select>
                            </div>
                            @error('target_price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <p class="text-gray-500 text-xs mt-1">
                                Your desired price per unit. Vendor may propose a different price.
                            </p>
                        </div>
                        
                        <!-- Shipping Information -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-shipping-fast mr-2"></i>
                                Shipping Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Country -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Country *
                                    </label>
                                    <input 
                                        type="text"
                                        wire:model="shipping_country"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., Guinea"
                                    >
                                    @error('shipping_country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- City -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input 
                                        type="text"
                                        wire:model="shipping_city"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., Conakry"
                                    >
                                    @error('shipping_city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Port -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Port/Airport
                                    </label>
                                    <input 
                                        type="text"
                                        wire:model="shipping_port"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., Port of Conakry"
                                    >
                                    @error('shipping_port') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customization -->
                        <div class="mb-6">
                            <div class="flex items-center mb-4">
                                <input 
                                    type="checkbox"
                                    wire:model="needs_customization"
                                    id="needs_customization"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label for="needs_customization" class="ml-2 text-sm font-medium text-gray-700">
                                    I need product customization (logo, packaging, specifications, etc.)
                                </label>
                            </div>
                            
                            @if($needs_customization)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Customization Notes
                                </label>
                                <textarea 
                                    wire:model="customization_notes"
                                    rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Describe your customization requirements..."
                                ></textarea>
                                @error('customization_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @endif
                        </div>
                        
                        <!-- Additional Notes -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Additional Notes (Optional)
                            </label>
                            <textarea 
                                wire:model="additional_notes"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Any other requirements, questions, or special instructions..."
                            ></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <button 
                                type="button"
                                wire:click="$set('showModal', false)"
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold flex items-center"
                                wire:loading.attr="disabled"
                            >
                                <i class="fas fa-paper-plane mr-2"></i>
                                <span wire:loading.remove>Submit RFQ</span>
                                <span wire:loading>
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Submitting...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Success Message -->
    @if(session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
             class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif
</div>