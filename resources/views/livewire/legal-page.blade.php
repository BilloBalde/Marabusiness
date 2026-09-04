<div class="w-full max-w-[90rem] px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.legal', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background - directly after navbar -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/legal-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Mentions <span class="text-gray-900">Légales</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Informations juridiques et conditions générales de MARA BUSINESS
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

    <!-- Quick Navigation Pills - cleaner than cards -->
    <div class="flex flex-wrap justify-center gap-3 mb-12">
        <a href="#editeur" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Éditeur</a>
        <a href="#hebergement" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Hébergement</a>
        <a href="#propriete" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Propriété</a>
        <a href="#donnees" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Données</a>
        <a href="#cookies" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Cookies</a>
        <a href="#responsabilite" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Responsabilité</a>
        <a href="#droit" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Droit</a>
        <a href="#contact" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">Contact</a>
    </div>

    <!-- Main Content - Single Column (NO GRID) -->
    <div class="max-w-4xl mx-auto space-y-8 mb-16">
        
        <!-- Section 1: Éditeur du site -->
        <div id="editeur" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">1</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Éditeur du site</h2>
            </div>
            
            <div class="space-y-4 text-gray-700">
                <p>
                    <span class="font-semibold text-gray-900">MARA BUSINESS</span> est une société à responsabilité limitée (SARL) au capital de 5 000 000 FCFA, immatriculée au Registre du Commerce et des Crédits Mobiliers de Dakar sous le numéro RCCM SN-DKR-2020-B-12345.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Identité</h3>
                        <ul class="space-y-2 text-sm">
                            <li><span class="text-gray-500">Raison sociale :</span> MARA BUSINESS SARL</li>
                            <li><span class="text-gray-500">Capital :</span> 5 000 000 FCFA</li>
                            <li><span class="text-gray-500">RCCM :</span> SN-DKR-2020-B-12345</li>
                            <li><span class="text-gray-500">NINEA :</span> 123456789</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900 mb-2">Coordonnées</h3>
                        <ul class="space-y-2 text-sm">
                            <li><span class="text-gray-500">Adresse :</span> 123 Rue Principale, Dakar</li>
                            <li><span class="text-gray-500">Téléphone :</span> +221 78 123 45 67</li>
                            <li><span class="text-gray-500">Email :</span> contact@marabusiness.com</li>
                        </ul>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-user-tie text-[#D4AF37] mr-2"></i>
                    <span class="font-semibold">Directeur de publication :</span> M. Amadou Diallo
                </p>
            </div>
        </div>

        <!-- Section 2: Hébergement -->
        <div id="hebergement" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">2</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Hébergement</h2>
            </div>
            
            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-cloud text-[#D4AF37] text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 mb-1">JINEIYATECH HOSTING</h3>
                    <p class="text-sm text-gray-600">Rue 123, Dakar, Sénégal - www.jineiyatech.com</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-600 mt-4">
                Le site est hébergé sur des serveurs sécurisés situés en France et au Sénégal.
            </p>
        </div>

        <!-- Section 3: Propriété intellectuelle -->
        <div id="propriete" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">3</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Propriété intellectuelle</h2>
            </div>
            
            <p class="text-gray-700 mb-4">
                L'ensemble du contenu du site est protégé par le droit d'auteur.
            </p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-trademark text-[#D4AF37]"></i>
                        <h3 class="font-semibold">Marques</h3>
                    </div>
                    <p class="text-sm text-gray-600">"MARA BUSINESS" et le logo sont des marques déposées.</p>
                </div>
                
                <div class="border border-gray-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-copyright text-[#D4AF37]"></i>
                        <h3 class="font-semibold">Contenus</h3>
                    </div>
                    <p class="text-sm text-gray-600">Reproduction interdite sans autorisation.</p>
                </div>
            </div>
        </div>

        <!-- Section 4: Données personnelles -->
        <div id="donnees" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">4</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Données personnelles</h2>
            </div>
            
            <p class="text-gray-700 mb-4">
                Conformément au RGPD, vous disposez de droits sur vos données.
            </p>
            
            <div class="grid grid-cols-3 gap-2 text-center mb-4">
                <div class="bg-gray-50 p-2 rounded-lg">
                    <i class="fas fa-eye text-[#D4AF37]"></i>
                    <p class="text-xs">Accès</p>
                </div>
                <div class="bg-gray-50 p-2 rounded-lg">
                    <i class="fas fa-edit text-[#D4AF37]"></i>
                    <p class="text-xs">Rectification</p>
                </div>
                <div class="bg-gray-50 p-2 rounded-lg">
                    <i class="fas fa-trash text-[#D4AF37]"></i>
                    <p class="text-xs">Effacement</p>
                </div>
            </div>
            
            <p class="text-sm text-gray-600">
                Contactez notre DPO : <a href="mailto:dpo@marabusiness.com" class="text-[#D4AF37]">dpo@marabusiness.com</a>
            </p>
        </div>

        <!-- Section 5: Cookies -->
        <div id="cookies" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">5</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Politique des cookies</h2>
            </div>
            
            <p class="text-gray-700 mb-4">Nous utilisons des cookies pour améliorer votre expérience.</p>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Finalité</th>
                            <th class="px-4 py-3 text-left">Durée</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr><td class="px-4 py-3">Essentiels</td><td>Fonctionnement</td><td>Session</td></tr>
                        <tr><td class="px-4 py-3">Analytiques</td><td>Audience</td><td>13 mois</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 6: Limitation de responsabilité -->
        <div id="responsabilite" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">6</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Limitation de responsabilité</h2>
            </div>
            
            <p class="text-gray-700">
                MARA BUSINESS ne peut être tenu responsable des dommages indirects ou des interruptions de service.
            </p>
        </div>

        <!-- Section 7: Droit applicable -->
        <div id="droit" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">7</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Droit applicable</h2>
            </div>
            
            <p class="text-gray-700">
                Les présentes mentions sont soumises au droit sénégalais. Tribunaux compétents : Dakar.
            </p>
        </div>

        <!-- Section 8: Contact juridique -->
        <div id="contact" class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-xl font-bold text-[#D4AF37]">8</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Contact juridique</h2>
            </div>
            
            <div class="space-y-2">
                <p><i class="fas fa-envelope text-[#D4AF37] w-6 inline"></i> legal@marabusiness.com</p>
                <p><i class="fas fa-phone-alt text-[#D4AF37] w-6 inline"></i> +221 78 123 45 67</p>
            </div>
            
            <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                <p class="font-semibold">Me. Fatou Ndiaye</p>
                <p class="text-sm">fatou.ndiaye@marabusiness.com</p>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center text-sm text-gray-500 pt-6 border-t border-gray-200">
            <p>© {{ now()->year }} MARA BUSINESS - Tous droits réservés.</p>
        </div>
    </div>
</div>

<style>
    html {
        scroll-behavior: smooth;
    }
</style>