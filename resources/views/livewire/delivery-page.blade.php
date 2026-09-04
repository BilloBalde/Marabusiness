<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.delivery', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/delivery-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Informations de <span class="text-gray-900">Livraison</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Découvrez nos options de livraison rapide et fiable à travers l'Afrique
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    <!-- Last Updated Badge -->
    <div class="flex justify-end mb-8">
        <div class="bg-gray-100 px-4 py-2 rounded-full text-sm text-gray-600 inline-flex items-center gap-2">
            <i class="far fa-calendar-alt text-[#D4AF37]"></i>
            Dernière mise à jour : {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <!-- Key Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-16">
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm">
            <div class="text-3xl font-bold text-[#D4AF37] mb-2">3-7</div>
            <div class="text-sm font-semibold text-gray-900 mb-1">jours</div>
            <p class="text-xs text-gray-600">Délai de livraison</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm">
            <div class="text-3xl font-bold text-[#D4AF37] mb-2">17</div>
            <div class="text-sm font-semibold text-gray-900 mb-1">pays</div>
            <p class="text-xs text-gray-600">desservis</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm">
            <div class="text-3xl font-bold text-[#D4AF37] mb-2">Gratuite</div>
            <div class="text-sm font-semibold text-gray-900 mb-1">dès 50k</div>
            <p class="text-xs text-gray-600">livraison offerte</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm">
            <div class="text-3xl font-bold text-[#D4AF37] mb-2">150+</div>
            <div class="text-sm font-semibold text-gray-900 mb-1">points</div>
            <p class="text-xs text-gray-600">relais</p>
        </div>
    </div>

    <!-- Quick Navigation Pills -->
    <div class="flex flex-wrap justify-center gap-3 mb-12">
        <a href="#options" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Options
        </a>
        <a href="#delais" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Délais
        </a>
        <a href="#tarifs" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Tarifs
        </a>
        <a href="#zones" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Zones
        </a>
        <a href="#suivi" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Suivi
        </a>
        <a href="#emballage" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Emballage
        </a>
        <a href="#problemes" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Problèmes
        </a>
    </div>

    <!-- Main Content - Single Column -->
    <div class="max-w-4xl mx-auto space-y-8 mb-16">
        
        <!-- Section 1: Options de livraison -->
        <div id="options" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">1</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Options de livraison</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-home text-[#D4AF37] text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Livraison à domicile</h3>
                    <p class="text-sm text-gray-600">Livraison directement à votre adresse.</p>
                    <p class="text-xs text-[#D4AF37] mt-2">3-7 jours ouvrés</p>
                </div>
                
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-store text-[#D4AF37] text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Point relais</h3>
                    <p class="text-sm text-gray-600">Retrait dans 150 points relais.</p>
                    <p class="text-xs text-[#D4AF37] mt-2">2-5 jours ouvrés</p>
                </div>
                
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-bolt text-[#D4AF37] text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Livraison express</h3>
                    <p class="text-sm text-gray-600">Pour les commandes urgentes.</p>
                    <p class="text-xs text-[#D4AF37] mt-2">24-48h (grandes villes)</p>
                </div>
            </div>
        </div>

        <!-- Section 2: Délais par zone -->
        <div id="delais" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">2</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Délais par zone</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Zone</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Domicile</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Point relais</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Express</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="px-4 py-3 font-medium">Dakar et banlieue</td>
                            <td class="px-4 py-3">2-3 jours</td>
                            <td class="px-4 py-3">1-2 jours</td>
                            <td class="px-4 py-3 text-[#D4AF37] font-medium">24h</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Autres régions Sénégal</td>
                            <td class="px-4 py-3">3-5 jours</td>
                            <td class="px-4 py-3">3-4 jours</td>
                            <td class="px-4 py-3 text-[#D4AF37] font-medium">2-3 jours</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Afrique de l'Ouest</td>
                            <td class="px-4 py-3">5-7 jours</td>
                            <td class="px-4 py-3">5-6 jours</td>
                            <td class="px-4 py-3 text-[#D4AF37] font-medium">3-4 jours</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Afrique Centrale</td>
                            <td class="px-4 py-3">7-10 jours</td>
                            <td class="px-4 py-3">7-9 jours</td>
                            <td class="px-4 py-3 text-[#D4AF37] font-medium">5-7 jours</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">International</td>
                            <td class="px-4 py-3">10-15 jours</td>
                            <td class="px-4 py-3">-</td>
                            <td class="px-4 py-3 text-[#D4AF37] font-medium">7-10 jours</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <p class="text-xs text-gray-500 mt-2">
                * Les délais sont donnés à titre indicatif.
            </p>
        </div>

        <!-- Section 3: Tarifs de livraison -->
        <div id="tarifs" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">3</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Tarifs de livraison</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-5 mb-4">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-gift text-[#D4AF37] mr-2"></i>
                            Livraison gratuite
                        </h3>
                        <p class="text-sm text-gray-700">
                            Dès <span class="font-bold text-[#D4AF37]">50 000 FCFA</span> d'achat
                        </p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Dakar (domicile)</span>
                            <span class="font-medium">2 500 FCFA</span>
                        </div>
                        <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Dakar (point relais)</span>
                            <span class="font-medium">1 500 FCFA</span>
                        </div>
                        <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Régions Sénégal</span>
                            <span class="font-medium">3 500 FCFA</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-4">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-bolt text-[#D4AF37] mr-2"></i>
                            Tarifs express
                        </h3>
                        <p class="text-sm text-gray-700">Livraison prioritaire</p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Dakar express</span>
                            <span class="font-medium text-[#D4AF37]">5 000 FCFA</span>
                        </div>
                        <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Régions express</span>
                            <span class="font-medium text-[#D4AF37]">7 500 FCFA</span>
                        </div>
                        <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">International express</span>
                            <span class="font-medium text-[#D4AF37]">25k - 40k FCFA</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700 mt-4">
                <i class="fas fa-info-circle mr-2"></i>
                Tarifs calculés automatiquement dans votre panier.
            </div>
        </div>

        <!-- Section 4: Zones desservies -->
        <div id="zones" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">4</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Zones desservies</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="border border-gray-200 rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Sénégal</h3>
                    <p class="text-sm text-gray-600">Dakar, Thiès, Saint-Louis, Kaolack, Ziguinchor</p>
                </div>
                
                <div class="border border-gray-200 rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Afrique de l'Ouest</h3>
                    <p class="text-sm text-gray-600">Mali, Côte d'Ivoire, Burkina, Guinée, Mauritanie</p>
                </div>
                
                <div class="border border-gray-200 rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">International</h3>
                    <p class="text-sm text-gray-600">France, Belgique, Canada, USA, UK, Allemagne</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-500 mt-4">
                <i class="fas fa-map-marker-alt text-[#D4AF37] mr-2"></i>
                Contactez-nous pour d'autres destinations.
            </p>
        </div>

        <!-- Section 5: Suivi de commande -->
        <div id="suivi" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">5</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Suivi de commande</h2>
            </div>
            
            <div class="space-y-6">
                <p class="text-gray-700">
                    Un email avec numéro de suivi vous est envoyé dès l'expédition.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" 
                           placeholder="Votre numéro de suivi" 
                           class="flex-1 px-4 py-3 border border-gray-200 rounded-xl focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition">
                    <button class="px-6 py-3 bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold rounded-xl transition">
                        <i class="fas fa-search mr-2"></i>
                        Suivre
                    </button>
                </div>
                
                <div class="bg-gray-50 p-5 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-3">Étapes de suivi</h3>
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div>
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center mx-auto mb-2 text-white font-bold">1</div>
                            <p class="text-xs">Confirmée</p>
                        </div>
                        <div>
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-500 font-bold">2</div>
                            <p class="text-xs">Préparation</p>
                        </div>
                        <div>
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-500 font-bold">3</div>
                            <p class="text-xs">Expédiée</p>
                        </div>
                        <div>
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-500 font-bold">4</div>
                            <p class="text-xs">Livrée</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Emballage -->
        <div id="emballage" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">6</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Emballage</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <ul class="space-y-2">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-600">Emballages robustes</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-600">Protection anti-chocs</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-600">Matériaux recyclables</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 p-5 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-2">Emballage cadeau</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        +1 000 FCFA - Message personnalisé inclus
                    </p>
                    <i class="fas fa-gift text-[#D4AF37]"></i>
                </div>
            </div>
        </div>

        <!-- Section 7: Problèmes de livraison -->
        <div id="problemes" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">7</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Problèmes de livraison</h2>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-clock text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-600">Retard : contactez-nous après +3 jours</span>
                </div>
                
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-box-open text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-600">Colis endommagé : refusez ou contactez-nous sous 48h</span>
                </div>
                
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-question-circle text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-600">Colis non reçu : vérifiez le suivi et contactez-nous</span>
                </div>
                
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-exchange-alt text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-600">Adresse incorrecte : contactez-nous immédiatement</span>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <i class="fas fa-phone-alt mr-2"></i>
                Assistance livraison : +221 78 123 45 67
            </div>
        </div>

        <!-- Section 8: FAQ Rapide -->
        <div id="faq" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">8</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">FAQ Livraison</h2>
            </div>
            
            <div class="space-y-3">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="font-semibold text-gray-900 mb-1">Puis-je modifier mon adresse de livraison ?</p>
                    <p class="text-sm text-gray-600">Oui, contactez-nous rapidement avant expédition.</p>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="font-semibold text-gray-900 mb-1">Que faire si je ne suis pas chez moi ?</p>
                    <p class="text-sm text-gray-600">Le livreur vous contactera ou déposera en point relais.</p>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="font-semibold text-gray-900 mb-1">Les frais de livraison sont-ils remboursables ?</p>
                    <p class="text-sm text-gray-600">Oui, en cas de retour pour défaut ou erreur de notre part.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Besoin d'aide avec votre livraison ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Notre équipe est là pour répondre à toutes vos questions
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-headset"></i>
                Contacter le support
            </a>
            <a href="/track-order" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-search"></i>
                Suivre ma commande
            </a>
        </div>
    </div>
</div>

<!-- Add to your CSS -->
<style>
    html {
        scroll-behavior: smooth;
    }
    
    .scroll-mt-24 {
        scroll-margin-top: 6rem;
    }
    
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .bg-gradient-to-r {
        background-size: 200% auto;
        animation: gradientShift 3s ease infinite;
    }
</style>