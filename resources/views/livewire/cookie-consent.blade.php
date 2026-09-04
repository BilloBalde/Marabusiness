<div x-data="{ 
    show: {{ $showCookieBanner ? 'true' : 'false' }},
    showDetails: @entangle('showDetails')
}" 
     x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-full opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="translate-y-full opacity-0"
     class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6"
     style="display: none;"
     @close-banner.window="show = false">

    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
            <!-- Main Banner -->
            <div class="p-6 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-cookie-bite text-[#D4AF37] text-2xl"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            🍪 Nous utilisons des cookies
                        </h3>
                        <p class="text-sm text-gray-600">
                            Pour améliorer votre expérience sur MARA BUSINESS, nous utilisons des cookies. 
                            Certains sont nécessaires au fonctionnement du site, d'autres nous aident à l'améliorer.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        <button wire:click="acceptEssential" 
                                class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition text-sm">
                            Essentiels uniquement
                        </button>
                        <button wire:click="toggleDetails" 
                                class="px-6 py-2.5 border-2 border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white font-semibold rounded-lg transition text-sm">
                            <i class="fas fa-cog mr-2"></i>
                            Personnaliser
                        </button>
                        <button wire:click="acceptAll" 
                                class="px-6 py-2.5 bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold rounded-lg transition text-sm whitespace-nowrap">
                            Accepter tous
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detailed Settings Panel -->
            <div x-show="showDetails" 
                 x-collapse
                 class="border-t border-gray-200 bg-gray-50 p-6">
                
                <div class="space-y-6">
                    <p class="text-sm text-gray-600">
                        Personnalisez vos préférences de cookies. Les cookies essentiels sont toujours actifs car ils sont nécessaires au fonctionnement du site.
                    </p>

                    @foreach($categories as $key => $category)
                        <div class="bg-white rounded-xl p-4 border border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $category['title'] }}</h4>
                                    <p class="text-xs text-gray-500">{{ $category['description'] }}</p>
                                </div>
                                
                                @if($category['always_active'])
                                    <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">
                                        Toujours actif
                                    </span>
                                @else
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="sr-only peer"
                                               wire:model="preferences.{{ $key }}">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#D4AF37]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#D4AF37]"></div>
                                    </label>
                                @endif
                            </div>

                            <!-- Cookie List -->
                            <div class="mt-3 text-sm">
                                <button @click="$el.nextElementSibling.classList.toggle('hidden')" 
                                        class="text-[#D4AF37] hover:text-[#c9a12f] text-xs flex items-center gap-1">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                    Voir les cookies utilisés
                                </button>
                                <div class="hidden mt-2">
                                    <table class="w-full text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Cookie</th>
                                                <th class="px-3 py-2 text-left">Objectif</th>
                                                <th class="px-3 py-2 text-left">Durée</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($category['cookies'] as $cookie)
                                                <tr>
                                                    <td class="px-3 py-2 font-mono">{{ $cookie['name'] }}</td>
                                                    <td class="px-3 py-2">{{ $cookie['purpose'] }}</td>
                                                    <td class="px-3 py-2">{{ $cookie['duration'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button wire:click="acceptEssential" 
                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                            Refuser tous
                        </button>
                        <button wire:click="save" 
                                class="px-6 py-2 bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold rounded-lg transition text-sm">
                            Enregistrer mes préférences
                        </button>
                    </div>

                    <p class="text-xs text-gray-400 text-center">
                        En cliquant sur "Enregistrer", vous acceptez l'utilisation des cookies selon vos préférences.
                        Vous pouvez modifier vos choix à tout moment depuis la <a href="/cookies" class="text-[#D4AF37] underline">page des cookies</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>