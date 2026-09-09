<header class="sticky top-0 z-50 w-full bg-white shadow-md">
    @php
        $langLabels = ['en' => 'EN', 'fr' => 'FR', 'zh' => '中文'];
        $currentLocale = app()->getLocale();
        $currentLangLabel = $langLabels[$currentLocale] ?? strtoupper($currentLocale);
        if(Auth::check()){
            $user = auth()->user();
            // ->first() is null for a signed-in account with no role at all — every
            // registration path assigns 'customer' immediately, so this is always an
            // anomaly rather than a designed state, but reading ->name on that null
            // crashed the navbar (and so every page using it) outright for exactly
            // that account. Falls back to the least-privileged label rather than
            // hiding the account menu entirely.
            $role = $user->roles->first()->name ?? 'customer';
        }else{
            $role = 'Not logged in';
        }
        // Runs unconditionally on every page load, logged in or not. Spatie's
        // role() scope throws if the 'manager' role row itself doesn't exist (not
        // just if nobody holds it) — normally harmless since that row exists today,
        // but it means a database missing that one seeded row (a fresh deploy
        // before seeders run, a migration rolled back) would 500 the entire site
        // for every visitor. Degrades to a dead "contact us" link instead.
        try {
            $managerId = \App\Models\User::role('manager')->value('id');
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            $managerId = null;
        }
    @endphp

    {{-- ============================== --}}
    {{-- TOP BAR --}}
    {{-- ============================== --}}
    <div class="bg-[#D4AF37] text-white text-xs py-2">
        <div class="max-w-7xl mx-auto px-4 flex justify-between">
            <div class="flex space-x-4">
                <a href="#" class="hover:underline">Télécharger l'app</a>
            </div>

            <div class="flex space-x-4">
                <a wire:navigate href="/wishlist" class="flex items-center gap-x-1 text-gray-700 hover:text-[#D4AF37] font-medium">
                    <i class="fa-solid fa-heart"></i>
                    <span class="px-2 py-0.5 text-xs bg-[#F5E6B3] border border-[#D4AF37] rounded text-[#D4AF37]">
                        {{ $wishlist_count }}
                    </span>
                </a>
                <a wire:navigate href="/cart" class="flex items-center gap-x-1 text-gray-700 hover:text-[#D4AF37] font-medium">
                    <i class="fa-solid fa-shopping-cart"></i>
                    <span class="px-2 py-0.5 text-xs bg-[#F5E6B3] border border-[#D4AF37] rounded text-[#D4AF37]">
                        {{ $total_count }}
                    </span>
                </a>
            </div>

            <div class="flex items-center space-x-3">
                <a href="#" class="hover:text-gray-200"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" class="hover:text-gray-200"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="hover:text-gray-200"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="#" class="hover:text-gray-200"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="#" class="hover:text-gray-200"><i class="fa-brands fa-snapchat-ghost"></i></a>
            </div>
        </div>
    </div>

    <nav class="max-w-[85rem] mx-auto px-4 md:px-6 lg:px-8 py-2">
        <div class="flex items-center justify-between">

            {{-- ============================== --}}
            {{-- LOGO --}}
            {{-- ============================== --}}
            <a href="/" class="flex-none">
                <img src="{{ asset('assets/images/logo.png') }}" class="w-[110px] h-[80px]" alt="Logo">
            </a>

            {{-- ============================== --}}
            {{-- DESKTOP LINKS --}}
            {{-- ============================== --}}
            <div class="hidden md:flex items-center space-x-6 ml-4">

                <a href="/" class="{{ request()->is('/') ? 'text-[#D4AF37]' : 'text-gray-700 hover:text-[#D4AF37]' }}">
                    <strong>{{ __('ui.navbar.home') }}</strong>
                </a>

                <a href="/vendors" class="{{ request()->is('vendors') ? 'text-[#D4AF37]' : 'text-gray-700 hover:text-[#D4AF37]' }}">
                    <strong>{{ __('ui.navbar.vendors') }}</strong>
                </a>

                <a href="/services" class="{{ request()->is('services') ? 'text-[#D4AF37]' : 'text-gray-700 hover:text-[#D4AF37]' }}">
                    <strong>{{ __('filament.nav.services') }}</strong>
                </a>

                {{-- CATEGORIES MENU --}}
                <div class="relative group">
                    <button class="font-medium text-gray-700 group-hover:text-[#D4AF37]">
                        <strong>{{ __('ui.navbar.categories') }}</strong>
                    </button>

                    <div class="absolute left-0 top-full w-[750px] bg-white border rounded-lg shadow-xl
                                opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-200 z-50">

                        <div class="flex">

                            {{-- LEFT FAMILIES --}}
                            <div class="w-1/3 border-r bg-gray-50 rounded-l-lg">
                                <ul class="divide-y">
                                    @foreach ($families as $family)
                                        @php $key = \Str::slug($family); @endphp
                                        <li class="px-4 py-3 hover:bg-[#F5E6B3] cursor-pointer 
                                                text-sm font-medium text-gray-700 hover:text-[#D4AF37]"
                                            onmouseover="showSub('{{ $key }}')">
                                            {{ $family }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- RIGHT SUB MENU --}}
                            <div class="w-2/3 p-5" id="temu-submenu">
                                <h3 class="font-semibold text-gray-800 mb-3">Sélection</h3>
                                <p class="text-sm text-gray-600">Passez la souris sur une catégorie…</p>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="/products" class="{{ request()->is('products') ? 'text-[#D4AF37]' : 'text-gray-700 hover:text-[#D4AF37]' }}">
                    <strong>{{ __('ui.navbar.products') }}</strong>
                </a>

            </div>

            <div class="hidden md:block flex-1 px-6">
                <livewire:global-search />
            </div>

            {{-- ============================== --}}
            {{-- RIGHT SECTION (LANG, CURRENCY, CART, LOGIN) --}}
            {{-- ============================== --}}
            <div class="hidden md:flex items-center space-x-6">

                {{-- LANGUAGE DROPDOWN --}}
                <div class="relative group">
                    <button class="flex items-center gap-2 px-3 py-1 text-xs font-semibold rounded-md bg-gray-100 text-gray-700 hover:bg-[#F5E6B3]">
                        @switch($currentLocale)
                            @case('en') <span class="fi fi-us"></span> @break
                            @case('fr') <span class="fi fi-fr"></span> @break
                            @case('zh') <span class="fi fi-cn"></span> @break
                        @endswitch

                        {{ $currentLangLabel }}
                        <i class="fa-solid fa-chevron-down text-[0.6rem]"></i>
                    </button>

                    <div class="absolute right-0 w-28 bg-white border rounded-md shadow-md opacity-0 invisible
                                group-hover:opacity-100 group-hover:visible transition duration-150">

                        <a href="{{ route('lang.switch','en') }}" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F5E6B3]" wire:navigate.hover>
                            <span class="fi fi-us"></span> EN
                        </a>
                        <a href="{{ route('lang.switch','fr') }}" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F5E6B3]" wire:navigate.hover>
                            <span class="fi fi-fr"></span> FR
                        </a>
                        <a href="{{ route('lang.switch','zh') }}" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F5E6B3]" wire:navigate.hover>
                            <span class="fi fi-cn"></span> 中文
                        </a>

                    </div>
                </div>
                {{-- LOGIN --}}
                @guest
                    <a href="/login" class="py-2 px-4 bg-[#D4AF37] text-white rounded-lg hover:bg-[#C9A227]">
                        {{ __('ui.navbar.login') }}
                    </a>
                @endguest

                @auth
                    <div class="relative group">
                        <button class="flex items-center text-gray-600 hover:text-[#D4AF37]">
                            <i class="fa-solid fa-user text-xl"></i> {{ auth()->user()->name }}
                        </button>
                        <div class="absolute right-0 bg-white shadow-md rounded-lg w-40 hidden group-hover:block z-50">
                            <a href="/orders"
                            class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                <i class="fa-solid fa-box"></i> {{ __('ui.navbar.my_orders') }}
                            </a>
                            <a href="{{ route('my.addresses') }}"
                            class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                <i class="fa-solid fa-location-dot"></i> My Addresses
                            </a>

                            @if ($role == 'customer')
                                <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="/chats/{{ $managerId }}">
                                    <i class="fa-solid fa-comments"></i> {{ __('ui.navbar.my_chats') }}
                                </a>
                            @endif
                            {{-- <a href="{{ route('user.rfqs') }}" 
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-file-invoice-dollar mr-3 text-blue-600"></i>
                                My RFQs
                                @php
                                    $pendingCount = \App\Models\BulkRfq::where('user_id', auth()->id())
                                        ->where('status', 'pending')
                                        ->count();
                                @endphp
                                @if($pendingCount > 0)
                                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">
                                        {{ $pendingCount }}
                                    </span>
                                @endif
                            </a> --}}
                            @if ($role == 'vendor')
                                <a href="/vendor" target="_blank"
                                class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                    <i class="fa-solid fa-gauge"></i>Dashboard
                                </a>
                            @elseif ($role == 'manager')
                                <a href="/admin" target="_blank"
                                class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                    <i class="fa-solid fa-gauge"></i> Dashboard
                                </a>
                            @elseif ($role == 'admin')
                                <a href="/admin" target="_blank"
                                class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                    <i class="fa-solid fa-gauge"></i> Dashboard
                                </a> 
                            @endif

                            <a href="/logout"
                            class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa-solid fa-right-from-bracket"></i> {{ __('ui.navbar.logout') }}
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </div>

                    </div>
                @endauth
            </div>

            {{-- ============================== --}}
            {{-- MOBILE TOGGLE --}}
            {{-- ============================== --}}
            <div class="md:hidden">
                <button id="mobile-toggle-btn" onclick="toggleMobileMenu()" class="w-10 h-10 flex items-center justify-center border rounded-lg">
                    ☰
                </button>
            </div>

        </div>

        {{-- ============================== --}}
        {{-- MOBILE MENU --}}
        {{-- ============================== --}}
        {{-- ============================== --}}
        <div id="mobile-menu" class="hidden md:hidden mt-4 pt-4 bg-white border-t shadow-inner">
            <div class="space-y-1">
                {{-- MAIN NAV LINKS --}}
                <a href="/" class="flex items-center px-4 py-3 text-gray-800 hover:bg-[#F5E6B3] hover:text-[#D4AF37] transition-colors {{ request()->is('/') ? 'bg-[#F5E6B3] text-[#D4AF37]' : '' }}">
                    <i class="fas fa-home w-6 text-center mr-3"></i>
                    {{ __('ui.navbar.home') }}
                </a>
                
                <a href="/vendors" class="flex items-center px-4 py-3 text-gray-800 hover:bg-[#F5E6B3] hover:text-[#D4AF37] transition-colors {{ request()->is('vendors') ? 'bg-[#F5E6B3] text-[#D4AF37]' : '' }}">
                    <i class="fas fa-store w-6 text-center mr-3"></i>
                    {{ __('ui.navbar.vendors') }}
                </a>
                
                <a href="/services" class="flex items-center px-4 py-3 text-gray-800 hover:bg-[#F5E6B3] hover:text-[#D4AF37] transition-colors {{ request()->is('services') ? 'bg-[#F5E6B3] text-[#D4AF37]' : '' }}">
                    <i class="fas fa-concierge-bell w-6 text-center mr-3"></i>
                    {{ __('filament.nav.services') }}
                </a>
                
                <a href="/products" class="flex items-center px-4 py-3 text-gray-800 hover:bg-[#F5E6B3] hover:text-[#D4AF37] transition-colors {{ request()->is('products') ? 'bg-[#F5E6B3] text-[#D4AF37]' : '' }}">
                    <i class="fas fa-box-open w-6 text-center mr-3"></i>
                    {{ __('ui.navbar.products') }}
                </a>
            </div>

            {{-- MOBILE SEARCH (OPTIONAL) --}}
            <div class="px-4 py-3 border-t border-b">
                <div class="relative">
                    <livewire:global-search />
                </div>
            </div>

            {{-- CATEGORIES ACCORDION --}}
            <div class="border-t">
                <div class="px-4 py-3">
                    <h3 class="font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-list-ul mr-2"></i>
                        {{ __('ui.navbar.categories') }}
                    </h3>
                </div>
                
                @foreach ($families as $family)
                    @php $key = \Str::slug($family); @endphp
                    <div class="border-b">
                        <button onclick="toggleCat('{{ $key }}')" 
                                class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 active:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-folder w-6 text-center mr-3 text-gray-500"></i>
                                <span class="font-medium text-gray-700">{{ $family }}</span>
                            </div>
                            <i id="cat-icon-{{ $key }}" class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"></i>
                        </button>
                        
                        <div id="cat-{{ $key }}" class="hidden bg-gray-50/50 px-4 pb-2">
                            <div class="py-2 space-y-1">
                                @foreach ($menuData[$key] as $cat)
                                    <a href="/products?selectedCategories[0]={{ $cat['id'] }}" 
                                    class="block pl-10 pr-3 py-2 text-sm text-gray-600 hover:text-[#D4AF37] hover:bg-white rounded-lg transition-colors">
                                        <i class="fas fa-chevron-right text-xs mr-2 opacity-60"></i>
                                        {{ $cat['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- LANGUAGE & SETTINGS --}}
            <div class="border-t">
                <div class="px-4 py-3">
                    <h3 class="font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-cog mr-2"></i>
                        {{ __('ui.navbar.settings') }}
                    </h3>
                </div>
                
                {{-- LANGUAGE SELECTOR --}}
                <div class="px-4 py-2">
                    <div class="flex items-center justify-between px-3 py-2 bg-gray-100 rounded-lg">
                        <div class="flex items-center">
                            @switch($currentLocale)
                                @case('en') <span class="fi fi-us rounded mr-2"></span> @break
                                @case('fr') <span class="fi fi-fr rounded mr-2"></span> @break
                                @case('zh') <span class="fi fi-cn rounded mr-2"></span> @break
                            @endswitch
                            <span class="font-medium text-gray-700">{{ $currentLangLabel }}</span>
                        </div>
                        <button onclick="toggleMobileLangMenu()" class="text-gray-500">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    
                    <div id="mobile-lang-menu" class="hidden mt-2 space-y-1 pl-10">
                        <a href="{{ route('lang.switch','en') }}" 
                        class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-gray-100 {{ $currentLocale == 'en' ? 'bg-[#F5E6B3] text-[#D4AF37]' : 'text-gray-600' }}" wire:navigate.hover>
                            <span class="fi fi-us rounded"></span>
                            <span>English</span>
                        </a>
                        <a href="{{ route('lang.switch','fr') }}" 
                        class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-gray-100 {{ $currentLocale == 'fr' ? 'bg-[#F5E6B3] text-[#D4AF37]' : 'text-gray-600' }}" wire:navigate.hover>
                            <span class="fi fi-fr rounded"></span>
                            <span>Français</span>
                        </a>
                        <a href="{{ route('lang.switch','zh') }}" 
                        class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-gray-100 {{ $currentLocale == 'zh' ? 'bg-[#F5E6B3] text-[#D4AF37]' : 'text-gray-600' }}" wire:navigate.hover>
                            <span class="fi fi-cn rounded"></span>
                            <span>中文</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- USER AUTH SECTION --}}
            <div class="border-t">
                @guest
                    <a href="/login" class="flex items-center px-4 py-3 bg-gradient-to-r from-[#D4AF37] to-[#C9A227] text-white font-semibold hover:opacity-90 transition-opacity">
                        <i class="fas fa-sign-in-alt w-6 text-center mr-3"></i>
                        {{ __('ui.navbar.login') }}
                    </a>
                @endguest
                
                @auth
                    <div class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-[#D4AF37] flex items-center justify-center text-white font-semibold mr-3">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ $role }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-4 pb-3 space-y-1">
                        <a href="/orders" class="flex items-center px-4 py-2 text-gray-700 hover:bg-[#F5E6B3] rounded-lg transition-colors">
                            <i class="fas fa-box w-6 text-center mr-3"></i>
                            {{ __('ui.navbar.my_orders') }}
                        </a>
                        <a href="{{ route('my.addresses') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-[#F5E6B3] rounded-lg transition-colors">
                            <i class="fa-solid fa-location-dot w-6 text-center mr-3"></i> My Addresses
                        </a>

                        @if ($role == 'customer')
                            <a href="/chats/{{ $managerId }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-[#F5E6B3] rounded-lg transition-colors">
                                <i class="fas fa-comments w-6 text-center mr-3"></i>
                                {{ __('ui.navbar.my_chats') }}
                            </a>
                        @endif
                        <a href="/logout"
                        class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fa-solid fa-right-from-bracket"></i> {{ __('ui.navbar.logout') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                @endauth
            </div>

            {{-- SOCIAL MEDIA (MOBILE) --}}
            <div class="border-t px-4 py-3">
                <div class="flex justify-center space-x-4">
                    <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-[#D4AF37] hover:text-white">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-[#D4AF37] hover:text-white">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-[#D4AF37] hover:text-white">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- ============================== --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================== --}}
    <style>
    #mobile-menu {
        max-height: calc(100vh - 80px);
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .rotate-180 {
        transform: rotate(180deg);
        transition: transform 0.3s ease;
    }
    [id^="cat-"] {
        transition: max-height 0.3s ease;
    }
    .fi {
        box-shadow: 0 0 1px rgba(0,0,0,0.2);
    }
</style>

<script>
    window.menuData = window.menuData ?? @json($menuData);

    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');

        if (!menu.classList.contains('hidden')) {
            document.addEventListener('click', closeOnOutsideClick);
        } else {
            document.removeEventListener('click', closeOnOutsideClick);
        }
    }

    function closeOnOutsideClick(event) {
        const menu = document.getElementById('mobile-menu');
        const btn = document.getElementById('mobile-toggle-btn');

        if (!menu.contains(event.target) && !btn.contains(event.target)) {
            menu.classList.add('hidden');
            document.removeEventListener('click', closeOnOutsideClick);
        }
    }

    function toggleCat(key) {
        const catContent = document.getElementById('cat-' + key);
        const catIcon = document.getElementById('cat-icon-' + key);
        
        catContent.classList.toggle('hidden');
        catIcon.classList.toggle('fa-chevron-down');
        catIcon.classList.toggle('fa-chevron-up');
        catIcon.classList.toggle('rotate-180');
    }

    function toggleMobileLangMenu() {
        const langMenu = document.getElementById('mobile-lang-menu');
        langMenu.classList.toggle('hidden');
    }

    window.showSub = function (key) {
        const submenu = document.getElementById('temu-submenu');
        const items = window.menuData[key] || [];

        submenu.innerHTML = `
            <h3 class="font-semibold text-gray-800 mb-3">${key.replace('-', ' ')}</h3>
            <div class="grid grid-cols-2 gap-3 text-sm text-gray-600">
                ${items.map(i => `
                    <a href="/products?selectedCategories[0]=${i.id}" class="hover:text-[#D4AF37]">
                        ${i.name}
                    </a>
                `).join('')}
            </div>`;
    }
</script>
</header>
