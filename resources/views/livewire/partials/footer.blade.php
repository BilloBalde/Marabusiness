<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<footer class="w-full bg-gray-900 text-gray-300">
    <!-- Main Footer -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        
        <!-- Top Section: Logo and Social -->
        {{-- <div class="flex flex-col md:flex-row justify-between items-center md:items-start gap-6 pb-8 border-b border-gray-800">
            <!-- Logo -->
            <a href="/" class="flex-shrink-0">
                <img src="{{ asset('assets/images/logo.png') }}" 
                     alt="MARA-BUSINESS Logo" 
                     class="h-16 w-auto object-contain brightness-0 invert">
            </a>
            
            <!-- Social Links -->
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-400 hidden lg:block">Connectez-vous avec MARA-BUSINESS</span>
                <div class="flex gap-3">
                    <a href="https://facebook.com" target="_blank" 
                       class="w-10 h-10 rounded-full bg-gray-800 hover:bg-[#1877F2] flex items-center justify-center transition-all duration-300 group">
                        <i class="fa-brands fa-facebook-f text-gray-400 group-hover:text-white"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" 
                       class="w-10 h-10 rounded-full bg-gray-800 hover:bg-[#E4405F] flex items-center justify-center transition-all duration-300 group">
                        <i class="fa-brands fa-instagram text-gray-400 group-hover:text-white"></i>
                    </a>
                    <a href="https://wa.me/0000000000" target="_blank" 
                       class="w-10 h-10 rounded-full bg-gray-800 hover:bg-[#25D366] flex items-center justify-center transition-all duration-300 group">
                        <i class="fa-brands fa-whatsapp text-gray-400 group-hover:text-white"></i>
                    </a>
                    <a href="https://linkedin.com" target="_blank" 
                       class="w-10 h-10 rounded-full bg-gray-800 hover:bg-[#0A66C2] flex items-center justify-center transition-all duration-300 group">
                        <i class="fa-brands fa-linkedin-in text-gray-400 group-hover:text-white"></i>
                    </a>
                    <a href="#" target="_blank" 
                       class="w-10 h-10 rounded-full bg-gray-800 hover:bg-[#FFFC00] flex items-center justify-center transition-all duration-300 group">
                        <i class="fa-brands fa-snapchat-ghost text-gray-400 group-hover:text-black"></i>
                    </a>
                </div>
            </div>
        </div> --}}

        <!-- Main Links Grid - 4 Columns -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 py-12">
            
            <!-- Column 1: Nous connaître -->
            <div>
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center">
                    <i class="fas fa-store text-[#D4AF37] mr-2 text-base"></i>
                    Nous connaître
                </h3>
                <ul class="space-y-3">
                    <li><a href="/about-us" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        À propos de MARA-BUSINESS
                    </a></li>
                    {{-- <li><a href="/affiliate-program" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Programme d'affiliation
                    </a></li> --}}
                    <li><a href="/contact" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Contactez-nous
                    </a></li>
                    <li><a href="/legal" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Mentions légales
                    </a></li>
                    {{-- <li><a href="/careers" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Carrières
                    </a></li>
                    <li><a href="/press" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Presse
                    </a></li>
                    <li><a href="/sustainability" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Programme éco-responsable
                    </a></li> --}}
                </ul>
            </div>
            
            <!-- Column 2: Service client & Aide (Combined) -->
            <div>
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center">
                    <i class="fas fa-headset text-[#D4AF37] mr-2 text-base"></i>
                    Service client
                </h3>
                <ul class="space-y-3 mb-6">
                    <li><a href="/returns" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Politique de retour
                    </a></li>
                    <li><a href="/intellectual-property" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Propriété intellectuelle
                    </a></li>
                    <li><a href="/delivery" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Informations de livraison
                    </a></li>
                    <li><a href="/product-safety" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Sécurité des produits
                    </a></li>
                </ul>
                
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center">
                    <i class="fas fa-life-ring text-[#D4AF37] mr-2 text-base"></i>
                    Aide
                </h3>
                <ul class="space-y-3">
                    <li><a href="/faq" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Centre d'aide & FAQ
                    </a></li>
                    <li><a href="/security" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Centre de sécurité
                    </a></li>
                    <li><a href="/buyer-protection" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Protection des achats
                    </a></li>
                    <li><a href="/digital-services" class="text-gray-400 hover:text-[#D4AF37] text-sm transition flex items-center group">
                        <i class="fas fa-chevron-right text-xs text-gray-600 mr-2 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition"></i>
                        Règlement DSA
                    </a></li>
                </ul>
            </div>
            
            <!-- Column 3: Télécharger l'app & Paiements -->
            <div>
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center">
                    <i class="fas fa-mobile-alt text-[#D4AF37] mr-2 text-base"></i>
                    Téléchargez l'app
                </h3>
                <div class="space-y-3 mb-6">
                    <div class="flex items-center gap-2 text-sm text-gray-400 mb-2 bg-gray-800/50 p-2 rounded-lg">
                        <i class="fas fa-bell text-[#D4AF37]"></i>
                        <span>Alertes de baisse de prix</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-400 mb-2 bg-gray-800/50 p-2 rounded-lg">
                        <i class="fas fa-bolt text-[#D4AF37]"></i>
                        <span>Paiement plus rapide</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-400 mb-4 bg-gray-800/50 p-2 rounded-lg">
                        <i class="fas fa-gem text-[#D4AF37]"></i>
                        <span>Offres exclusives</span>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <a href="#" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition w-full border border-gray-700 group">
                            <i class="fab fa-google-play mr-3 text-[#D4AF37] text-lg"></i>
                            <div class="flex flex-col items-start">
                                <span class="text-xs text-gray-400">Disponible sur</span>
                                <span class="text-sm font-semibold group-hover:text-[#D4AF37]">Google Play</span>
                            </div>
                        </a>
                        <a href="#" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition w-full border border-gray-700 group">
                            <i class="fab fa-apple mr-3 text-[#D4AF37] text-lg"></i>
                            <div class="flex flex-col items-start">
                                <span class="text-xs text-gray-400">Télécharger sur</span>
                                <span class="text-sm font-semibold group-hover:text-[#D4AF37]">App Store</span>
                            </div>
                        </a>
                    </div>
                </div>
                
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center">
                    <i class="fas fa-credit-card text-[#D4AF37] mr-2 text-base"></i>
                    Paiements acceptés
                </h3>
                <div class="flex flex-wrap gap-2">
                    <span class="px-3 py-1.5 bg-gray-800 text-gray-300 text-xs font-semibold rounded-lg border border-gray-700 hover:border-[#D4AF37] transition">VISA</span>
                    <span class="px-3 py-1.5 bg-gray-800 text-gray-300 text-xs font-semibold rounded-lg border border-gray-700 hover:border-[#D4AF37] transition">OM</span>
                    <span class="px-3 py-1.5 bg-gray-800 text-gray-300 text-xs font-semibold rounded-lg border border-gray-700 hover:border-[#D4AF37] transition">MoMo</span>
                    <span class="px-3 py-1.5 bg-gray-800 text-gray-300 text-xs font-semibold rounded-lg border border-gray-700 hover:border-[#D4AF37] transition">CASH</span>
                </div>
            </div>
            
            <!-- Column 4: Contact & Support -->
            <div>
                <h3 class="font-bold text-white mb-4 text-sm uppercase tracking-wider flex items-center">
                    <i class="fas fa-envelope text-[#D4AF37] mr-2 text-base"></i>
                    Support client
                </h3>
                
                <div class="space-y-4">
                    <div class="bg-gray-800/50 p-4 rounded-lg border border-gray-700">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-[#D4AF37]/20 flex items-center justify-center">
                                <i class="fas fa-phone-alt text-[#D4AF37]"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Service client</p>
                                <p class="text-white font-semibold">+221 78 123 45 67</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#D4AF37]/20 flex items-center justify-center">
                                <i class="fas fa-clock text-[#D4AF37]"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Disponibilité</p>
                                <p class="text-white text-sm">Lun-Ven: 8h-20h</p>
                                <p class="text-white text-sm">Sam: 9h-18h</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800/50 p-4 rounded-lg border border-gray-700">
                        <h4 class="text-white text-sm font-semibold mb-2 flex items-center">
                            <i class="fas fa-shield-alt text-[#D4AF37] mr-2"></i>
                            Certifications
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <div class="flex items-center gap-1 text-xs text-gray-400 bg-gray-900 px-2 py-1 rounded">
                                <i class="fas fa-lock text-green-500"></i>
                                <span>SSL Secure</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-gray-400 bg-gray-900 px-2 py-1 rounded">
                                <i class="fas fa-star text-yellow-500"></i>
                                <span>Trustpilot</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-gray-400 bg-gray-900 px-2 py-1 rounded">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Verified</span>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <div class="flex">
                                @for($i=1; $i<=5; $i++)
                                    <i class="fas fa-star text-yellow-500 text-xs"></i>
                                @endfor
                            </div>
                            <span class="text-xs text-gray-400">4.5/5 (2,500+ avis)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar with Copyright and Legal Links -->
        <div class="pt-8 mt-8 border-t border-gray-800">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <!-- Copyright -->
                <p class="text-sm text-gray-400">
                    &copy; {{ now()->year }} {{ config('app.name') }}. Tous droits réservés. 
                    <span class="text-gray-600 mx-2">|</span>
                    Mis en place par <a href="https://jineiyatech.com" target="_blank" class="text-[#D4AF37] hover:underline">JINEIYATECH</a>
                </p>
                
                <!-- Legal Links -->
                <div class="flex flex-wrap justify-center gap-4 text-xs">
                    <a href="/privacy-policy" class="text-gray-400 hover:text-[#D4AF37] transition">Politique de confidentialité</a>
                    <a href="/terms-of-use" class="text-gray-400 hover:text-[#D4AF37] transition">Conditions d'utilisation</a>
                    <a href="/cookies" class="text-gray-400 hover:text-[#D4AF37] transition">Cookies</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tiny extra dark bar at very bottom for depth -->
    <div class="bg-gray-950 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-xs text-gray-600 text-center">
                {{ config('app.name') }} - Votre partenaire de confiance pour le commerce en Afrique
            </p>
        </div>
    </div>
</footer>

<style>
/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

footer ul li {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}

footer ul li:nth-child(1) { animation-delay: 0.1s; }
footer ul li:nth-child(2) { animation-delay: 0.15s; }
footer ul li:nth-child(3) { animation-delay: 0.2s; }
footer ul li:nth-child(4) { animation-delay: 0.25s; }
footer ul li:nth-child(5) { animation-delay: 0.3s; }
footer ul li:nth-child(6) { animation-delay: 0.35s; }
footer ul li:nth-child(7) { animation-delay: 0.4s; }

/* Subtle gradient overlay */
footer {
    position: relative;
}

footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.3), transparent);
}

/* Hover effects for payment badges */
footer .border-gray-700:hover {
    border-color: #D4AF37;
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    footer .grid {
        gap: 2rem;
    }
}
</style>
</div>
