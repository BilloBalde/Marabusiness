<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<!-- Remove padding from main container, add it to content sections instead -->
<div class="w-full max-w-[90rem] px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.security', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    

    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/security-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Centre de <span class="text-gray-900">Sécurité</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Protégez vos données et achetez en toute confiance sur MARA BUSINESS
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    <!-- Key Stats / Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-16">
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">256-bit</h3>
            <p class="text-sm text-gray-600">Chiffrement SSL</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">100%</h3>
            <p class="text-sm text-gray-600">Paiements sécurisés</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-shield text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">24/7</h3>
            <p class="text-sm text-gray-600">Surveillance</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-certificate text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">ISO 27001</h3>
            <p class="text-sm text-gray-600">Certification</p>
        </div>
    </div>

    <!-- Trust Badges -->
    <div class="bg-gradient-to-r from-gray-50 to-white rounded-3xl p-8 mb-16 border border-gray-200">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Nos certifications et partenaires sécurité</h2>
        </div>
        <div class="flex flex-wrap justify-center items-center gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-md flex items-center justify-center mx-auto mb-2 border border-gray-200">
                    <i class="fab fa-cc-visa text-4xl text-blue-600"></i>
                </div>
                <p class="text-xs text-gray-600">Visa Secure</p>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-md flex items-center justify-center mx-auto mb-2 border border-gray-200">
                    <i class="fas fa-shield-alt text-4xl text-[#D4AF37]"></i>
                </div>
                <p class="text-xs text-gray-600">Mastercard SecureCode</p>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-md flex items-center justify-center mx-auto mb-2 border border-gray-200">
                    <i class="fab fa-cc-paypal text-4xl text-blue-700"></i>
                </div>
                <p class="text-xs text-gray-600">PayPal Verified</p>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-md flex items-center justify-center mx-auto mb-2 border border-gray-200">
                    <i class="fas fa-lock text-4xl text-green-600"></i>
                </div>
                <p class="text-xs text-gray-600">SSL 256-bit</p>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-md flex items-center justify-center mx-auto mb-2 border border-gray-200">
                    <span class="text-2xl font-bold text-gray-800">PCI</span>
                </div>
                <p class="text-xs text-gray-600">PCI DSS Compliant</p>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-16">
        <a href="#protection" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-shield-alt text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Protection</span>
        </a>
        
        <a href="#paiement" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-credit-card text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Paiement</span>
        </a>
        
        <a href="#compte" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-user-lock text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Compte</span>
        </a>
        
        <a href="#donnees" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-database text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Données</span>
        </a>
        
        <a href="#conseils" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-lightbulb text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Conseils</span>
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
                        <a href="#protection" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            1. Protection des achats
                        </a>
                    </li>
                    <li>
                        <a href="#paiement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            2. Sécurité des paiements
                        </a>
                    </li>
                    <li>
                        <a href="#compte" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            3. Sécurité du compte
                        </a>
                    </li>
                    <li>
                        <a href="#donnees" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            4. Protection des données
                        </a>
                    </li>
                    <li>
                        <a href="#fraude" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            5. Prévention de la fraude
                        </a>
                    </li>
                    <li>
                        <a href="#conseils" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            6. Conseils de sécurité
                        </a>
                    </li>
                    <li>
                        <a href="#signalement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            7. Signaler un problème
                        </a>
                    </li>
                    <li>
                        <a href="#faq" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            8. FAQ Sécurité
                        </a>
                    </li>
                </ul>
                
                <!-- Security Alert Button -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="#signalement" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition text-sm font-medium">
                        <i class="fas fa-exclamation-triangle"></i>
                        Signaler un incident
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Section 1: Protection des achats -->
            <div id="protection" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">1</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Protection des achats</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span> garantit la sécurité de vos achats grâce à notre programme de protection acheteur :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-shield-alt text-[#D4AF37] text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Garantie "Satisfait ou remboursé"</h3>
                            <p class="text-sm text-gray-600">Si vous n'êtes pas satisfait, vous disposez de 30 jours pour retourner votre article.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-clock text-[#D4AF37] text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Garantie de livraison</h3>
                            <p class="text-sm text-gray-600">Si votre colis n'arrive pas dans les délais annoncés, nous vous remboursons.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-check-double text-[#D4AF37] text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Garantie de conformité</h3>
                            <p class="text-sm text-gray-600">Le produit reçu doit correspondre exactement à sa description.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-gavel text-[#D4AF37] text-xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Médiation</h3>
                            <p class="text-sm text-gray-600">En cas de litige, un service de médiation est à votre disposition.</p>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="font-semibold">Plafond de protection :</span> Jusqu'à 500 000 FCFA par achat.
                    </div>
                </div>
            </div>

            <!-- Section 2: Sécurité des paiements -->
            <div id="paiement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">2</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Sécurité des paiements</h2>
                </div>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3">Technologies de sécurisation</h3>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-lock text-[#D4AF37] mt-1"></i>
                                    <div>
                                        <span class="font-medium text-gray-900">Chiffrement SSL 256-bit</span>
                                        <p class="text-sm text-gray-600">Toutes vos données bancaires sont cryptées pendant la transmission.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-credit-card text-[#D4AF37] mt-1"></i>
                                    <div>
                                        <span class="font-medium text-gray-900">Tokenisation</span>
                                        <p class="text-sm text-gray-600">Nous ne stockons jamais vos numéros de carte bancaire.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-fingerprint text-[#D4AF37] mt-1"></i>
                                    <div>
                                        <span class="font-medium text-gray-900">3D Secure</span>
                                        <p class="text-sm text-gray-600">Authentification renforcée pour les paiements par carte.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="bg-gray-50 p-5 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-3">Moyens de paiement sécurisés</h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                                    <span class="text-sm">Cartes bancaires</span>
                                    <div class="flex gap-1">
                                        <i class="fab fa-cc-visa text-blue-600"></i>
                                        <i class="fab fa-cc-mastercard text-orange-600"></i>
                                        <i class="fab fa-cc-amex text-blue-400"></i>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                                    <span class="text-sm">Orange Money</span>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                                    <span class="text-sm">Wave</span>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded-lg">
                                    <span class="text-sm">PayPal</span>
                                    <i class="fab fa-paypal text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Tous nos prestataires de paiement sont certifiés PCI-DSS niveau 1, la norme de sécurité la plus élevée dans l'industrie des paiements.
                    </div>
                </div>
            </div>

            <!-- Section 3: Sécurité du compte -->
            <div id="compte" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">3</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Sécurité du compte</h2>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-key text-[#D4AF37] mr-2"></i>
                                Authentification
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>Mots de passe hachés (bcrypt)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>Connexion sécurisée HTTPS</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>Protection contre les attaques brute force</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-history text-[#D4AF37] mr-2"></i>
                                Sessions
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>Déconnexion automatique après inactivité</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>Historique des connexions</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>Gestion des appareils connectés</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-3">Recommandations pour votre compte</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-arrow-right text-[#D4AF37] mt-1"></i>
                                <span>Utilisez un mot de passe unique et complexe</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-arrow-right text-[#D4AF37] mt-1"></i>
                                <span>Activez la double authentification (disponible prochainement)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-arrow-right text-[#D4AF37] mt-1"></i>
                                <span>Ne partagez jamais vos identifiants</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-arrow-right text-[#D4AF37] mt-1"></i>
                                <span>Changez régulièrement votre mot de passe</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 4: Protection des données -->
            <div id="donnees" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">4</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Protection des données personnelles</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Nous respectons scrupuleusement le Règlement Général sur la Protection des Données (RGPD) et la loi Informatique et Libertés.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <i class="fas fa-eye text-[#D4AF37] text-xl mb-2"></i>
                            <h4 class="font-semibold text-sm">Droit d'accès</h4>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <i class="fas fa-edit text-[#D4AF37] text-xl mb-2"></i>
                            <h4 class="font-semibold text-sm">Droit de rectification</h4>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <i class="fas fa-trash text-[#D4AF37] text-xl mb-2"></i>
                            <h4 class="font-semibold text-sm">Droit à l'effacement</h4>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Notre engagement</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Collecte minimale des données nécessaires au service</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Conservation limitée dans le temps</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Jamais de vente de données à des tiers</span>
                            </li>
                        </ul>
                    </div>
                    
                    <p class="text-sm text-gray-600">
                        Pour exercer vos droits : <a href="mailto:dpo@marabusiness.com" class="text-[#D4AF37] hover:underline">dpo@marabusiness.com</a>
                    </p>
                </div>
            </div>

            <!-- Section 5: Prévention de la fraude -->
            <div id="fraude" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">5</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Prévention de la fraude</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Nous utilisons des systèmes avancés de détection et de prévention de la fraude :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-brain text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">IA anti-fraude</h3>
                                <p class="text-sm text-gray-600">Analyse comportementale des transactions suspectes</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-map-marker-alt text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Géolocalisation</h3>
                                <p class="text-sm text-gray-600">Détection des connexions inhabituelles</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-clock text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Analyse temporelle</h3>
                                <p class="text-sm text-gray-600">Détection des commandes anormalement rapides</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-users text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Vérification manuelle</h3>
                                <p class="text-sm text-gray-600">Contrôle humain des transactions à risque</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        En cas de transaction suspecte, notre équipe peut vous contacter pour vérification.
                    </div>
                </div>
            </div>

            <!-- Section 6: Conseils de sécurité -->
            <div id="conseils" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">6</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Conseils de sécurité</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-shield-alt text-[#D4AF37] mr-2"></i>
                            À faire
                        </h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Vérifier l'URL (https://)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Utiliser un mot de passe fort</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Mettre à jour vos informations de contact</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Vérifier vos relevés bancaires</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-ban text-red-500 mr-2"></i>
                            À éviter
                        </h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle text-red-500 mt-1"></i>
                                <span>Cliquer sur des liens suspects</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle text-red-500 mt-1"></i>
                                <span>Partager vos identifiants</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle text-red-500 mt-1"></i>
                                <span>Utiliser le même mot de passe partout</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times-circle text-red-500 mt-1"></i>
                                <span>Effectuer des achats sur Wi-Fi public</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700">
                    <i class="fas fa-lightbulb mr-2"></i>
                    <span class="font-semibold">Astuce :</span> Activez les notifications de connexion pour être alerté en cas d'accès inhabituel à votre compte.
                </div>
            </div>

            <!-- Section 7: Signaler un problème -->
            <div id="signalement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">7</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Signaler un problème de sécurité</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Si vous êtes victime d'une fraude ou si vous détectez une activité suspecte sur votre compte :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Urgence (24/7)</h3>
                            <p class="text-lg text-[#D4AF37] font-bold">+221 78 123 45 67</p>
                            <p class="text-xs text-gray-500 mt-1">Ligne d'urgence sécurité</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Par email</h3>
                            <p class="text-[#D4AF37] font-medium">securite@marabusiness.com</p>
                            <p class="text-xs text-gray-500 mt-1">Réponse sous 2h ouvrées</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Informations à fournir</h3>
                        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                            <li>Description du problème</li>
                            <li>Date et heure de l'incident</li>
                            <li>Captures d'écran (si applicable)</li>
                            <li>Vos coordonnées</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 8: FAQ Sécurité -->
            <div id="faq" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">8</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">FAQ - Sécurité</h2>
                </div>
                
                <div class="space-y-4">
                    <!-- FAQ Item 1 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Mes informations bancaires sont-elles stockées ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Non, nous ne stockons jamais vos numéros de carte bancaire. Tous les paiements sont traités via des prestataires certifiés PCI-DSS qui utilisent la tokenisation.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 2 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Comment savoir si je suis sur le site officiel ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Vérifiez la présence du cadenas dans la barre d'adresse et que l'URL commence par https://. Notre domaine officiel est marabusiness.com.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 3 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Que faire si je reçois un email suspect ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Ne cliquez sur aucun lien et ne téléchargez aucune pièce jointe. Transférez l'email à securite@marabusiness.com puis supprimez-le.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 4 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">La double authentification est-elle disponible ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            La double authentification (2FA) sera disponible prochainement. Nous vous tiendrons informés de son lancement.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
                <p>La sécurité de nos clients est notre priorité absolue. Nous améliorons constamment nos systèmes.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Besoin d'aide ou d'informations ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Notre équipe sécurité est disponible 24h/24 pour répondre à vos questions
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-envelope"></i>
                Contactez-nous
            </a>
            <a href="#signalement" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-exclamation-triangle"></i>
                Signaler un incident
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
    
    /* FAQ Accordion */
    .border-gray-200 button {
        transition: all 0.3s ease;
    }
    
    .border-gray-200 button i {
        transition: transform 0.3s ease;
    }
    
    .border-gray-200.active button i {
        transform: rotate(180deg);
    }
</style>

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
