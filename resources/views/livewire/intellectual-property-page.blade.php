<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.intellectual-property', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/ip-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Propriété <span class="text-gray-900">Intellectuelle</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Protection des droits d'auteur, marques et contenus sur MARA BUSINESS
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
                <i class="fas fa-copyright text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Copyright</h3>
            <p class="text-sm text-gray-600">Tous contenus protégés</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trademark text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Marques</h3>
            <p class="text-sm text-gray-600">Marques déposées</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-registered text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Brevets</h3>
            <p class="text-sm text-gray-600">Innovations protégées</p>
        </div>
        
        <div class="bg-white rounded-2xl p-6 text-center border border-gray-200 shadow-sm hover:shadow-lg transition-all">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-[#D4AF37] text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Protection</h3>
            <p class="text-sm text-gray-600">Contenu exclusif</p>
        </div>
    </div>

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-16">
        <a href="#copyright" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-copyright text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Copyright</span>
        </a>
        
        <a href="#marques" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-trademark text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Marques</span>
        </a>
        
        <a href="#brevets" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-file-certificate text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Brevets</span>
        </a>
        
        <a href="#infraction" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-exclamation-triangle text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Infraction</span>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
        <!-- Table of Contents - Sticky Sidebar -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="fas fa-balance-scale text-[#D4AF37]"></i>
                    Sommaire
                </h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="#introduction" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Introduction
                        </a>
                    </li>
                    <li>
                        <a href="#copyright" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Droits d'auteur (Copyright)
                        </a>
                    </li>
                    <li>
                        <a href="#marques" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Marques déposées
                        </a>
                    </li>
                    <li>
                        <a href="#brevets" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Brevets et modèles
                        </a>
                    </li>
                    <li>
                        <a href="#contenus" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Contenus utilisateurs
                        </a>
                    </li>
                    <li>
                        <a href="#licences" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Licences et autorisations
                        </a>
                    </li>
                    <li>
                        <a href="#infraction" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Signalement d'infraction
                        </a>
                    </li>
                    <li>
                        <a href="#contact" class="text-gray-600 hover:text-[#D4AF37] transition flex items-center gap-2 group">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full group-hover:bg-[#D4AF37]"></span>
                            Contact IP
                        </a>
                    </li>
                </ul>
                
                <!-- Download PDF Button -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition text-sm font-medium">
                        <i class="fas fa-file-pdf text-red-500"></i>
                        Télécharger en PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Section: Introduction -->
            <div id="introduction" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <i class="fas fa-info-circle text-[#D4AF37] text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Introduction</h2>
                </div>
                
                <div class="space-y-4 text-gray-700">
                    <p>
                        Chez <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span>, nous accordons une importance capitale à la protection de la propriété intellectuelle. Cette politique définit les droits et obligations concernant l'utilisation des contenus, marques et innovations présents sur notre plateforme.
                    </p>
                    <p>
                        La propriété intellectuelle est un pilier fondamental de notre activité et de celle de nos partenaires. Nous nous engageons à respecter et faire respecter ces droits conformément aux législations sénégalaises et internationales.
                    </p>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                        <i class="fas fa-gavel mr-2"></i>
                        <span class="font-semibold">Cadre légal :</span> Code de la Propriété Intellectuelle du Sénégal (loi n° 2008-09), Accords de l'OMPI, conventions de Berne et de Paris.
                    </div>
                </div>
            </div>

            <!-- Section 1: Droits d'auteur (Copyright) -->
            <div id="copyright" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">1</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Droits d'auteur (Copyright)</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        L'ensemble du contenu présent sur le site MARA BUSINESS est protégé par le droit d'auteur :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Contenus protégés
                            </h3>
                            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                <li>Textes et descriptions</li>
                                <li>Photographies et images</li>
                                <li>Vidéos et animations</li>
                                <li>Logos et éléments graphiques</li>
                                <li>Base de données produits</li>
                                <li>Interface utilisateur</li>
                            </ul>
                        </div>
                        
                        <div class="bg-amber-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                                <i class="fas fa-times-circle text-red-500 mr-2"></i>
                                Interdictions
                            </h3>
                            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                <li>Reproduction sans autorisation</li>
                                <li>Distribution non autorisée</li>
                                <li>Modification des contenus</li>
                                <li>Utilisation commerciale</li>
                                <li>Copie vers d'autres sites</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Toute reproduction non autorisée constitue une contrefaçon passible de poursuites civiles et pénales (jusqu'à 3 ans d'emprisonnement et 300 000€ d'amende).
                    </div>
                </div>
            </div>

            <!-- Section 2: Marques déposées -->
            <div id="marques" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">2</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Marques déposées</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Les marques suivantes sont la propriété exclusive de MARA BUSINESS et sont protégées par enregistrement auprès de l'OAPI (Organisation Africaine de la Propriété Intellectuelle) :
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-xl font-bold text-[#D4AF37]">®</span>
                                <h3 class="font-semibold text-gray-900">MARA BUSINESS</h3>
                            </div>
                            <p class="text-xs text-gray-500">Marque verbale et figurative</p>
                            <p class="text-xs text-gray-400 mt-1">Enregistrement n° OAPI 123456</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-xl font-bold text-[#D4AF37]">®</span>
                                <h3 class="font-semibold text-gray-900">MARA</h3>
                            </div>
                            <p class="text-xs text-gray-500">Logo et identité visuelle</p>
                            <p class="text-xs text-gray-400 mt-1">Enregistrement n° OAPI 123457</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-xl font-bold text-[#D4AF37]">®</span>
                                <h3 class="font-semibold text-gray-900">MARA EXPRESS</h3>
                            </div>
                            <p class="text-xs text-gray-500">Service de livraison rapide</p>
                            <p class="text-xs text-gray-400 mt-1">Enregistrement n° OAPI 123458</p>
                        </div>
                        
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-xl font-bold text-[#D4AF37]">®</span>
                                <h3 class="font-semibold text-gray-900">MARA PAY</h3>
                            </div>
                            <p class="text-xs text-gray-500">Solution de paiement</p>
                            <p class="text-xs text-gray-400 mt-1">Enregistrement n° OAPI 123459</p>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-600 mt-2">
                        Toute utilisation non autorisée de ces marques est interdite et constitutive de contrefaçon.
                    </p>
                </div>
            </div>

            <!-- Section 3: Brevets et modèles -->
            <div id="brevets" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">3</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Brevets et modèles</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Certaines innovations technologiques développées par MARA BUSINESS font l'objet de dépôts de brevets et de modèles d'utilité :
                    </p>
                    
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-file-invoice text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Système de gestion logistique intelligente</h3>
                                <p class="text-sm text-gray-600">Brevet n° SN/2023/00123 - Algorithme d'optimisation des tournées de livraison</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-file-invoice text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Plateforme de suivi en temps réel</h3>
                                <p class="text-sm text-gray-600">Modèle d'utilité n° SN/2023/00456 - Interface de tracking innovante</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                            <i class="fas fa-file-invoice text-[#D4AF37] mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-900">Système de paiement sécurisé</h3>
                                <p class="text-sm text-gray-600">Brevet n° SN/2023/00789 - Protocole de transaction mobile</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        Ces brevets sont déposés auprès de l'OAPI et bénéficient d'une protection dans les 17 pays membres.
                    </div>
                </div>
            </div>

            <!-- Section 4: Contenus utilisateurs -->
            <div id="contenus" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">4</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Contenus utilisateurs</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        En publiant des contenus sur notre plateforme (avis, photos, commentaires), vous nous accordez une licence d'utilisation tout en conservant vos droits :
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-2">Ce que vous conservez</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Vos droits d'auteur</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Votre propriété intellectuelle</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Droit de retrait (sous conditions)</li>
                            </ul>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-900 mb-2">Ce que vous nous accordez</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><i class="fas fa-arrow-right text-[#D4AF37] mr-2"></i>Licence non-exclusive</li>
                                <li><i class="fas fa-arrow-right text-[#D4AF37] mr-2"></i>Droit d'affichage</li>
                                <li><i class="fas fa-arrow-right text-[#D4AF37] mr-2"></i>Partage sur nos réseaux</li>
                            </ul>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-600">
                        <span class="font-semibold">Responsabilité :</span> Vous garantissez que vos contenus ne violent pas les droits de tiers. En cas de litige, vous en assumez l'entière responsabilité.
                    </p>
                </div>
            </div>

            <!-- Section 5: Licences et autorisations -->
            <div id="licences" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">5</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Licences et autorisations</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Pour toute utilisation de nos contenus protégés, vous devez obtenir une autorisation préalable :
                    </p>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Type d'utilisation</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Autorisation requise</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Conditions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-3">Citation courte</td>
                                    <td class="px-4 py-3">Non (avec mention)</td>
                                    <td class="px-4 py-3">Source et auteur</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Reproduction partielle</td>
                                    <td class="px-4 py-3">Oui - écrite</td>
                                    <td class="px-4 py-3">Limité à 10%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Reproduction intégrale</td>
                                    <td class="px-4 py-3">Oui - écrite + licence</td>
                                    <td class="px-4 py-3">Payante</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">Utilisation commerciale</td>
                                    <td class="px-4 py-3">Oui - contrat</td>
                                    <td class="px-4 py-3">Redevances</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <p class="text-sm text-gray-600 mt-2">
                        Pour demander une autorisation : <a href="mailto:ip@marabusiness.com" class="text-[#D4AF37] hover:underline">ip@marabusiness.com</a>
                    </p>
                </div>
            </div>

            <!-- Section 6: Signalement d'infraction -->
            <div id="infraction" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">6</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Signalement d'infraction</h2>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        Si vous estimez que vos droits de propriété intellectuelle sont violés sur notre plateforme, vous pouvez nous signaler l'infraction :
                    </p>
                    
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-4">Formulaire de notification DMCA</h3>
                        
                        <div class="space-y-3 text-sm">
                            <p>Votre notification doit contenir les éléments suivants :</p>
                            
                            <ol class="list-decimal list-inside space-y-2 text-gray-600">
                                <li>Description de l'œuvre protégée</li>
                                <li>URL précise du contenu litigieux</li>
                                <li>Preuve de vos droits (certificat d'enregistrement)</li>
                                <li>Vos coordonnées complètes</li>
                                <li>Déclaration sur l'honneur de bonne foi</li>
                                <li>Signature électronique ou physique</li>
                            </ol>
                            
                            <div class="bg-white p-4 rounded-lg border border-gray-200 mt-4">
                                <p class="font-medium">Envoyez à :</p>
                                <p class="text-[#D4AF37]">legal@marabusiness.com</p>
                                <p class="text-xs text-gray-500 mt-1">Ou par courrier : MARA BUSINESS - Service Juridique, 123 Rue Principale, Dakar, Sénégal</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">
                        <i class="fas fa-clock mr-2"></i>
                        Nous traitons les signalements sous 48h ouvrées et retirons tout contenu contrefaisant.
                    </div>
                </div>
            </div>

            <!-- Section 7: Contact IP -->
            <div id="contact" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8 scroll-mt-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <span class="text-xl font-bold text-[#D4AF37]">7</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Contact - Propriété Intellectuelle</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-gray-700 mb-4">Pour toute question relative à la propriété intellectuelle :</p>
                        
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-envelope text-[#D4AF37] w-5"></i>
                                <a href="mailto:ip@marabusiness.com" class="text-gray-700 hover:text-[#D4AF37]">ip@marabusiness.com</a>
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
                        <h3 class="font-semibold text-gray-900 mb-3">Responsable IP</h3>
                        <p class="text-sm text-gray-700 mb-2">Me. Amadou Diallo</p>
                        <p class="text-sm text-gray-600 mb-1">Conseil en Propriété Industrielle</p>
                        <p class="text-sm text-[#D4AF37] mb-1">a.diallo@marabusiness.com</p>
                        <p class="text-sm text-gray-600">+221 78 987 65 43</p>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
                <p>© {{ now()->year }} MARA BUSINESS - Tous droits de propriété intellectuelle réservés.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Protégeons ensemble la propriété intellectuelle</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Signalez toute infraction ou posez vos questions à notre équipe dédiée
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-exclamation-triangle"></i>
                Signaler une infraction
            </a>
            <a href="mailto:ip@marabusiness.com" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-envelope"></i>
                ip@marabusiness.com
            </a>
        </div>
    </div>
</div>
   