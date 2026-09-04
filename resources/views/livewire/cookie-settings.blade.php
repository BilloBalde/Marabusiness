<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.cookies', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    @if($message)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            {{ $message }}
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] px-8 py-12 text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Gestion des cookies</h1>
            <p class="text-lg opacity-90 max-w-2xl">
                Personnalisez vos préférences de cookies. Vous pouvez modifier vos choix à tout moment.
            </p>
        </div>

        <!-- Settings Panel -->
        <div class="p-8">
            <div class="space-y-6">
                @foreach($categories as $key => $category)
                    <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    @switch($key)
                                        @case('essential')
                                            <i class="fas fa-shield-alt text-[#D4AF37] text-xl"></i>
                                            @break
                                        @case('functional')
                                            <i class="fas fa-sliders-h text-[#D4AF37] text-xl"></i>
                                            @break
                                        @case('analytics')
                                            <i class="fas fa-chart-line text-[#D4AF37] text-xl"></i>
                                            @break
                                        @case('marketing')
                                            <i class="fas fa-ad text-[#D4AF37] text-xl"></i>
                                            @break
                                    @endswitch
                                    <h3 class="text-xl font-semibold text-gray-900">{{ $category['title'] }}</h3>
                                </div>
                                <p class="text-gray-600 mb-4">{{ $category['description'] }}</p>
                                
                                <!-- Cookie List -->
                                <details class="text-sm">
                                    <summary class="text-[#D4AF37] cursor-pointer hover:underline">
                                        Voir la liste des cookies
                                    </summary>
                                    <div class="mt-4 overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left">Cookie</th>
                                                    <th class="px-4 py-2 text-left">Objectif</th>
                                                    <th class="px-4 py-2 text-left">Durée</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach($category['cookies'] as $cookie)
                                                    <tr>
                                                        <td class="px-4 py-2 font-mono text-xs">{{ $cookie['name'] }}</td>
                                                        <td class="px-4 py-2">{{ $cookie['purpose'] }}</td>
                                                        <td class="px-4 py-2">{{ $cookie['duration'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </div>
                            
                            <div class="ml-6">
                                @if($category['always_active'])
                                    <span class="px-4 py-2 bg-gray-100 text-gray-500 rounded-full text-sm">
                                        Toujours actif
                                    </span>
                                @else
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="sr-only peer"
                                               wire:model.live="preferences.{{ $key }}">
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#D4AF37]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-[#D4AF37]"></div>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-end pt-6 border-t border-gray-200">
                    <button wire:click="acceptAll" 
                            class="px-8 py-3 border-2 border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white font-semibold rounded-xl transition">
                        Tout accepter
                    </button>
                    <button wire:click="save" 
                            class="px-8 py-3 bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold rounded-xl transition shadow-lg">
                        Enregistrer mes préférences
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>