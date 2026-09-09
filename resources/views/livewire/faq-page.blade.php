<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.faq', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/faq-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                Foire Aux <span class="text-gray-900">Questions</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Trouvez rapidement des réponses à vos questions sur nos services
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    {{-- <!-- Search Bar -->
    <div class="max-w-2xl mx-auto mb-16">
        <div class="relative">
            <input type="text" 
                   placeholder="Rechercher une question..." 
                   class="w-full px-6 py-4 pr-12 rounded-2xl border border-gray-200 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition-all shadow-lg">
            <i class="fas fa-search absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
        </div>
        <p class="text-sm text-gray-500 text-center mt-3">
            Questions fréquentes • Livraison • Paiements • Retours
        </p>
    </div> --}}

    <!-- FAQ Categories -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-16">
        <a href="#commandes" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-shopping-bag text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Commandes</span>
        </a>
        
        <a href="#livraison" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-truck text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Livraison</span>
        </a>
        
        <a href="#paiements" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-credit-card text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Paiements</span>
        </a>
        
        <a href="#retours" class="bg-white p-4 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:shadow-lg transition-all text-center group">
            <div class="w-12 h-12 mx-auto mb-3 bg-[#D4AF37]/10 rounded-full flex items-center justify-center group-hover:bg-[#D4AF37] transition-all">
                <i class="fas fa-undo-alt text-[#D4AF37] group-hover:text-white"></i>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-[#D4AF37]">Retours</span>
        </a>
    </div>

    <!-- Commandes Section -->
    <div id="commandes" class="scroll-mt-24 mb-16">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-bag text-[#D4AF37] text-xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Commandes</h2>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Comment passer une commande ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Pour passer une commande, parcourez nos produits, ajoutez-les à votre panier, puis suivez les instructions de paiement. Vous recevrez un email de confirmation une fois votre commande validée.
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Puis-je modifier ma commande après validation ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Vous pouvez modifier votre commande dans les 2 heures suivant sa validation en contactant notre service client. Passé ce délai, la commande est en cours de traitement.
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Comment suivre ma commande ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Une fois votre commande expédiée, vous recevrez un email avec un numéro de suivi. Vous pouvez également suivre votre commande depuis votre compte dans la section "Mes commandes".
                </div>
            </div>
        </div>
    </div>

    <!-- Livraison Section -->
    <div id="livraison" class="scroll-mt-24 mb-16">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <i class="fas fa-truck text-[#D4AF37] text-xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Livraison</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clock text-[#D4AF37] text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Délais de livraison</h3>
                <p class="text-sm text-gray-600">3-7 jours ouvrés selon votre localisation</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-globe-africa text-[#D4AF37] text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Zones de livraison</h3>
                <p class="text-sm text-gray-600">Nous livrons dans toute l'Afrique de l'Ouest</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-euro-sign text-[#D4AF37] text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Frais de livraison</h3>
                <p class="text-sm text-gray-600">Gratuits pour les commandes de +50 000 FCFA</p>
            </div>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Quels sont les modes de livraison disponibles ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Nous proposons la livraison à domicile par nos partenaires logistiques et le retrait en point relais dans les grandes villes.
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Que faire si ma commande n'arrive pas ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Contactez notre service client dans les 48h suivant la date de livraison prévue. Nous ouvrirons une enquête auprès du transporteur.
                </div>
            </div>
        </div>
    </div>

    <!-- Paiements Section -->
    <div id="paiements" class="scroll-mt-24 mb-16">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <i class="fas fa-credit-card text-[#D4AF37] text-xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Paiements</h2>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 6 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Quels moyens de paiement acceptez-vous ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Nous acceptons les cartes bancaires (Visa, Mastercard), Orange Money, Wave, et PayPal. Tous les paiements sont sécurisés.
                </div>
            </div>

            <!-- FAQ Item 7 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Mes informations bancaires sont-elles sécurisées ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Oui, toutes vos transactions sont chiffrées via SSL. Nous ne stockons jamais vos informations de carte bancaire.
                </div>
            </div>

            <!-- FAQ Item 8 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Puis-je payer en plusieurs fois ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Pour le moment, nous ne proposons pas de paiement en plusieurs fois. Cette option sera disponible prochainement.
                </div>
            </div>
        </div>
    </div>

    <!-- Retours Section -->
    <div id="retours" class="scroll-mt-24 mb-16">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center">
                <i class="fas fa-undo-alt text-[#D4AF37] text-xl"></i>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Retours et Remboursements</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-[#D4AF37]/5 to-transparent rounded-2xl p-6 border border-[#D4AF37]/20">
                <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                    <i class="fas fa-check-circle text-[#D4AF37] mr-2"></i>
                    Politique de retour
                </h3>
                <p class="text-gray-600 text-sm">
                    Vous disposez de 30 jours pour retourner un produit non utilisé dans son emballage d'origine.
                </p>
            </div>
            
            <div class="bg-gradient-to-br from-[#D4AF37]/5 to-transparent rounded-2xl p-6 border border-[#D4AF37]/20">
                <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                    <i class="fas fa-clock text-[#D4AF37] mr-2"></i>
                    Délai de remboursement
                </h3>
                <p class="text-gray-600 text-sm">
                    Les remboursements sont traités sous 5-7 jours ouvrés après réception du retour.
                </p>
            </div>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 9 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Comment retourner un produit ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Connectez-vous à votre compte, allez dans "Mes commandes" et sélectionnez le produit à retourner. Suivez ensuite les instructions pour générer votre bon de retour.
                </div>
            </div>

            <!-- FAQ Item 10 -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex items-center justify-between group">
                    <span class="font-semibold text-gray-900 group-hover:text-[#D4AF37] transition">Les frais de retour sont-ils gratuits ?</span>
                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-[#D4AF37] transition"></i>
                </button>
                <div class="px-6 pb-4 text-gray-600">
                    Les retours pour défaut de fabrication sont gratuits. Pour les autres motifs, les frais de retour sont à la charge du client.
                </div>
            </div>
        </div>
    </div>

    <!-- Still Have Questions CTA -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Vous n'avez pas trouvé votre réponse ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Notre équipe est là pour vous aider ! Contactez-nous et nous vous répondrons dans les plus brefs délais.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-envelope"></i>
                Contactez-nous
            </a>
            <a href="#" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-phone-alt"></i>
                +221 78 123 45 67
            </a>
        </div>
    </div>
</div>

<!-- Add to your CSS -->
<style>
    /* Smooth scrolling for anchor links */
    html {
        scroll-behavior: smooth;
    }
    
    .scroll-mt-24 {
        scroll-margin-top: 6rem;
    }
    
    /* Smooth transitions */
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }
    
    /* Hero section gradient animation */
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .bg-gradient-to-r {
        background-size: 200% auto;
        animation: gradientShift 3s ease infinite;
    }
    
    /* FAQ accordion animation */
    .faq-item {
        transition: all 0.3s ease;
    }
    
    .faq-item.active {
        border-color: #D4AF37;
    }
    
    /* Add this JavaScript for accordion functionality */
    .faq-item .fa-chevron-down {
        transition: transform 0.3s ease;
    }
    
    .faq-item.active .fa-chevron-down {
        transform: rotate(180deg);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // FAQ Accordion functionality
        document.querySelectorAll('.faq-item button').forEach(button => {
            button.addEventListener('click', () => {
                const item = button.closest('.faq-item');
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
