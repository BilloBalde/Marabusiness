<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.terms-of-use', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/terms-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Conditions Générales <span class="text-gray-900">d'Utilisation</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Les règles qui régissent votre utilisation de la plateforme MARA BUSINESS
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    <!-- Introduction Card -->
    <div class="bg-gradient-to-r from-gray-50 to-white rounded-3xl p-8 mb-16 border border-gray-200 max-w-4xl mx-auto">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 bg-[#D4AF37] rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                <i class="fas fa-file-contract text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Bienvenue sur MARA BUSINESS</h2>
                <p class="text-gray-700 leading-relaxed">
                    En accédant à la plateforme <span class="font-semibold text-[#D4AF37]">MARA BUSINESS</span>, vous acceptez d'être lié par les présentes Conditions Générales d'Utilisation. Veuillez les lire attentivement avant d'utiliser nos services.
                </p>
                <div class="mt-4 bg-gray-100 px-4 py-2 rounded-full text-sm text-gray-600 inline-flex items-center gap-2">
                    <i class="far fa-calendar-alt text-[#D4AF37]"></i>
                    Dernière mise à jour : {{ now()->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Pills -->
    <div class="flex flex-wrap justify-center gap-3 mb-16 max-w-4xl mx-auto">
        <a href="#acceptation" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Acceptation
        </a>
        <a href="#compte" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Compte
        </a>
        <a href="#achats" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Achats
        </a>
        <a href="#prix" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Prix
        </a>
        <a href="#livraison" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Livraison
        </a>
        <a href="#retours" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Retours
        </a>
        <a href="#responsabilite" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Responsabilité
        </a>
        <a href="#propriete" class="px-5 py-2.5 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Propriété
        </a>
    </div>

    <!-- Main Content - Centered Single Column -->
    <div class="max-w-4xl mx-auto space-y-12">
        
        <!-- Section 1: Acceptation -->
        <div id="acceptation" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">1</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Acceptation des conditions</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <p class="text-gray-700 mb-4">
                    En accédant ou en utilisant la plateforme MARA BUSINESS, vous confirmez avoir lu, compris et accepté d'être lié par les présentes Conditions Générales d'Utilisation. Si vous n'acceptez pas ces conditions, vous ne devez pas utiliser nos services.
                </p>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="font-semibold">Important :</span> Ces conditions constituent un contrat légal entre vous et MARA BUSINESS.
                </div>
                
                <p class="text-sm text-gray-600">
                    Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prennent effet dès leur publication sur cette page.
                </p>
            </div>
        </div>

        <!-- Section 2: Création de compte -->
        <div id="compte" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">2</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Création et sécurité du compte</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Conditions d'inscription</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Être âgé d'au moins 18 ans</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Fournir des informations exactes et à jour</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Une seule inscription par personne</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Sécurité du compte</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Vous êtes responsable de votre mot de passe</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Informez-nous de toute utilisation non autorisée</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Ne partagez jamais vos identifiants</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Vous pouvez créer un compte en tant que particulier ou professionnel.
                </div>
            </div>
        </div>

        <!-- Section 3: Achats et commandes -->
        <div id="achats" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">3</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Achats et commandes</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">i</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Processus de commande</h3>
                            <p class="text-gray-600">Une commande est considérée comme acceptée après confirmation de paiement et envoi d'un email de confirmation.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">i</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Annulation</h3>
                            <p class="text-gray-600">Vous pouvez annuler votre commande dans un délai de 2 heures après validation, sauf si celle-ci a déjà été expédiée.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">i</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Disponibilité des produits</h3>
                            <p class="text-gray-600">En cas d'indisponibilité après commande, nous vous proposerons un remboursement ou un avoir.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Prix et paiement -->
        <div id="prix" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">4</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Prix et paiement</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Prix</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Prix indiqués en FCFA ou euros, TTC</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Frais de livraison indiqués avant validation</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Paiement</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Carte bancaire, Orange Money, Wave, PayPal</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-circle-check text-[#D4AF37] text-sm mt-1"></i>
                                <span class="text-gray-600">Paiement 100% sécurisé (SSL 256-bit)</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Livraison -->
        <div id="livraison" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">5</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Livraison</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-map-marker-alt text-[#D4AF37] mb-1"></i>
                        <p class="font-medium">Sénégal</p>
                        <p class="text-xs text-gray-500">2-5 jours</p>
                    </div>
                    <div class="text-center p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-map-marker-alt text-[#D4AF37] mb-1"></i>
                        <p class="font-medium">Afrique de l'Ouest</p>
                        <p class="text-xs text-gray-500">5-10 jours</p>
                    </div>
                    <div class="text-center p-3 border border-gray-200 rounded-xl">
                        <i class="fas fa-map-marker-alt text-[#D4AF37] mb-1"></i>
                        <p class="font-medium">International</p>
                        <p class="text-xs text-gray-500">10-15 jours</p>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600">
                    <span class="font-medium">Retard de livraison :</span> En cas de retard important (plus de 7 jours après la date annoncée), veuillez nous contacter.
                </p>
            </div>
        </div>

        <!-- Section 6: Retours et remboursements -->
        <div id="retours" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">6</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Retours et remboursements</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <p class="text-gray-700 mb-4">
                    Vous disposez d'un droit de rétractation de <span class="font-bold">14 jours</span> à compter de la réception de votre commande.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-green-50 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Retour accepté</h3>
                        <p class="text-sm text-gray-600">Produit non utilisé, emballage d'origine intact</p>
                    </div>
                    
                    <div class="bg-red-50 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Retour refusé</h3>
                        <p class="text-sm text-gray-600">Produits personnalisés, articles hygiéniques ouverts</p>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600">
                    <span class="font-medium">Remboursement :</span> Effectué sous 14 jours après réception et vérification.
                </p>
            </div>
        </div>

        <!-- Section 7: Limitation de responsabilité -->
        <div id="responsabilite" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">7</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Limitation de responsabilité</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <p class="text-gray-700 mb-4">
                    MARA BUSINESS agit en tant qu'intermédiaire entre les vendeurs et les acheteurs.
                </p>
                
                <ul class="space-y-2 mb-4">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle text-[#D4AF37] text-xs mt-2"></i>
                        <span class="text-gray-600">Non-conformité des produits vendus par des tiers</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle text-[#D4AF37] text-xs mt-2"></i>
                        <span class="text-gray-600">Retards de livraison imputables aux transporteurs</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle text-[#D4AF37] text-xs mt-2"></i>
                        <span class="text-gray-600">Dommages causés lors du transport</span>
                    </li>
                </ul>
                
                <div class="bg-gray-50 p-4 rounded-xl text-sm text-gray-600">
                    <span class="font-semibold">Garantie légale :</span> Nous restons tenus par les garanties légales de conformité et des vices cachés.
                </div>
            </div>
        </div>

        <!-- Section 8: Propriété intellectuelle -->
        <div id="propriete" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">8</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Propriété intellectuelle</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <p class="text-gray-700 mb-4">
                    L'ensemble du contenu de la plateforme est protégé par le droit d'auteur.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Interdictions</h3>
                        <ul class="space-y-1">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times text-red-500 text-sm mt-1"></i>
                                <span class="text-sm text-gray-600">Reproduction non autorisée</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times text-red-500 text-sm mt-1"></i>
                                <span class="text-sm text-gray-600">Distribution des contenus</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Marques déposées</h3>
                        <p class="text-sm text-gray-600">
                            "MARA BUSINESS" et le logo sont des marques déposées.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 9: Droit applicable -->
        <div id="droit" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">9</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Droit applicable</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <p class="text-gray-700 mb-3">
                    Les présentes conditions sont régies par le droit sénégalais.
                </p>
                <p class="text-sm text-gray-600">
                    Tout litige relève de la compétence exclusive des tribunaux de Dakar.
                </p>
            </div>
        </div>

        <!-- Section 10: Nous contacter -->
        <div id="contact" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-[#D4AF37]">10</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Nous contacter</h2>
            </div>
            
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-envelope text-[#D4AF37] w-5"></i>
                            <a href="mailto:legal@marabusiness.com" class="text-gray-700 hover:text-[#D4AF37]">legal@marabusiness.com</a>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-phone-alt text-[#D4AF37] w-5"></i>
                            <a href="tel:+221781234567" class="text-gray-700 hover:text-[#D4AF37]">+221 78 123 45 67</a>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <p class="text-sm text-gray-700 mb-1">Service juridique</p>
                        <p class="text-sm text-[#D4AF37]">a.diallo@marabusiness.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="mt-16 bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Prêt à commencer ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Créez votre compte et profitez de tous nos services
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-user-plus"></i>
                Créer un compte
            </a>
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-envelope"></i>
                Nous contacter
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