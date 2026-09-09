<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="w-full max-w-[90rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.contact', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <!-- Header with decorative element -->
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4 tracking-tight">
            Nous <span class="text-[#D4AF37]">Contacter</span>
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
            Une question ? Une suggestion ? Notre équipe est là pour vous aider.
        </p>
        <div class="w-24 h-1 bg-[#D4AF37] mx-auto mt-6 rounded-full"></div>
    </div>

    <!-- Contact Cards Row (Top) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Phone Card -->
        <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group hover:border-[#D4AF37]">
            <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                <i class="fas fa-phone-alt text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Par Téléphone</h3>
            <p class="text-gray-600 mb-3">Lun-Ven: 8h-20h | Sam: 9h-18h</p>
            <a href="tel:+1234567890" class="text-[#D4AF37] font-semibold text-lg hover:underline">
                +123 456 7890
            </a>
        </div>

        <!-- Email Card -->
        <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group hover:border-[#D4AF37]">
            <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                <i class="fas fa-envelope text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Par Email</h3>
            <p class="text-gray-600 mb-3">Réponse sous 24h</p>
            <a href="mailto:contact@yourcompany.com" class="text-[#D4AF37] font-semibold text-lg hover:underline break-all">
                contact@yourcompany.com
            </a>
        </div>

        <!-- Live Chat Card -->
        <div class="bg-gradient-to-br from-gray-50 to-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group hover:border-[#D4AF37]">
            <div class="w-14 h-14 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[#D4AF37] transition-all duration-300">
                <i class="fas fa-comment-dots text-2xl text-[#D4AF37] group-hover:text-white transition-all duration-300"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Chat en Direct</h3>
            <p class="text-gray-600 mb-3">Discutez avec notre équipe</p>
            <button class="text-[#D4AF37] font-semibold text-lg hover:underline text-left">
                Démarrer le chat
            </button>
        </div>
    </div>

    <!-- Main Content: Form + Map/Info -->
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- LEFT: FORM (2/3 width) -->
        <div class="lg:w-2/3">
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Form Header -->
                <div class="bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-paper-plane"></i>
                        Envoyez-nous un message
                    </h2>
                    <p class="text-white/90 mt-1">Remplissez le formulaire et nous vous répondrons dans les plus brefs délais.</p>
                </div>

                <!-- Form Body -->
                <div class="p-8">
                    @if (session()->has('success'))
                        <div class="p-4 mb-6 rounded-xl bg-green-50 text-green-700 font-medium border border-green-200 flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="submit" class="space-y-6">

                        <!-- NAME & EMAIL (2 columns on desktop) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-gray-700 font-semibold text-sm">
                                    <i class="fas fa-user text-[#D4AF37] mr-2"></i>Nom complet
                                </label>
                                <input type="text"
                                       wire:model.defer="name"
                                       placeholder="Jean Dupont"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition bg-gray-50 focus:bg-white">
                                @error('name') <span class="text-sm text-red-500 flex items-center gap-1 mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-gray-700 font-semibold text-sm">
                                    <i class="fas fa-envelope text-[#D4AF37] mr-2"></i>Email
                                </label>
                                <input type="email"
                                       wire:model.defer="email"
                                       placeholder="jean@exemple.com"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition bg-gray-50 focus:bg-white">
                                @error('email') <span class="text-sm text-red-500 flex items-center gap-1 mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- SUBJECT -->
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-semibold text-sm">
                                <i class="fas fa-tag text-[#D4AF37] mr-2"></i>Sujet
                            </label>
                            <select wire:model.defer="subject" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition bg-gray-50 focus:bg-white">
                                <option value="">Sélectionnez un sujet</option>
                                <option value="info">Demande d'information</option>
                                <option value="support">Support technique</option>
                                <option value="partnership">Partenariat</option>
                                <option value="other">Autre</option>
                            </select>
                            @error('subject') <span class="text-sm text-red-500 flex items-center gap-1 mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</span> @enderror
                        </div>

                        <!-- MESSAGE -->
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-semibold text-sm">
                                <i class="fas fa-comment text-[#D4AF37] mr-2"></i>Message
                            </label>
                            <textarea wire:model.defer="message" rows="5"
                                      placeholder="Décrivez votre demande en détail..."
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20 transition bg-gray-50 focus:bg-white"></textarea>
                            @error('message') <span class="text-sm text-red-500 flex items-center gap-1 mt-1"><i class="fas fa-exclamation-circle"></i>{{ $message }}</span> @enderror
                        </div>

                        <!-- OPTIONAL: FILE ATTACHMENT (optional, can be removed) -->
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-semibold text-sm">
                                <i class="fas fa-paperclip text-[#D4AF37] mr-2"></i>Pièce jointe (optionnel)
                            </label>
                            <div class="flex items-center gap-3">
                                <label class="cursor-pointer px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-600 text-sm font-medium transition">
                                    <i class="fas fa-upload mr-2"></i>Choisir un fichier
                                    <input type="file" class="hidden">
                                </label>
                                <span class="text-xs text-gray-500">Max. 10MB</span>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <div>
                            <button type="submit"
                                    class="w-full py-4 flex items-center justify-center gap-3 rounded-xl text-lg font-bold text-white bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] hover:from-[#c9a12f] hover:to-[#D4AF37] shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5">
                                <i class="fa-solid fa-paper-plane"></i>
                                Envoyer le message
                                <span wire:loading wire:target="submit">
                                    <i class="fas fa-spinner fa-spin ml-2"></i>
                                </span>
                            </button>
                        </div>

                        <!-- PRIVACY NOTE -->
                        <p class="text-xs text-gray-500 text-center mt-4">
                            En soumettant ce formulaire, vous acceptez notre 
                            <a href="/privacy-policy" class="text-[#D4AF37] hover:underline">politique de confidentialité</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: CONTACT INFO & MAP (1/3 width) -->
        <div class="lg:w-1/3 space-y-6">
            <!-- Office Hours Card -->
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-2xl text-[#D4AF37]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Heures d'ouverture</h3>
                </div>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <span class="text-gray-600">Lundi - Vendredi</span>
                        <span class="font-semibold text-gray-900">8h00 - 20h00</span>
                    </div>
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <span class="text-gray-600">Samedi</span>
                        <span class="font-semibold text-gray-900">9h00 - 18h00</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Dimanche</span>
                        <span class="font-semibold text-red-500">Fermé</span>
                    </div>
                </div>
            </div>

            <!-- Address Card -->
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-2xl text-[#D4AF37]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Notre adresse</h3>
                </div>
                
                                <p class="text-gray-700 leading-relaxed mb-4">
                        123 Main Street<br>
                        Dakar, Sénégal
                    </p>
                    
                <!-- Simple Map Placeholder (optional) -->
                <div class="mt-4 bg-gray-200 rounded-xl h-40 flex items-center justify-center text-gray-500 border border-gray-300">
                    <div class="text-center">
                        <i class="fas fa-map-marked-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm">Carte interactive</p>
                    </div>
                </div>
                
                <a href="#" class="inline-flex items-center gap-2 text-[#D4AF37] font-medium mt-4 hover:underline">
                    <i class="fas fa-directions"></i>
                    Obtenir l'itinéraire
                </a>
            </div>

            <!-- Social Media Card -->
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-share-alt text-2xl text-[#D4AF37]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Suivez-nous</h3>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <a href="#" class="w-12 h-12 bg-gray-100 hover:bg-[#1877F2] rounded-xl flex items-center justify-center text-gray-600 hover:text-white transition-all duration-300">
                        <i class="fab fa-facebook-f text-xl"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 hover:bg-[#E4405F] rounded-xl flex items-center justify-center text-gray-600 hover:text-white transition-all duration-300">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 hover:bg-[#25D366] rounded-xl flex items-center justify-center text-gray-600 hover:text-white transition-all duration-300">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 hover:bg-[#0A66C2] rounded-xl flex items-center justify-center text-gray-600 hover:text-white transition-all duration-300">
                        <i class="fab fa-linkedin-in text-xl"></i>
                    </a>
                    <a href="#" class="w-12 h-12 bg-gray-100 hover:bg-black rounded-xl flex items-center justify-center text-gray-600 hover:text-white transition-all duration-300">
                        <i class="fab fa-tiktok text-xl"></i>
                    </a>
                </div>
                
                <p class="text-sm text-gray-500 mt-4">
                    Rejoignez notre communauté pour suivre nos actualités et offres exclusives.
                </p>
            </div>
        </div>
    </div>

    <!-- FAQ Section (optional) -->
    <div class="mt-16">
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Questions fréquentes</h2>
            <p class="text-gray-600">Trouvez rapidement une réponse à vos questions</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-[#D4AF37] transition-all duration-300">
                <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-truck text-[#D4AF37]"></i>
                    Quels sont les délais de livraison ?
                </h3>
                <p class="text-gray-600">Les délais de livraison varient entre 3-7 jours ouvrés selon votre localisation.</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-[#D4AF37] transition-all duration-300">
                <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-undo-alt text-[#D4AF37]"></i>
                    Comment retourner un produit ?
                </h3>
                <p class="text-gray-600">Vous avez 30 jours pour retourner votre produit. Consultez notre politique de retour.</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-[#D4AF37] transition-all duration-300">
                <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-credit-card text-[#D4AF37]"></i>
                    Quels moyens de paiement acceptez-vous ?
                </h3>
                <p class="text-gray-600">Nous acceptons Visa, Mastercard, Orange Money, MTN MoMo et PayPal.</p>
            </div>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-200 hover:border-[#D4AF37] transition-all duration-300">
                <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-[#D4AF37]"></i>
                    Mes données sont-elles sécurisées ?
                </h3>
                <p class="text-gray-600">Oui, nous utilisons le chiffrement SSL pour protéger vos informations.</p>
            </div>
        </div>
        
        <div class="text-center mt-8">
            <a href="/faq" class="inline-flex items-center gap-2 text-[#D4AF37] font-semibold hover:underline">
                Voir toutes les FAQ
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Add this to your CSS or <style> block -->
<style>
    /* Smooth transitions */
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 300ms;
    }
    
    /* Custom focus styles */
    input:focus, select:focus, textarea:focus {
        outline: none;
    }
    
    /* Gradient animation for submit button */
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .bg-gradient-to-r {
        background-size: 200% auto;
        animation: gradientShift 3s ease infinite;
    }
    
    /* Card hover effects */
    .hover\:border-\[\#D4AF37\]:hover {
        border-color: #D4AF37;
        box-shadow: 0 10px 30px -10px rgba(212, 175, 55, 0.2);
    }
</style>
</div>
