<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.digital-services', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/digital-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Règlement sur les <span class="text-gray-900">Services Numériques</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Conformité au Digital Services Act (DSA) - Transparence et sécurité pour nos utilisateurs
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
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                <i class="fas fa-euro-sign text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Notre engagement DSA</h2>
                <p class="text-gray-700 leading-relaxed">
                    En tant que plateforme de commerce électronique opérant sur le marché européen, <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span> se conforme pleinement au Règlement (UE) 2022/2065 sur les services numériques (Digital Services Act). Cette page détaille nos obligations, vos droits et les mécanismes de signalement mis en place pour garantir un environnement en ligne sûr, transparent et équitable.
                </p>
            </div>
        </div>
    </div>

    <!-- Key Stats / Highlights -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-16">
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">50k+</h3>
            <p class="text-sm text-gray-600">Utilisateurs actifs</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-flag text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">48h</h3>
            <p class="text-sm text-gray-600">Traitement des signalements</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-balance-scale text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">100%</h3>
            <p class="text-sm text-gray-600">Conformité DSA</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-gavel text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">Point de contact</h3>
            <p class="text-sm text-gray-600">Autorités européennes</p>
        </div>
    </div>

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-16">
        <a href="#informations" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-info-circle text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Informations</span>
        </a>
        
        <a href="#signalement" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-flag text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Signalement</span>
        </a>
        
        <a href="#transparence" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-chart-line text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Transparence</span>
        </a>
        
        <a href="#contact" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-envelope text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Contact DSA</span>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
        <!-- Table of Contents - Sticky Sidebar -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="fas fa-euro-sign text-[#D4AF37]"></i>
                    Sommaire DSA
                </h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="#introduction" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Qu'est-ce que le DSA ?
                        </a>
                    </li>
                    <li>
                        <a href="#informations" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Informations légales
                        </a>
                    </li>
                    <li>
                        <a href="#signalement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Signalement de contenus illicites
                        </a>
                    </li>
                    <li>
                        <a href="#traitement" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Traitement des signalements
                        </a>
                    </li>
                    <li>
                        <a href="#voies" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Voies de recours
                        </a>
                    </li>
                    <li>
                        <a href="#transparence" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Rapport de transparence
                        </a>
                    </li>
                    <li>
                        <a href="#autorites" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Autorités compétentes
                        </a>
                    </li>
                    <li>
                        <a href="#contact" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Point de contact DSA
                        </a>
                    </li>
                </ul>
                
                <!-- Signalement Button -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="#signalement" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition text-sm font-medium">
                        <i class="fas fa-flag"></i>
                        Signaler un contenu
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Section: Qu'est-ce que le DSA -->
            <div id="introduction" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <i class="fas fa-question-circle text-[#D4AF37] text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Qu'est-ce que le Digital Services Act (DSA) ?</h2>
                </div>
                
                <div class="space-y-4 text-gray-700">
                    <p>
                        Le <span class="font-semibold text-[#D4AF37]">Règlement sur les services numériques</span> (Digital Services Act) est un règlement européen entré en vigueur le 16 novembre 2022. Il vise à créer un environnement numérique plus sûr et plus transparent pour les utilisateurs de l'Union européenne.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Objectifs principaux
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-chevron-right text-[#D4AF37] text-xs mt-1"></i>
                                    <span>Lutter contre les contenus illicites en ligne</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-chevron-right text-[#D4AF37] text-xs mt-1"></i>
                                    <span>Protéger les droits fondamentaux des utilisateurs</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-chevron-right text-[#D4AF37] text-xs mt-1"></i>
                                    <span>Garantir la transparence des plateformes</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Nos obligations
                            </h3>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-chevron-right text-[#D4AF37] text-xs mt-1"></i>
                                    <span>Mécanisme de signalement accessible</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-chevron-right text-[#D4AF37] text-xs mt-1"></i>
                                    <span>Transparence sur les recommandations</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-chevron-right text-[#D4AF37] text-xs mt-1"></i>
                                    <span>Point de contact unique</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 1: Informations légales -->
            <div id="informations" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">1</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Informations légales (Art. 5 DSA)</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Identité du prestataire</h3>
                        <div class="bg-gray-50 p-4 rounded-xl space-y-2 text-sm">
                            <p><span class="font-medium">Nom :</span> MARA BUSINESS SARL</p>
                            <p><span class="font-medium">Forme juridique :</span> Société à responsabilité limitée</p>
                            <p><span class="font-medium">Capital :</span> 5 000 000 FCFA</p>
                            <p><span class="font-medium">RCCM :</span> SN-DKR-2020-B-12345</p>
                            <p><span class="font-medium">NINEA :</span> 123456789</p>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Coordonnées</h3>
                        <div class="bg-gray-50 p-4 rounded-xl space-y-2 text-sm">
                            <p><span class="font-medium">Adresse :</span> 123 Rue Principale, Dakar, Sénégal</p>
                            <p><span class="font-medium">Email :</span> contact@marabusiness.com</p>
                            <p><span class="font-medium">Téléphone :</span> +221 78 123 45 67</p>
                            <p><span class="font-medium">Représentant légal :</span> M. Amadou Diallo</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span class="font-semibold">Représentant DSA dans l'UE :</span> Conformément à l'article 13 du DSA, notre représentant désigné est DSArep SARL, 15 Rue de la Loi, 75008 Paris, France - dsa@marabusiness.eu
                </div>
            </div>

            <!-- Section 2: Signalement de contenus illicites -->
            <div id="signalement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">2</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Signalement de contenus illicites (Art. 16 DSA)</h2>
                </div>
                
                <div class="space-y-6">
                    <p class="text-gray-700">
                        Tout utilisateur peut signaler un contenu qu'il estime illicite (produits contrefaits, discours de haine, contenus frauduleux, etc.) via notre mécanisme de signalement dédié.
                    </p>
                    
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-4">Formulaire de signalement</h3>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Votre nom complet *</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Votre email *</label>
                                    <input type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">URL du contenu à signaler *</label>
                                <input type="url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de contenu illicite *</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition">
                                    <option value="">Sélectionnez un motif</option>
                                    <option value="contrefacon">Produit contrefait / contrefaçon</option>
                                    <option value="fraude">Offre frauduleuse / arnaque</option>
                                    <option value="discours_haine">Discours de haine / incitation à la violence</option>
                                    <option value="illegal">Produit illégal (interdit par la loi)</option>
                                    <option value="dangereux">Produit dangereux / non conforme</option>
                                    <option value="autre">Autre motif</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description détaillée *</label>
                                <textarea rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition" placeholder="Expliquez pourquoi ce contenu est illicite..."></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pièces jointes (preuves, captures d'écran)</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-500">Glissez vos fichiers ici ou <span class="text-[#D4AF37] cursor-pointer">parcourez</span></p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-2">
                                <input type="checkbox" class="mt-1">
                                <p class="text-xs text-gray-500">Je certifie que les informations fournies sont exactes et que ce signalement est fait de bonne foi. (Conformément à l'article 16 du DSA)</p>
                            </div>
                            
                            <button class="w-full py-3 bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold rounded-lg transition">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Soumettre le signalement
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span class="font-semibold">Attention :</span> Les signalements abusifs peuvent entraîner la suspension de votre compte.
                    </div>
                </div>
            </div>

            <!-- Section 3: Traitement des signalements -->
            <div id="traitement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">3</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Traitement des signalements</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Conformément au DSA, nous traitons tous les signalements dans les plus brefs délais :
                    </p>
                    
                    <div class="relative">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">1</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Accusé de réception</h3>
                                <p class="text-sm text-gray-600">Sous 24h, nous confirmons la réception de votre signalement.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">2</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Analyse</h3>
                                <p class="text-sm text-gray-600">Notre équipe examine le contenu signalé et les preuves fournies.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">3</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Décision motivée</h3>
                                <p class="text-sm text-gray-600">Sous 48h (délai légal), nous prenons une décision et vous notifions.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">4</div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">Action</h3>
                                <p class="text-sm text-gray-600">Si le contenu est illicite, il est retiré ou l'accès est bloqué.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">
                        <i class="fas fa-clock mr-2"></i>
                        <span class="font-semibold">Délai légal :</span> 48h pour traiter les signalements (sauf cas complexes justifiés).
                    </div>
                </div>
            </div>

            <!-- Section 4: Voies de recours -->
            <div id="voies" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">4</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Voies de recours (Art. 20 DSA)</h2>
                </div>
                
                <div class="space-y-6">
                    <p class="text-gray-700">
                        Si vous n'êtes pas satisfait de notre décision concernant un signalement ou une action sur votre contenu, vous disposez de plusieurs voies de recours :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-redo-alt text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900">Réexamen interne</h3>
                            </div>
                            <p class="text-sm text-gray-600">Demandez un réexamen de notre décision sous 6 mois.</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-gavel text-purple-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900">Médiation</h3>
                            </div>
                            <p class="text-sm text-gray-600">Recours à un organisme de médiation agréé.</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-balance-scale text-green-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900">Autorité judiciaire</h3>
                            </div>
                            <p class="text-sm text-gray-600">Saisine des tribunaux compétents.</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-landmark text-orange-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900">Autorité de régulation</h3>
                            </div>
                            <p class="text-sm text-gray-600">Saisine de l'ARCOM ou des autorités nationales.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Rapport de transparence -->
            <div id="transparence" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">5</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Rapport de transparence</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Conformément à l'article 24 du DSA, nous publions un rapport semestriel sur nos activités de modération :
                    </p>
                    
                    <div class="bg-gray-50 p-5 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-4">Rapport Janvier - Juin 2026</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Signalements reçus</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Contrefaçon</span>
                                        <span class="font-bold text-gray-900">187</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Produits dangereux</span>
                                        <span class="font-bold text-gray-900">42</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Contenus frauduleux</span>
                                        <span class="font-bold text-gray-900">28</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Discours de haine</span>
                                        <span class="font-bold text-gray-900">7</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Actions prises</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Contenus retirés</span>
                                        <span class="font-bold text-green-600">198</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Signalements rejetés</span>
                                        <span class="font-bold text-gray-900">48</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Comptes suspendus</span>
                                        <span class="font-bold text-orange-600">23</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Délai moyen de traitement</span>
                                        <span class="font-bold text-[#D4AF37]">32h</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-right">
                            <a href="#" class="text-[#D4AF37] hover:underline text-sm font-medium">
                                Télécharger le rapport complet (PDF)
                                <i class="fas fa-download ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Autorités compétentes -->
            <div id="autorites" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">6</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Autorités compétentes DSA</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Coordinateur pour les services numériques</h3>
                        <p class="text-sm text-gray-600 mb-3">ARCOM (Autorité de régulation de la communication audiovisuelle et numérique)</p>
                        <div class="text-sm">
                            <p><span class="font-medium">Site web :</span> www.arcom.fr</p>
                            <p><span class="font-medium">Email :</span> dsa@arcom.fr</p>
                            <p><span class="font-medium">Adresse :</span> 39-43 quai André Citroën, 75015 Paris</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Commission européenne</h3>
                        <p class="text-sm text-gray-600 mb-3">Direction générale des réseaux de communication, du contenu et des technologies</p>
                        <div class="text-sm">
                            <p><span class="font-medium">Site web :</span> www.ec.europa.eu/digital-strategy</p>
                            <p><span class="font-medium">Email :</span> cnect-dsa@ec.europa.eu</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 7: Point de contact DSA -->
            <div id="contact" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">7</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Point de contact DSA</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Pour les utilisateurs</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-envelope text-[#D4AF37] w-5"></i>
                                <a href="mailto:dsa@marabusiness.com" class="text-gray-700 hover:text-[#D4AF37]">dsa@marabusiness.com</a>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <i class="fas fa-phone-alt text-[#D4AF37] w-5"></i>
                                <a href="tel:+33123456789" class="text-gray-700 hover:text-[#D4AF37]">+33 1 23 45 67 89</a>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <i class="fas fa-globe text-[#D4AF37] w-5"></i>
                                <span class="text-gray-700">Formulaire de contact (ci-dessus)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-3">Notre représentant DSA</h3>
                        <p class="text-sm text-gray-700 mb-2">M. Jean Dupont</p>
                        <p class="text-sm text-gray-600 mb-1">Responsable conformité DSA</p>
                        <p class="text-sm text-[#D4AF37] mb-1">j.dupont@marabusiness.com</p>
                        <p class="text-sm text-gray-600">+33 6 12 34 56 78</p>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
                <p>© {{ now()->year }} MARA BUSINESS - Conforme au Règlement (UE) 2022/2065 sur les services numériques.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Besoin d'aide concernant le DSA ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Notre équipe est à votre disposition pour toute question relative au Digital Services Act
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#signalement" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-flag"></i>
                Signaler un contenu
            </a>
            <a href="#contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-envelope"></i>
                Contacter le DPO
            </a>
        </div>
    </div>
</div>

