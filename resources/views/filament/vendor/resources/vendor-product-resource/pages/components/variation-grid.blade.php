@props(['combinations', 'statePath'])

<div x-data="{
    combinations: @entangle($statePath).defer,
    updateVariation(index, field, value) {
        if (!this.combinations[index]) this.combinations[index] = {};
        this.combinations[index][field] = value;
    }
}">
    <div class="space-y-4">
        <template x-for="(combo, index) in combinations" :key="index">
            <div class="border rounded-lg p-4">
                <div class="flex justify-between mb-3">
                    <div>
                        <template x-for="(value, attr) in combo.attributes">
                            <span class="inline-block bg-gray-100 px-2 py-1 rounded text-sm mr-2">
                                <span x-text="attr" class="font-semibold"></span>: 
                                <span x-text="value"></span>
                            </span>
                        </template>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" step="0.01"
                            x-model="combo.price"
                            @input="updateVariation(index, 'price', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number"
                            x-model="combo.stock"
                            @input="updateVariation(index, 'stock', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text"
                            x-model="combo.sku"
                            @input="updateVariation(index, 'sku', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Auto-generate">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Image</label>
                        <input type="file"
                            @change="updateVariation(index, 'image', $event.target.files[0])"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>