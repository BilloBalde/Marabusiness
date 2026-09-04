<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.cookies', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/cookies-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Politique des <span class="text-gray-900">Cookies</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Comment nous utilisons les cookies pour améliorer votre expérience sur MARA BUSINESS
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

    <!-- Introduction Card -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-3xl p-8 mb-16 border border-blue-200">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 bg-[#D4AF37] rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                <i class="fas fa-cookie-bite text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Notre engagement pour votre vie privée</h2>
                <p class="text-gray-700 leading-relaxed">
                    Chez <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span>, nous utilisons des cookies et technologies similaires pour améliorer votre expérience de navigation, analyser le trafic et personnaliser le contenu. Cette politique vous explique ce que sont les cookies, comment nous les utilisons et comment vous pouvez les gérer.
                </p>
            </div>
        </div>
    </div>

    <!-- Cookie Consent Preview -->
    <div class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 mb-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Aperçu du bandeau cookie</h2>
        <div class="bg-gray-900 rounded-2xl p-6 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-cookie-bite text-[#D4AF37] text-2xl"></i>
                    <div>
                        <p class="text-sm">Nous utilisons des cookies pour améliorer votre expérience sur MARA BUSINESS. En continuant, vous acceptez notre <a href="#" class="text-[#D4AF37] underline">politique de cookies</a>.</p>
                    </div>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <button class="px-4 py-2 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-semibold rounded-lg text-sm transition">
                        Accepter
                    </button>
                    <button class="px-4 py-2 bg-transparent border border-white/30 hover:bg-white/10 text-white rounded-lg text-sm transition">
                        Personnaliser
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-16">
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-clock text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">4</h3>
            <p class="text-sm text-gray-600">Types de cookies</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">13 mois</h3>
            <p class="text-sm text-gray-600">Durée maximale</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">100%</h3>
            <p class="text-sm text-gray-600">Conformité RGPD</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-sliders-h text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">Total</h3>
            <p class="text-sm text-gray-600">Contrôle total</p>
        </div>
    </div>

    <!-- Quick Navigation Pills (replacing the cards) -->
    <div class="flex flex-wrap justify-center gap-3 mb-12">
        <a href="#cestquoi" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Qu'est-ce qu'un cookie ?
        </a>
        <a href="#types" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Types de cookies
        </a>
        <a href="#essentiels" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Essentiels
        </a>
        <a href="#fonctionnels" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Fonctionnels
        </a>
        <a href="#analytiques" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Analytiques
        </a>
        <a href="#publicitaires" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Publicitaires
        </a>
        <a href="#gestion" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Gestion
        </a>
    </div>

    <!-- Main Content - Simplified Single Column -->
    <div class="max-w-4xl mx-auto space-y-8 mb-16">
        
        <!-- Section 1: Qu'est-ce qu'un cookie -->
        <div id="cestquoi" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">1</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Qu'est-ce qu'un cookie ?</h2>
            </div>
            
            <div class="space-y-4 text-gray-700">
                <p>
                    Un <span class="font-semibold text-[#D4AF37]">cookie</span> est un petit fichier texte déposé sur votre ordinateur, tablette ou smartphone lors de la visite d'un site web. Il permet de stocker des informations sur votre navigation pour faciliter votre expérience et offrir des fonctionnalités personnalisées.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">À quoi ça sert ?</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span>Mémoriser votre panier d'achat</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span>Garder votre connexion active</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span>Analyser le trafic du site</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Durée de vie</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span>Cookies de session : supprimés à la fermeture</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span>Cookies persistants : jusqu'à 13 mois</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Types de cookies utilisés -->
        <div id="types" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">2</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Types de cookies utilisés</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Objectif</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Durée</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Consentement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="px-4 py-3 font-medium">Essentiels</td>
                            <td class="px-4 py-3">Fonctionnement du site</td>
                            <td class="px-4 py-3">Session</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Obligatoire</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Fonctionnels</td>
                            <td class="px-4 py-3">Préférences utilisateur</td>
                            <td class="px-4 py-3">6 mois</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Optionnel</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Analytiques</td>
                            <td class="px-4 py-3">Mesure d'audience</td>
                            <td class="px-4 py-3">13 mois</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Optionnel</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Publicitaires</td>
                            <td class="px-4 py-3">Publicités personnalisées</td>
                            <td class="px-4 py-3">6 mois</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Optionnel</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Cookies essentiels -->
        <div id="essentiels" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">3</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Cookies essentiels</h2>
            </div>
            
            <div class="space-y-4">
                <p class="text-gray-700">
                    Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés. Ils permettent de :
                </p>
                
                <div class="grid grid-cols-1 gap-3">
                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-shopping-cart text-[#D4AF37] mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Panier d'achat</h3>
                            <p class="text-sm text-gray-600">Mémoriser les articles que vous avez ajoutés à votre panier</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-user-lock text-[#D4AF37] mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Authentification</h3>
                            <p class="text-sm text-gray-600">Garder votre session active et sécurisée</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-shield-alt text-[#D4AF37] mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Sécurité</h3>
                            <p class="text-sm text-gray-600">Protéger contre les fraudes et les attaques</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Ces cookies ne collectent pas d'informations personnelles à des fins marketing.
                </div>
            </div>
        </div>

        <!-- Section 4: Cookies fonctionnels -->
        <div id="fonctionnels" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">4</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Cookies fonctionnels</h2>
            </div>
            
            <div class="space-y-4">
                <p class="text-gray-700">
                    Ces cookies améliorent votre expérience en mémorisant vos préférences :
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Langue</h3>
                        <p class="text-sm text-gray-600">Mémorise votre langue préférée</p>
                    </div>
                    <div class="border border-gray-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Devise</h3>
                        <p class="text-sm text-gray-600">Conserve votre choix de devise (FCFA/EUR)</p>
                    </div>
                    <div class="border border-gray-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Historique</h3>
                        <p class="text-sm text-gray-600">Sauvegarde vos recherches récentes</p>
                    </div>
                    <div class="border border-gray-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Affichage</h3>
                        <p class="text-sm text-gray-600">Mémorise votre mode d'affichage (grille/liste)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Cookies analytiques -->
        <div id="analytiques" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">5</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Cookies analytiques</h2>
            </div>
            
            <div class="space-y-4">
                <p class="text-gray-700">
                    Nous utilisons ces cookies pour comprendre comment les visiteurs interagissent avec notre site :
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Google Analytics</h3>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-chart-line text-[#D4AF37] w-4"></i>
                                <span>Pages visitées</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-clock text-[#D4AF37] w-4"></i>
                                <span>Temps passé sur le site</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-mouse-pointer text-[#D4AF37] w-4"></i>
                                <span>Parcours de navigation</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Données collectées</h3>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-ip text-[#D4AF37] w-4"></i>
                                <span>Adresse IP (anonymisée)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-globe text-[#D4AF37] w-4"></i>
                                <span>Localisation approximative</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-laptop text-[#D4AF37] w-4"></i>
                                <span>Type d'appareil</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Ces données nous aident à améliorer continuellement notre site.
                </div>
            </div>
        </div>

        <!-- Section 6: Cookies publicitaires -->
        <div id="publicitaires" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">6</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Cookies publicitaires</h2>
            </div>
            
            <div class="space-y-4">
                <p class="text-gray-700">
                    Ces cookies sont utilisés pour vous proposer des publicités pertinentes :
                </p>
                
                <div class="grid grid-cols-1 gap-3">
                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                        <i class="fab fa-facebook text-[#D4AF37] mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Facebook Pixel</h3>
                            <p class="text-sm text-gray-600">Publicités ciblées sur Facebook et Instagram</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                        <i class="fab fa-google text-[#D4AF37] mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Google Ads</h3>
                            <p class="text-sm text-gray-600">Annonces personnalisées sur le réseau Google</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-chart-bar text-[#D4AF37] mt-1"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Ciblage</h3>
                            <p class="text-sm text-gray-600">Limite le nombre de fois que vous voyez une publicité</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 7: Gérer vos préférences -->
        <div id="gestion" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">7</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Gérer vos préférences</h2>
            </div>
            
            <div class="space-y-6">
                <p class="text-gray-700">
                    Vous pouvez à tout moment modifier vos préférences de cookies via notre interface dédiée ou directement depuis votre navigateur.
                </p>
                
                <div class="bg-gray-50 p-6 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-4">Personnaliser vos préférences</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                            <div>
                                <span class="font-medium text-gray-900">Cookies essentiels</span>
                                <p class="text-xs text-gray-500">Nécessaires au fonctionnement du site</p>
                            </div>
                            <div class="px-3 py-1 bg-gray-200 text-gray-500 rounded-full text-sm">
                                Toujours actifs
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                            <div>
                                <span class="font-medium text-gray-900">Cookies fonctionnels</span>
                                <p class="text-xs text-gray-500">Préférences utilisateur</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#D4AF37]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#D4AF37]"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                            <div>
                                <span class="font-medium text-gray-900">Cookies analytiques</span>
                                <p class="text-xs text-gray-500">Mesure d'audience</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#D4AF37]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#D4AF37]"></div>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                            <div>
                                <span class="font-medium text-gray-900">Cookies publicitaires</span>
                                <p class="text-xs text-gray-500">Publicités personnalisées</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#D4AF37]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#D4AF37]"></div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button class="px-6 py-2 bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold rounded-lg transition">
                            Enregistrer
                        </button>
                        <button class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
                            Tout accepter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 8: Nous contacter -->
        <div id="contact" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">8</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Nous contacter</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-700 mb-4">Pour toute question relative aux cookies :</p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-envelope text-[#D4AF37] w-5"></i>
                            <a href="mailto:privacy@marabusiness.com" class="text-gray-700 hover:text-[#D4AF37]">privacy@marabusiness.com</a>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <i class="fas fa-phone-alt text-[#D4AF37] w-5"></i>
                            <a href="tel:+221781234567" class="text-gray-700 hover:text-[#D4AF37]">+221 78 123 45 67</a>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <i class="fas fa-map-marker-alt text-[#D4AF37] w-5"></i>
                            <span class="text-gray-700">123 Rue Principale, Dakar, Sénégal</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-3">Délégué à la Protection des Données</h3>
                    <p class="text-sm text-gray-700 mb-2">M. Amadou Diallo</p>
                    <p class="text-sm text-gray-600 mb-1">dpo@marabusiness.com</p>
                    <p class="text-sm text-gray-600">+221 78 987 65 43</p>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
            <p>© {{ now()->year }} MARA BUSINESS - Politique de cookies conforme au RGPD.</p>
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