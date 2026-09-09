<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.buyer-protection', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/protection-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Protection des <span class="text-gray-900">Acheteurs</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Achetez en toute confiance sur MARA BUSINESS
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

    <!-- Key Stats / Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-16">
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1">100%</h3>
            <p class="text-sm text-gray-600">Achats protégés</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-undo-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1">30</h3>
            <p class="text-sm text-gray-600">Jours pour retourner</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-clock text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1">48h</h3>
            <p class="text-sm text-gray-600">Traitement des litiges</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-euro-sign text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-1">500k</h3>
            <p class="text-sm text-gray-600">Plafond de protection</p>
        </div>
    </div>

    <!-- Trust Badges / Garanties -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-3xl p-8 mb-16 border border-green-200">
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Nos garanties exclusives</h2>
            <p class="text-gray-600">Des garanties conçues pour vous protéger à chaque étape de votre achat</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 text-center shadow-md">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-double text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Garantie de conformité</h3>
                <p class="text-sm text-gray-600">Le produit reçu doit être identique à sa description. Sinon, vous êtes remboursé.</p>
            </div>
            
            <div class="bg-white rounded-xl p-6 text-center shadow-md">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-truck text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Garantie de livraison</h3>
                <p class="text-sm text-gray-600">Si votre colis n'arrive pas, nous vous remboursons intégralement.</p>
            </div>
            
            <div class="bg-white rounded-xl p-6 text-center shadow-md">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-undo-alt text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Garantie de retour</h3>
                <p class="text-sm text-gray-600">Vous disposez de 30 jours pour retourner un article qui ne vous satisfait pas.</p>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-16">
        <a href="#couverture" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-shield-alt text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Couverture</span>
        </a>
        
        <a href="#conditions" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-clipboard-check text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Conditions</span>
        </a>
        
        <a href="#litiges" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-gavel text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Litiges</span>
        </a>
        
        <a href="#remboursement" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-money-bill-wave text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Remboursement</span>
        </a>
        
        <a href="#faq" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-question-circle text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">FAQ</span>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
        <!-- Table of Contents - Sticky Sidebar -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-[#D4AF37]"></i>
                    Sommaire
                </h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="#couverture" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            1. Ce qui est couvert
                        </a>
                    </li>
                    <li>
                        <a href="#conditions" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            2. Conditions d'éligibilité
                        </a>
                    </li>
                    <li>
                        <a href="#litiges" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            3. Processus de litige
                        </a>
                    </li>
                    <li>
                        <a href="#remboursement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            4. Remboursement
                        </a>
                    </li>
                    <li>
                        <a href="#delais" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            5. Délais de traitement
                        </a>
                    </li>
                    <li>
                        <a href="#exclusions" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            6. Exclusions
                        </a>
                    </li>
                    <li>
                        <a href="#mediation" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            7. Médiation
                        </a>
                    </li>
                    <li>
                        <a href="#faq" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            8. FAQ
                        </a>
                    </li>
                </ul>
                
                <!-- Open Dispute Button -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="/contact" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#D4AF37] hover:bg-[#c9a12f] text-white rounded-xl transition text-sm font-medium">
                        <i class="fas fa-gavel"></i>
                        Ouvrir un litige
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Section 1: Ce qui est couvert -->
            <div id="couverture" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">1</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Ce qui est couvert par notre protection</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-box-open text-green-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">Article non reçu</h3>
                        </div>
                        <p class="text-sm text-gray-600">Si votre colis n'arrive jamais ou dépasse le délai de livraison maximum.</p>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-green-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">Article non conforme</h3>
                        </div>
                        <p class="text-sm text-gray-600">Si le produit reçu est différent de sa description (mauvaise taille, couleur, modèle).</p>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-tools text-green-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">Article défectueux</h3>
                        </div>
                        <p class="text-sm text-gray-600">Si le produit est endommagé ou ne fonctionne pas correctement.</p>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-fake text-green-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">Article contrefait</h3>
                        </div>
                        <p class="text-sm text-gray-600">Si vous recevez un produit qui n'est pas authentique.</p>
                    </div>
                </div>
                
                <div class="mt-4 bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="font-semibold">Plafond de protection :</span> Jusqu'à 500 000 FCFA par achat.
                </div>
            </div>

            <!-- Section 2: Conditions d'éligibilité -->
            <div id="conditions" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">2</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Conditions d'éligibilité</h2>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Conditions requises
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-1"></i>
                                    <span>Avoir effectué l'achat sur MARA BUSINESS</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-1"></i>
                                    <span>Respecter les délais de réclamation</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-1"></i>
                                    <span>Fournir les preuves demandées</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-1"></i>
                                    <span>Avoir tenté de contacter le vendeur</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-clock text-[#D4AF37] mr-2"></i>
                                Délais à respecter
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600">Article non reçu</span>
                                    <span class="font-medium text-gray-900">15 jours après date prévue</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600">Article non conforme</span>
                                    <span class="font-medium text-gray-900">30 jours après réception</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-600">Article défectueux</span>
                                    <span class="font-medium text-gray-900">30 jours après réception</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Processus de litige -->
            <div id="litiges" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">3</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Processus de litige</h2>
                </div>
                
                <div class="space-y-6">
                    <div class="relative">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">1</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Contactez le vendeur</h3>
                                <p class="text-sm text-gray-600">Tentez de résoudre le problème directement avec le vendeur via votre espace client.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">2</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Ouvrez un litige</h3>
                                <p class="text-sm text-gray-600">Si aucun accord n'est trouvé, ouvrez un litige depuis votre espace client.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">3</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Fournissez les preuves</h3>
                                <p class="text-sm text-gray-600">Photos du produit, captures d'écran, numéro de suivi, etc.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">4</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Notre équipe analyse</h3>
                                <p class="text-sm text-gray-600">Nous examinons les éléments fournis par les deux parties.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">5</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Décision finale</h3>
                                <p class="text-sm text-gray-600">Nous tranchons en votre faveur ou en faveur du vendeur et procédons au remboursement si nécessaire.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Dans 90% des cas, le litige est résolu en moins de 48h.
                    </div>
                </div>
            </div>

            <!-- Section 4: Remboursement -->
            <div id="remboursement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">4</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Modalités de remboursement</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Montant remboursé</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-700">Prix du produit</span>
                                <span class="font-bold text-gray-900">100%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-700">Frais de livraison</span>
                                <span class="font-bold text-gray-900">100% (produit défectueux)</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-700">Frais de retour</span>
                                <span class="font-bold text-gray-900">Pris en charge (défaut)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Délais selon le mode de paiement</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 border-b border-gray-200">
                                <span class="text-sm text-gray-600">Carte bancaire</span>
                                <span class="text-sm font-medium">3-5 jours ouvrés</span>
                            </div>
                            <div class="flex items-center justify-between p-2 border-b border-gray-200">
                                <span class="text-sm text-gray-600">Orange Money / Wave</span>
                                <span class="text-sm font-medium">24-48h</span>
                            </div>
                            <div class="flex items-center justify-between p-2 border-b border-gray-200">
                                <span class="text-sm text-gray-600">PayPal</span>
                                <span class="text-sm font-medium">24-48h</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Délais de traitement -->
            <div id="delais" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">5</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Délais de traitement</h2>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="text-3xl font-bold text-[#D4AF37] mb-1">24h</div>
                            <p class="text-sm text-gray-600">Accusé de réception</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="text-3xl font-bold text-[#D4AF37] mb-1">48h</div>
                            <p class="text-sm text-gray-600">Analyse du dossier</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="text-3xl font-bold text-[#D4AF37] mb-1">72h</div>
                            <p class="text-sm text-gray-600">Décision finale</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Litige complexe (nécessitant une enquête approfondie)</span>
                            <span class="font-bold text-gray-900">5-7 jours</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Exclusions -->
            <div id="exclusions" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">6</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Exclusions de garantie</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <ul class="space-y-2 text-sm text-red-700">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle mt-1"></i>
                                <span>Achat en dehors de la plateforme</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle mt-1"></i>
                                <span>Produits personnalisés / sur mesure</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle mt-1"></i>
                                <span>Dommages causés par une mauvaise utilisation</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <ul class="space-y-2 text-sm text-red-700">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle mt-1"></i>
                                <span>Usure normale du produit</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle mt-1"></i>
                                <span>Produits périssables (aliments, fleurs)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle mt-1"></i>
                                <span>Cartes cadeaux et codes numériques</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 7: Médiation -->
            <div id="mediation" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">7</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Médiation</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Si notre décision ne vous satisfait pas, vous pouvez recourir à un service de médiation indépendant :
                    </p>
                    
                    <div class="bg-gray-50 p-5 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Médiateur du commerce</h3>
                        <p class="text-sm text-gray-600 mb-3">Service gratuit de résolution des litiges de consommation</p>
                        <div class="space-y-1 text-sm">
                            <p><span class="font-medium">Site web :</span> www.mediateur-commerce.sn</p>
                            <p><span class="font-medium">Email :</span> contact@mediateur-commerce.sn</p>
                            <p><span class="font-medium">Tél :</span> +221 33 123 45 67</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 8: FAQ -->
            <div id="faq" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">8</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">FAQ - Protection acheteur</h2>
                </div>
                
                <div class="space-y-4">
                    <!-- FAQ Item 1 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">La protection est-elle automatique ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Oui, tous les achats effectués sur MARA BUSINESS sont automatiquement couverts par notre protection acheteur, sans frais supplémentaires.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 2 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Que faire si le vendeur ne répond pas ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Si le vendeur ne répond pas sous 48h, vous pouvez ouvrir un litige directement. Notre équipe prendra le relais.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 3 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Puis-je être remboursé si j'ai changé d'avis ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Oui, notre politique de retour vous permet de retourner un article dans les 30 jours, même si vous avez simplement changé d'avis. Les frais de retour sont à votre charge dans ce cas.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 4 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Y a-t-il un montant minimum ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Non, la protection s'applique à tous les achats, quel que soit leur montant, jusqu'à 500 000 FCFA.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
                <p>Notre objectif : vous offrir une expérience d'achat sereine et sans risque.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Un problème avec votre commande ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Notre équipe est là pour vous aider à résoudre tout litige rapidement
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-headset"></i>
                Contacter le support
            </a>
            <a href="#litiges" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-gavel"></i>
                Ouvrir un litige
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // FAQ Accordion functionality
        document.querySelectorAll('#faq .border-gray-200 button').forEach(button => {
            button.addEventListener('click', () => {
                const item = button.closest('.border-gray-200');
                const content = item.querySelector('div:last-child');
                
                // Toggle active class
                item.classList.toggle('active');
                
                // Toggle content visibility
                if (content.style.display === 'none' || !content.style.display) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });
        });
    });
</script>
</div>
