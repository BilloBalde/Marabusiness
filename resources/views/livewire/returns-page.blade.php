<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.returns', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/returns-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Retours et <span class="text-gray-900">Remboursements</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Notre politique de retour simple et transparente pour votre tranquillité d'esprit
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    <!-- Key Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
        <div class="bg-white rounded-2xl p-8 text-center border border-gray-200 shadow-sm">
            <div class="text-4xl font-bold text-[#D4AF37] mb-2">30</div>
            <div class="text-lg font-semibold text-gray-900 mb-1">jours</div>
            <p class="text-sm text-gray-600">pour retourner votre article</p>
        </div>
        
        <div class="bg-white rounded-2xl p-8 text-center border border-gray-200 shadow-sm">
            <div class="text-4xl font-bold text-[#D4AF37] mb-2">Gratuits</div>
            <div class="text-lg font-semibold text-gray-900 mb-1">retours</div>
            <p class="text-sm text-gray-600">pour les articles défectueux</p>
        </div>
        
        <div class="bg-white rounded-2xl p-8 text-center border border-gray-200 shadow-sm">
            <div class="text-4xl font-bold text-[#D4AF37] mb-2">5-7</div>
            <div class="text-lg font-semibold text-gray-900 mb-1">jours</div>
            <p class="text-sm text-gray-600">délai de remboursement</p>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="flex flex-wrap justify-center gap-4 mb-16">
        <a href="#conditions" class="px-6 py-3 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Conditions
        </a>
        <a href="#delais" class="px-6 py-3 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Délais
        </a>
        <a href="#procedure" class="px-6 py-3 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Procédure
        </a>
        <a href="#remboursement" class="px-6 py-3 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Remboursement
        </a>
        <a href="#echange" class="px-6 py-3 bg-[#D4AF37]/10 text-[#D4AF37] rounded-full font-medium hover:bg-[#D4AF37] hover:text-white transition">
            Échange
        </a>
    </div>

    <!-- Conditions de retour -->
    <div id="conditions" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Conditions
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Conditions de retour</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Pour être éligible à un retour, votre article doit répondre aux conditions suivantes
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Accepté</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Article non utilisé</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Emballage d'origine intact</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Étiquettes encore attachées</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-check text-green-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Moins de 30 jours depuis réception</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-2xl p-8 border border-gray-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Non accepté</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-xmark text-red-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Articles portés ou lavés</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-xmark text-red-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Emballage endommagé</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-xmark text-red-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Plus de 30 jours</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle-xmark text-red-500 text-sm mt-1"></i>
                        <span class="text-gray-600">Articles soldés (sauf défaut)</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Délais de retour -->
    <div id="delais" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Délais
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Délais de retour</h2>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-check text-[#D4AF37] text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Délai standard</h3>
                        <p class="text-gray-600">Vous disposez de <span class="font-bold text-[#D4AF37]">30 jours</span> à compter de la date de réception de votre commande pour initier un retour.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-[#D4AF37] text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Période de traitement</h3>
                        <p class="text-gray-600">Une fois votre retour reçu, le traitement prend <span class="font-bold text-[#D4AF37]">2-3 jours ouvrés</span>. Le remboursement sera effectué sous <span class="font-bold text-[#D4AF37]">5-7 jours ouvrés</span>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Procédure de retour -->
    <div id="procedure" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Procédure
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Procédure de retour</h2>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold">1</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Connectez-vous à votre compte</h3>
                        <p class="text-gray-600">Accédez à la section "Mes commandes" dans votre espace client.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold">2</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Sélectionnez la commande et l'article</h3>
                        <p class="text-gray-600">Choisissez le ou les articles que vous souhaitez retourner.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold">3</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Indiquez la raison du retour</h3>
                        <p class="text-gray-600">Sélectionnez le motif (taille incorrecte, défaut, changement d'avis...).</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold">4</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Imprimez l'étiquette de retour</h3>
                        <p class="text-gray-600">Générez et imprimez votre bon de retour pré-affranchi (retours gratuits pour les articles défectueux).</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-6 border border-gray-200">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 bg-[#D4AF37] rounded-full flex items-center justify-center text-white font-bold">5</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Emballez et expédiez</h3>
                        <p class="text-gray-600">Emballez soigneusement l'article dans son emballage d'origine et déposez le colis au point relais indiqué.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
            <i class="fas fa-info-circle mr-2"></i>
            Vous recevrez un email de confirmation à chaque étape du processus.
        </div>
    </div>

    <!-- Remboursement -->
    <div id="remboursement" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Remboursement
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Remboursement</h2>
        </div>

        <div class="bg-white rounded-2xl p-8 border border-gray-200 mb-6">
            <p class="text-gray-700 mb-6">Une fois votre retour reçu et inspecté, nous vous informerons par email de l'acceptation ou du refus de votre remboursement.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-green-50 rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        Remboursement accepté
                    </h3>
                    <p class="text-sm text-gray-600">Le remboursement sera crédité sur votre moyen de paiement original sous 5-7 jours ouvrés.</p>
                </div>
                
                <div class="bg-red-50 rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-times-circle text-red-600 mr-2"></i>
                        Remboursement refusé
                    </h3>
                    <p class="text-sm text-gray-600">Si l'article ne remplit pas les conditions, il vous sera renvoyé à vos frais.</p>
                </div>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-3">Délais selon le mode de paiement</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Carte bancaire</span>
                    <span class="font-medium text-gray-900">3-5 jours ouvrés</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">PayPal</span>
                    <span class="font-medium text-gray-900">24-48 heures</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Orange Money / Wave</span>
                    <span class="font-medium text-gray-900">2-3 jours ouvrés</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Échange -->
    <div id="echange" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Échange
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Échange</h2>
        </div>

        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <p class="text-gray-700 mb-4">Si vous souhaitez échanger un article (taille, couleur, etc.), la procédure est la suivante :</p>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4">
                <p class="text-sm text-yellow-700">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Pour un échange, nous vous recommandons de retourner l'article et de passer une nouvelle commande. Cela garantit la disponibilité du produit et accélère le processus.
                </p>
            </div>
            
            <p class="text-sm text-gray-600">
                Si vous avez besoin d'une taille différente d'un même article, contactez notre service client pour vérifier la disponibilité avant de procéder au retour.
            </p>
        </div>
    </div>

    <!-- Articles défectueux -->
    <div id="defectueux" class="max-w-4xl mx-auto mb-16 scroll-mt-24">
        <div class="text-center mb-8">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Articles défectueux
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Articles défectueux</h2>
        </div>

        <div class="bg-white rounded-2xl p-8 border border-gray-200">
            <p class="text-gray-700 mb-6">Si vous avez reçu un article défectueux ou endommagé :</p>
            
            <div class="space-y-3">
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-camera text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-700">Prenez des photos du défaut et de l'emballage</span>
                </div>
                
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-envelope text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-700">Contactez notre service client dans les 48h suivant la réception</span>
                </div>
                
                <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-xl">
                    <i class="fas fa-truck text-[#D4AF37] mt-1"></i>
                    <span class="text-gray-700">Les frais de retour sont pris en charge par MARA BUSINESS</span>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                <i class="fas fa-check-circle mr-2"></i>
                Après vérification, vous serez intégralement remboursé ou un échange sera effectué sans frais supplémentaires.
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Besoin d'aide pour votre retour ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Notre équipe est là pour vous guider à chaque étape du processus
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-headset"></i>
                Contacter le support
            </a>
            <a href="tel:+221781234567" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-phone-alt"></i>
                +221 78 123 45 67
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
</div>
