<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.privacy', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/privacy-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Politique de <span class="text-gray-900">confidentialité</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Protection de vos données personnelles
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    <!-- Introduction Card -->
    <div class="max-w-4xl mx-auto text-center mb-16">
        <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
            Protection des données
        </div>
        <p class="text-lg text-gray-700 leading-relaxed">
            Chez <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span>, nous accordons une importance capitale 
            à la protection de vos données personnelles. Cette politique détaille comment nous collectons, 
            utilisons et protégeons vos informations lorsque vous utilisez nos services.
        </p>
        <div class="flex justify-center mt-6">
            <div class="bg-gray-100 px-4 py-2 rounded-full text-sm text-gray-600 inline-flex items-center gap-2">
                <i class="far fa-calendar-alt text-[#D4AF37]"></i>
                Version 2.0 - Mise à jour le {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <!-- Table of Contents -->
    <div class="mb-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Sommaire</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <a href="#collecte" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">1</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Collecte</span>
            </a>
            <a href="#utilisation" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">2</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Utilisation</span>
            </a>
            <a href="#partage" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">3</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Partage</span>
            </a>
            <a href="#securite" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">4</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Sécurité</span>
            </a>
            <a href="#cookies" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">5</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Cookies</span>
            </a>
            <a href="#droits" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">6</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Vos droits</span>
            </a>
            <a href="#contact" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
                <span class="block text-xl font-bold text-[#D4AF37] mb-2">7</span>
                <span class="text-sm text-gray-600 group-hover:text-[#D4AF37]">Contact</span>
            </a>
        </div>
    </div>

    <!-- Section 1: Collecte -->
    <div id="collecte" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">1</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Collecte des informations</h2>
        </div>
        
        <p class="text-gray-700 mb-6">
            Nous collectons plusieurs types d'informations dans le but de vous fournir et d'améliorer nos services :
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user text-[#D4AF37]"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Informations personnelles</h3>
                </div>
                <p class="text-gray-600">Nom, prénom, adresse email, numéro de téléphone, adresse de livraison</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-credit-card text-[#D4AF37]"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Informations de paiement</h3>
                </div>
                <p class="text-gray-600">Données de carte bancaire (chiffrées), historique des transactions</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-laptop text-[#D4AF37]"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Données de navigation</h3>
                </div>
                <p class="text-gray-600">Adresse IP, type de navigateur, pages visitées, durée des sessions</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:shadow-lg transition-all">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-comment text-[#D4AF37]"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Communications</h3>
                </div>
                <p class="text-gray-600">Correspondance avec notre service client, avis et commentaires</p>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
            <i class="fas fa-info-circle mr-2"></i>
            Nous ne collectons jamais d'informations sensibles sans votre consentement explicite.
        </div>
    </div>

    <!-- Section 2: Utilisation -->
    <div id="utilisation" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">2</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Utilisation de vos données</h2>
        </div>
        
        <div class="space-y-4">
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-check text-green-600 text-xs"></i>
                </div>
                <div>
                    <span class="font-semibold text-gray-900">Traitement des commandes :</span>
                    <p class="text-gray-600">Pour gérer vos achats, livraisons et services après-vente</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-check text-green-600 text-xs"></i>
                </div>
                <div>
                    <span class="font-semibold text-gray-900">Communication :</span>
                    <p class="text-gray-600">Pour vous envoyer des mises à jour sur vos commandes</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-check text-green-600 text-xs"></i>
                </div>
                <div>
                    <span class="font-semibold text-gray-900">Amélioration du service :</span>
                    <p class="text-gray-600">Pour analyser et optimiser votre expérience</p>
                </div>
            </div>
            
            <div class="flex items-start gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-check text-green-600 text-xs"></i>
                </div>
                <div>
                    <span class="font-semibold text-gray-900">Personnalisation :</span>
                    <p class="text-gray-600">Pour vous proposer des offres adaptées</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Partage -->
    <div id="partage" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">3</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Partage des informations</h2>
        </div>
        
        <p class="text-gray-700 mb-6">Nous ne vendons jamais vos données personnelles. Elles peuvent être partagées uniquement dans les cas suivants :</p>
        
        <div class="space-y-3">
            <div class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <i class="fas fa-truck text-[#D4AF37] w-6"></i>
                <span class="text-gray-700"><span class="font-semibold">Partenaires logistiques :</span> Pour assurer la livraison</span>
            </div>
            
            <div class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <i class="fas fa-credit-card text-[#D4AF37] w-6"></i>
                <span class="text-gray-700"><span class="font-semibold">Prestataires de paiement :</span> Pour traiter vos transactions</span>
            </div>
            
            <div class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200">
                <i class="fas fa-gavel text-[#D4AF37] w-6"></i>
                <span class="text-gray-700"><span class="font-semibold">Obligations légales :</span> Si la loi nous y oblige</span>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-yellow-50 rounded-xl text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Tous nos partenaires sont tenus de respecter la confidentialité de vos données.
        </div>
    </div>

    <!-- Section 4: Sécurité -->
    <div id="securite" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">4</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Sécurité des données</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-white rounded-xl border border-gray-200">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Chiffrement SSL</h3>
                <p class="text-sm text-gray-600">Connexion sécurisée</p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-xl border border-gray-200">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-server text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Serveurs sécurisés</h3>
                <p class="text-sm text-gray-600">Certifiés ISO 27001</p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-xl border border-gray-200">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-shield text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Accès restreint</h3>
                <p class="text-sm text-gray-600">Employés autorisés uniquement</p>
            </div>
        </div>
    </div>

    <!-- Section 5: Cookies -->
    <div id="cookies" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">5</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Cookies et technologies</h2>
        </div>
        
        <p class="text-gray-700 mb-6">Nous utilisons des cookies pour améliorer votre expérience :</p>
        
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Type</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Objectif</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Durée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-3">Essentiels</td>
                        <td class="px-6 py-3">Fonctionnement du site</td>
                        <td class="px-6 py-3">Session</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3">Fonctionnels</td>
                        <td class="px-6 py-3">Préférences</td>
                        <td class="px-6 py-3">1 an</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3">Analytiques</td>
                        <td class="px-6 py-3">Audience</td>
                        <td class="px-6 py-3">13 mois</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-right">
            <a href="#" class="text-[#D4AF37] hover:underline text-sm">
                <i class="fas fa-cog mr-1"></i> Gérer mes préférences
            </a>
        </div>
    </div>

    <!-- Section 6: Vos droits -->
    <div id="droits" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">6</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Vos droits (RGPD)</h2>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-eye text-[#D4AF37]"></i>
                    <span class="font-semibold">Droit d'accès</span>
                </div>
                <p class="text-sm text-gray-600">Consulter vos données</p>
            </div>
            
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-edit text-[#D4AF37]"></i>
                    <span class="font-semibold">Droit de rectification</span>
                </div>
                <p class="text-sm text-gray-600">Modifier vos informations</p>
            </div>
            
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-trash-alt text-[#D4AF37]"></i>
                    <span class="font-semibold">Droit à l'effacement</span>
                </div>
                <p class="text-sm text-gray-600">Supprimer vos données</p>
            </div>
            
            <div class="bg-white p-4 rounded-xl border border-gray-200">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-ban text-[#D4AF37]"></i>
                    <span class="font-semibold">Droit d'opposition</span>
                </div>
                <p class="text-sm text-gray-600">Vous opposer au traitement</p>
            </div>
        </div>
        
        <div class="p-4 bg-gray-50 rounded-xl">
            <p class="text-sm text-gray-700">
                <span class="font-semibold">Pour exercer vos droits :</span> Contactez notre DPO à 
                <a href="mailto:dpo@marabusiness.com" class="text-[#D4AF37] hover:underline">dpo@marabusiness.com</a>
            </p>
        </div>
    </div>

    <!-- Section 7: Contact -->
    <div id="contact" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <span class="text-xl font-bold text-[#D4AF37]">7</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Nous contacter</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl border border-gray-200">
                <div class="space-y-4">
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
                        <span class="text-gray-700">123 Rue Principale, Dakar</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                <h3 class="font-semibold text-gray-900 mb-3">Délégué à la Protection</h3>
                <p class="text-sm text-gray-700 mb-2">M. Amadou Diallo</p>
                <p class="text-sm text-[#D4AF37] mb-1">dpo@marabusiness.com</p>
                <p class="text-sm text-gray-600">+221 78 987 65 43</p>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="text-center text-sm text-gray-500 pt-8 border-t border-gray-200">
        <p>Cette politique peut être mise à jour périodiquement. Dernière mise à jour : {{ now()->format('d/m/Y') }}</p>
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
</div>
