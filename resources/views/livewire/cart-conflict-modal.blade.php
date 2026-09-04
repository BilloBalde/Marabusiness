<div>
    @if($show)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    Cart Conflict
                </h3>
                
                <div class="mt-2">
                    <p class="text-sm text-gray-500">{{ $message }}</p>
                </div>
                
                <div class="mt-6 flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-2">
                    <button 
                        wire:click="closeModal"
                        type="button" 
                        class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    
                    <button 
                        wire:click="goToCart"
                        type="button" 
                        class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Go to Cart
                    </button>
                    
                    <button 
                        wire:click="removeExistingItem"
                        type="button" 
                        class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Remove Existing Item
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>