<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.product-safety', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/safety-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Sécurité des <span class="text-gray-900">Produits</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Notre engagement pour votre sécurité et la conformité de nos produits
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
                <i class="fas fa-check-circle text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">100%</h3>
            <p class="text-sm text-gray-600">Produits conformes</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-clipboard-check text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">50+</h3>
            <p class="text-sm text-gray-600">Normes respectées</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-flask text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">1000+</h3>
            <p class="text-sm text-gray-600">Tests effectués</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
            <p class="text-sm text-gray-600">Incident majeur</p>
        </div>
    </div>

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-16">
        <a href="#engagement" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-handshake text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Engagement</span>
        </a>
        
        <a href="#normes" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-certificate text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Normes</span>
        </a>
        
        <a href="#controles" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-search text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Contrôles</span>
        </a>
        
        <a href="#categories" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-tags text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Catégories</span>
        </a>
        
        <a href="#signalement" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-exclamation-triangle text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Signalement</span>
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
                        <a href="#engagement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            1. Notre engagement
                        </a>
                    </li>
                    <li>
                        <a href="#normes" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            2. Normes de sécurité
                        </a>
                    </li>
                    <li>
                        <a href="#controles" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            3. Contrôle qualité
                        </a>
                    </li>
                    <li>
                        <a href="#categories" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            4. Par catégorie
                        </a>
                    </li>
                    <li>
                        <a href="#etiquetage" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            5. Étiquetage
                        </a>
                    </li>
                    <li>
                        <a href="#rappels" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            6. Rappels produits
                        </a>
                    </li>
                    <li>
                        <a href="#signalement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            7. Signalement
                        </a>
                    </li>
                    <li>
                        <a href="#faq" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            8. FAQ Sécurité
                        </a>
                    </li>
                </ul>
                
                <!-- Report Safety Issue Button -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="#signalement" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#D4AF37] hover:bg-[#c9a12f] text-white rounded-xl transition text-sm font-medium">
                        <i class="fas fa-exclamation-triangle"></i>
                        Signaler un problème
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Section 1: Notre engagement -->
            <div id="engagement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">1</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Notre engagement pour votre sécurité</h2>
                </div>
                
                <div class="space-y-4 text-gray-700">
                    <p>
                        Chez <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span>, la sécurité de nos clients est notre priorité absolue. Nous nous engageons à :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="fas fa-check-circle text-[#D4AF37] text-xl"></i>
                                <h3 class="font-semibold text-gray-900">Sélection rigoureuse</h3>
                            </div>
                            <p class="text-sm text-gray-600">Tous nos produits et vendeurs sont soigneusement vérifiés avant leur mise en ligne.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="fas fa-check-circle text-[#D4AF37] text-xl"></i>
                                <h3 class="font-semibold text-gray-900">Conformité légale</h3>
                            </div>
                            <p class="text-sm text-gray-600">Nos produits respectent les normes de sécurité en vigueur dans chaque pays.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="fas fa-check-circle text-[#D4AF37] text-xl"></i>
                                <h3 class="font-semibold text-gray-900">Information transparente</h3>
                            </div>
                            <p class="text-sm text-gray-600">Nous fournissons des informations claires sur l'utilisation et les risques potentiels.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="flex items-center gap-3 mb-2">
                                <i class="fas fa-check-circle text-[#D4AF37] text-xl"></i>
                                <h3 class="font-semibold text-gray-900">Réactivité</h3>
                            </div>
                            <p class="text-sm text-gray-600">Nous traitons rapidement tout signalement de problème de sécurité.</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Notre politique de sécurité s'applique à 100% des produits vendus sur notre plateforme.
                    </div>
                </div>
            </div>

            <!-- Section 2: Normes de sécurité -->
            <div id="normes" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">2</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Normes de sécurité</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Nos produits sont conformes aux principales normes de sécurité internationales et régionales :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Normes internationales</h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">ISO 9001</span>
                                    <span class="text-gray-600">Management de la qualité</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">ISO 22000</span>
                                    <span class="text-gray-600">Sécurité des aliments</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">CE</span>
                                    <span class="text-gray-600">Conformité européenne</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">UL</span>
                                    <span class="text-gray-600">Sécurité électrique</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Normes africaines</h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">ARSO</span>
                                    <span class="text-gray-600">Organisation régionale africaine</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">CODINORM</span>
                                    <span class="text-gray-600">Normes ouest-africaines</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold rounded">ASN</span>
                                    <span class="text-gray-600">Normes sénégalaises</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">
                        <i class="fas fa-certificate mr-2"></i>
                        Nos partenaires fournisseurs doivent justifier de la conformité de leurs produits aux normes applicables.
                    </div>
                </div>
            </div>

            <!-- Section 3: Contrôle qualité -->
            <div id="controles" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">3</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Contrôle qualité</h2>
                </div>
                
                <div class="space-y-6">
                    <div class="relative">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-10 h-10 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">1</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Audit des fournisseurs</h3>
                                <p class="text-sm text-gray-600">Vérification des certifications et des processus de fabrication avant référencement.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-10 h-10 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">2</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Tests échantillons</h3>
                                <p class="text-sm text-gray-600">Analyse en laboratoire des premiers échantillons pour chaque nouvelle référence.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-10 h-10 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">3</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Contrôle à réception</h3>
                                <p class="text-sm text-gray-600">Vérification aléatoire des lots à leur arrivée dans nos entrepôts.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">4</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Suivi des retours</h3>
                                <p class="text-sm text-gray-600">Analyse des motifs de retour pour identifier d'éventuels problèmes récurrents.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Notre laboratoire partenaire</h3>
                        <p class="text-sm text-gray-600">
                            Nous collaborons avec <span class="font-medium">SGS Sénégal</span>, leader mondial de l'inspection et de la certification, pour nos analyses produits.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 4: Par catégorie -->
            <div id="categories" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">4</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Sécurité par catégorie</h2>
                </div>
                
                <div class="space-y-6">
                    <!-- Alimentation -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-utensils text-[#D4AF37]"></i>
                                Produits alimentaires
                            </h3>
                        </div>
                        <div class="p-4">
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Traçabilité complète</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Dates de péremption contrôlées</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Absence d'OGM</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Allergènes clairement indiqués</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Électronique -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-plug text-[#D4AF37]"></i>
                                Appareils électroniques
                            </h3>
                        </div>
                        <div class="p-4">
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Certification CE/UL</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Protection contre les surtensions</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Matériaux ignifugés</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Adaptateurs conformes</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Enfants -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-child text-[#D4AF37]"></i>
                                Produits pour enfants
                            </h3>
                        </div>
                        <div class="p-4">
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Absence de petites pièces détachables</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Peintures non toxiques</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Normes EN71 (jouets)</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Certification sans phtalates</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Cosmétiques -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-spa text-[#D4AF37]"></i>
                                Cosmétiques
                            </h3>
                        </div>
                        <div class="p-4">
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Tests dermatologiques</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Liste INCI complète</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Sans parabènes (sur demande)</li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500 text-xs"></i> Non testés sur les animaux</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Étiquetage -->
            <div id="etiquetage" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">5</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Étiquetage et informations</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Informations obligatoires</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-tag text-[#D4AF37] mt-1"></i>
                                <span>Nom du produit et marque</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-balance-scale text-[#D4AF37] mt-1"></i>
                                <span>Poids/volume net</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-list text-[#D4AF37] mt-1"></i>
                                <span>Composition / Ingrédients</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-calendar-alt text-[#D4AF37] mt-1"></i>
                                <span>Date de péremption (DLUO/DLC)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-map-marker-alt text-[#D4AF37] mt-1"></i>
                                <span>Pays d'origine</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-[#D4AF37] mt-1"></i>
                                <span>Précautions d'emploi</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-3">Symboles de sécurité</h3>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-white rounded-full border border-gray-200 flex items-center justify-center mb-2">
                                    <span class="text-red-600 font-bold text-xl">!</span>
                                </div>
                                <p class="text-xs text-gray-600">Attention</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-white rounded-full border border-gray-200 flex items-center justify-center mb-2">
                                    <span class="text-green-600 text-xl">🌿</span>
                                </div>
                                <p class="text-xs text-gray-600">Bio</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto bg-white rounded-full border border-gray-200 flex items-center justify-center mb-2">
                                    <span class="text-blue-600 text-xl">CE</span>
                                </div>
                                <p class="text-xs text-gray-600">Conforme UE</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Rappels produits -->
            <div id="rappels" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">6</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Rappels produits</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        En cas de défaut de sécurité identifié sur un produit, nous procédons à son retrait immédiat et informons les clients concernés.
                    </p>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                            Rappels en cours
                        </h3>
                        <p class="text-sm text-gray-600 italic">Aucun rappel de produit actif pour le moment.</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Historique des rappels</h3>
                        <p class="text-sm text-gray-600">
                            Consultez l'historique des rappels produits sur le site de la <a href="#" class="text-[#D4AF37] hover:underline">Direction du Commerce Intérieur</a>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 7: Signalement -->
            <div id="signalement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">7</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Signaler un problème de sécurité</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Si vous rencontrez un problème de sécurité avec un produit acheté sur notre plateforme, signalez-le nous immédiatement :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Par email</h3>
                            <p class="text-sm text-gray-600 mb-2">Envoyez votre signalement à :</p>
                            <p class="text-[#D4AF37] font-medium">securite@marabusiness.com</p>
                            <p class="text-xs text-gray-500 mt-2">Réponse sous 24h</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Par téléphone</h3>
                            <p class="text-sm text-gray-600 mb-2">Service sécurité produit :</p>
                            <p class="text-[#D4AF37] font-medium">+221 78 123 45 67</p>
                            <p class="text-xs text-gray-500 mt-2">Lun-Ven: 8h-18h</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Informations à fournir</h3>
                        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                            <li>Numéro de commande</li>
                            <li>Nom du produit et référence</li>
                            <li>Description précise du problème</li>
                            <li>Photos du défaut (si applicable)</li>
                            <li>Date d'achat</li>
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
                    <h2 class="text-2xl font-bold text-gray-900">FAQ - Sécurité des produits</h2>
                </div>
                
                <div class="space-y-4">
                    <!-- FAQ Item 1 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Comment savoir si un produit est certifié ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Les certifications sont indiquées dans la description du produit, sous forme de logos (CE, ISO, etc.) ou explicitement mentionnées.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 2 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Que faire si je reçois un produit endommagé ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Contactez notre service client dans les 48h avec des photos du produit et de l'emballage. Nous procéderons à un échange ou remboursement.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 3 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Les produits cosmétiques sont-ils testés ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Oui, tous nos produits cosmétiques sont soumis à des tests dermatologiques et leur composition est conforme à la réglementation en vigueur.
                        </div>
                    </div>
                    
                    <!-- FAQ Item 4 -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <button class="w-full px-6 py-4 text-left flex items-center justify-between group hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37]">Y a-t-il des risques d'allergie ?</span>
                            <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37]"></i>
                        </button>
                        <div class="px-6 pb-4 text-gray-600" style="display: none;">
                            Les allergènes majeurs sont systématiquement indiqués dans la description des produits. Nous vous recommandons de toujours vérifier la liste des ingrédients avant achat.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
                <p>Votre sécurité est notre priorité. Nous travaillons quotidiennement à l'amélioration de nos contrôles.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Une question sur la sécurité d'un produit ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
        Notre équipe est à votre disposition pour vous informer sur nos normes et contrôles qualité
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-envelope"></i>
                Contactez-nous
            </a>
            <a href="#signalement" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-exclamation-triangle"></i>
                Signaler un problème
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