<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.about-us', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Hero Section with Background -->
    <div class="relative rounded-3xl overflow-hidden mb-16">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#D4AF37]/90 to-[#c9a12f]/90 z-10"></div>
        <div class="absolute inset-0 bg-[url('/assets/images/about-hero-bg.jpg')] bg-cover bg-center"></div>
        
        <!-- Content -->
        <div class="relative z-20 py-20 px-8 md:px-16 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                À propos de <span class="text-gray-900">MARA BUSINESS</span>
            </h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto opacity-90">
                Votre partenaire de confiance pour la distribution et la logistique en Afrique et dans le monde
            </p>
            <div class="w-24 h-1 bg-white mx-auto mt-8 rounded-full"></div>
        </div>
    </div>

    <!-- Mission & Vision Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <!-- Mission Card -->
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#D4AF37] transition-all duration-300">
                <i class="fas fa-bullseye text-3xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Notre Mission</h2>
            <p class="text-gray-600 leading-relaxed">
                Fournir des solutions logistiques innovantes et fiables qui connectent les entreprises 
                africaines au marché mondial, en garantissant qualité, rapidité et transparence à chaque étape.
            </p>
        </div>

        <!-- Vision Card -->
        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100 hover:shadow-2xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#D4AF37] transition-all duration-300">
                <i class="fas fa-eye text-3xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Notre Vision</h2>
            <p class="text-gray-600 leading-relaxed">
                Devenir le leader panafricain de la distribution et de la logistique, 
                en créant un écosystème commercial durable qui favorise la croissance économique du continent.
            </p>
        </div>
    </div>

    <!-- Story Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
        <!-- Left: Story Content -->
        <div class="space-y-6">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm">
                Notre Histoire
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
                Plus qu'une entreprise, <br><span class="text-[#D4AF37]">une passion pour l'Afrique</span>
            </h2>
            
            <div class="space-y-4 text-gray-600 leading-relaxed">
                <p>
                    <span class="font-bold text-gray-900">MARA BUSINESS</span> est née d'une vision simple mais puissante : 
                    faciliter le commerce entre l'Afrique et le reste du monde. Fondée en 2020, notre entreprise a commencé 
                    avec une petite équipe passionnée et un entrepôt modeste à Dakar.
                </p>
                <p>
                    Aujourd'hui, nous sommes fiers de servir des centaines de clients à travers le continent, 
                    avec des bureaux dans 5 pays africains et des partenariats logistiques internationaux. 
                    Notre croissance est le reflet de notre engagement envers l'excellence et la satisfaction client.
                </p>
                <p>
                    Nous croyons fermement au potentiel de l'Afrique et travaillons chaque jour pour créer 
                    des ponts commerciaux durables qui bénéficient aux communautés locales et internationales.
                </p>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 pt-6">
                <div>
                    <div class="text-3xl font-bold text-[#D4AF37]">500+</div>
                    <div class="text-sm text-gray-500">Clients satisfaits</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-[#D4AF37]">5</div>
                    <div class="text-sm text-gray-500">Pays couverts</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-[#D4AF37]">10k+</div>
                    <div class="text-sm text-gray-500">Produits livrés</div>
                </div>
            </div>
        </div>

        <!-- Right: Image/Illustration -->
        <div class="relative">
            <div class="absolute -top-4 -left-4 w-24 h-24 bg-[#D4AF37]/10 rounded-3xl"></div>
            <div class="absolute -bottom-4 -right-4 w-32 h-32 bg-[#D4AF37]/10 rounded-3xl"></div>
            <div class="relative rounded-3xl overflow-hidden shadow-2xl">
                <img src="https://images.unsplash.com/photo-1494412519320-aa613dfb7733?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" 
                     alt="Logistics in Africa" 
                     class="w-full h-full object-cover">
                <!-- Overlay gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
            </div>
        </div>
    </div>

    <!-- Values Section -->
    <div class="bg-gradient-to-br from-gray-50 to-white rounded-3xl p-8 md:p-12 mb-16">
        <div class="text-center mb-12">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Nos Valeurs
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Ce qui nous guide</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Value 1 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 group">
                <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                    <i class="fas fa-hand-holding-heart text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Intégrité</h3>
                <p class="text-gray-600 text-sm">La transparence et l'honnêteté dans toutes nos relations commerciales.</p>
            </div>

            <!-- Value 2 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 group">
                <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                    <i class="fas fa-rocket text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Innovation</h3>
                <p class="text-gray-600 text-sm">Toujours à la recherche de solutions plus efficaces et modernes.</p>
            </div>

            <!-- Value 3 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 group">
                <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                    <i class="fas fa-users text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Communauté</h3>
                <p class="text-gray-600 text-sm">Construire ensemble un avenir prospère pour l'Afrique.</p>
            </div>

            <!-- Value 4 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 group">
                <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                    <i class="fas fa-medal text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Excellence</h3>
                <p class="text-gray-600 text-sm">La qualité et la satisfaction client au cœur de notre métier.</p>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <div class="mb-16">
        <div class="text-center mb-12">
            <div class="inline-block px-4 py-2 bg-[#D4AF37]/10 rounded-full text-[#D4AF37] font-semibold text-sm mb-4">
                Notre Équipe
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Les talents derrière MARA BUSINESS</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Une équipe passionnée, diversifiée et engagée à votre service
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Team Member 1 -->
            <div class="group">
                <div class="relative rounded-2xl overflow-hidden mb-4">
                    <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=687&q=80" 
                         alt="Amadou Diallo" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute bottom-4 left-4 text-white transform translate-y-10 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                        <p class="text-sm">Fondateur & CEO</p>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Amadou Diallo</h3>
                <p class="text-[#D4AF37] text-sm">Fondateur & CEO</p>
            </div>

            <!-- Team Member 2 -->
            <div class="group">
                <div class="relative rounded-2xl overflow-hidden mb-4">
                    <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=687&q=80" 
                         alt="Fatou Ndiaye" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute bottom-4 left-4 text-white transform translate-y-10 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                        <p class="text-sm">Directrice des Opérations</p>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Fatou Ndiaye</h3>
                <p class="text-[#D4AF37] text-sm">Directrice des Opérations</p>
            </div>

            <!-- Team Member 3 -->
            <div class="group">
                <div class="relative rounded-2xl overflow-hidden mb-4">
                    <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&auto=format&fit=crop&w=687&q=80" 
                         alt="Moussa Keita" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute bottom-4 left-4 text-white transform translate-y-10 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                        <p class="text-sm">Directeur Commercial</p>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Moussa Keita</h3>
                <p class="text-[#D4AF37] text-sm">Directeur Commercial</p>
            </div>

            <!-- Team Member 4 -->
            <div class="group">
                <div class="relative rounded-2xl overflow-hidden mb-4">
                    <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-4.0.3&auto=format&fit=crop&w=761&q=80" 
                         alt="Aminata Sow" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute bottom-4 left-4 text-white transform translate-y-10 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                        <p class="text-sm">Responsable Logistique</p>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Aminata Sow</h3>
                <p class="text-[#D4AF37] text-sm">Responsable Logistique</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl p-8 md:p-12 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Prêt à travailler avec nous ?</h2>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            Rejoignez les centaines d'entreprises qui nous font confiance pour leurs besoins logistiques
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/contact" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-[#D4AF37] hover:bg-[#c9a12f] text-gray-900 font-bold rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                <i class="fas fa-envelope"></i>
                Contactez-nous
            </a>
            <a href="/products" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-transparent hover:bg-white/10 text-white border-2 border-white/30 font-bold rounded-xl transition-all duration-300">
                <i class="fas fa-store"></i>
                Découvrir nos produits
            </a>
        </div>
    </div>
</div>

<!-- Add to your CSS -->
<style>
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
    
    /* Team member hover effect */
    .group:hover .group-hover\:scale-105 {
        transform: scale(1.05);
    }
    
    /* Stats counter animation (optional) */
    @keyframes countUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .grid-cols-3 > div {
        animation: countUp 0.5s ease-out forwards;
    }
</style>