<header class="sticky top-0 z-50 w-full bg-white shadow-md">
    @php
        $langLabels = ['en' => 'EN', 'fr' => 'FR', 'zh' => '中文'];
        $currentLocale = app()->getLocale();
        $currentLangLabel = $langLabels[$currentLocale] ?? strtoupper($currentLocale);
        if(Auth::check()){
            $user = auth()->user();
            $role = $user->roles->first()->name;
        }else{
            $role = 'Not logged in';
        }
        $managerId = \App\Models\User::role('manager')->value('id');
    @endphp

    {{-- ============================== --}}
    {{-- TOP BAR --}}
    {{-- ============================== --}}
    <div class="bg-[#D4AF37] text-white text-xs py-2">
        <div class="max-w-7xl mx-auto px-4 flex justify-between">
            <div class="flex space-x-4">
                <a href="#" class="hover:underline">Télécharger l'app</a>
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
                <img src="{{ asset('assets/images/logo.png') }}" class="w-[110px] h-[60px]" alt="Logo">
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

            {{-- ============================== --}}
            {{-- SEARCH BAR --}}
            {{-- ============================== --}}
            {{-- <div class="hidden md:block flex-1 px-6">
                <div class="relative">
                    <input type="text"
                        placeholder="Rechercher un produit..."
                        class="w-full bg-gray-100 px-4 py-3 pr-12 rounded-full focus:ring-2 focus:ring-[#D4AF37]">
                    <button class="absolute inset-y-0 right-0 flex items-center justify-center px-4
                                bg-[#D4AF37] text-white rounded-r-full hover:bg-[#C9A227]">🔍</button>
                </div>
            </div> --}}
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

                        <a href="{{ route('locale.switch','en') }}" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F5E6B3]">
                            <span class="fi fi-us"></span> EN
                        </a>
                        <a href="{{ route('locale.switch','fr') }}" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F5E6B3]">
                            <span class="fi fi-fr"></span> FR
                        </a>
                        <a href="{{ route('locale.switch','zh') }}" class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F5E6B3]">
                            <span class="fi fi-cn"></span> 中文
                        </a>

                    </div>
                </div>

                {{-- CURRENCY SELECT --}}
                {{-- <select wire:model="currencyCode" class="min-w-[110px] px-2 py-1 rounded-md border border-gray-200 text-sm text-gray-700 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
                    <option value="">{{ __('ui.navbar.currency') }}</option>

                    @foreach ($currencies as $cur)
                        <option value="{{ $cur['code'] }}">{{ $cur['code'] }}</option>
                    @endforeach
                </select> --}}


                {{-- CART --}}
                <a wire:navigate href="/cart" class="flex items-center gap-x-1 text-gray-700 hover:text-[#D4AF37] font-medium">
                    🛒 {{ __('ui.navbar.cart') }}
                    <span class="px-2 py-0.5 text-xs bg-[#F5E6B3] border border-[#D4AF37] rounded text-[#D4AF37]">
                        {{ $total_count }}
                    </span>
                </a>

                {{-- LOGIN --}}
                @guest
                    <a href="/login" class="py-2 px-4 bg-[#D4AF37] text-white rounded-lg hover:bg-[#C9A227]">
                        {{ __('ui.navbar.login') }}
                    </a>
                @endguest

                @auth
                    <div class="relative group">
                        <button class="flex items-center text-gray-600 hover:text-[#D4AF37]">
                            <i class="fa-solid fa-user text-xl"></i>
                        </button>
                        <div class="absolute right-0 mt-1 bg-white shadow-md rounded-lg w-40 hidden group-hover:block z-50">
                            <a href="/orders"
                            class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                <i class="fa-solid fa-box"></i> {{ __('ui.navbar.my_orders') }}
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

                            <a href="/logout"
                            class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                                <i class="fa-solid fa-right-from-bracket"></i> {{ __('ui.navbar.logout') }}
                            </a>
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
        <div id="mobile-menu" class="hidden mt-4 border-t pt-4">

            <a href="/" class="block py-2 hover:text-[#D4AF37]">{{ __('ui.navbar.home') }}</a>
            <a href="/vendors" class="block py-2 hover:text-[#D4AF37]">{{ __('ui.navbar.vendors') }}</a>
            <a href="/services" class="block py-2 hover:text-[#D4AF37]">{{ __('filament.nav.services') }}</a>
            <a href="/products" class="block py-2 hover:text-[#D4AF37]">{{ __('ui.navbar.products') }}</a>

            {{-- MOBILE CATEGORIES --}}
            <div class="mt-3">
                <p class="font-semibold text-gray-800 mb-1">Catégories</p>

                @foreach ($families as $family)
                    @php $key = \Str::slug($family); @endphp
                    <div class="border rounded-md mb-2">
                        <button onclick="toggleCat('{{ $key }}')" class="w-full text-left px-3 py-2 font-medium text-gray-700 hover:text-[#D4AF37]">
                            {{ $family }}
                        </button>

                        <div id="cat-{{ $key }}" class="hidden px-4 pb-2">
                            @foreach ($menuData[$key] as $cat)
                                <a href="/products?selectedCategories[0]={{ $cat['id'] }}" class="block py-1 text-sm text-gray-600 hover:text-[#D4AF37]">
                                    {{ $cat['name'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- MOBILE CART --}}
            <a wire:navigate href="/cart" class="block py-2 hover:text-[#D4AF37]">
                🛒 Panier
                <span class="ml-2 px-2 py-0.5 text-xs bg-[#F5E6B3] border border-[#D4AF37] rounded text-[#D4AF37]">
                    {{ $total_count }}
                </span>
            </a>

            {{-- MOBILE LANG --}}
            <div class="mt-3 border rounded-md">
                <button onclick="toggleMobileLangMenu()" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 hover:text-[#D4AF37]">
                    @switch($currentLocale)
                        @case('en') <span class="fi fi-us"></span> @break
                        @case('fr') <span class="fi fi-fr"></span> @break
                        @case('zh') <span class="fi fi-cn"></span> @break
                    @endswitch

                    {{ $currentLangLabel }}
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </button>

                <div id="mobile-lang-menu" class="hidden border-t">
                    <a href="{{ route('locale.switch','en') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[#F5E6B3]">
                        <span class="fi fi-us"></span> EN
                    </a>
                    <a href="{{ route('locale.switch','fr') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[#F5E6B3]">
                        <span class="fi fi-fr"></span> FR
                    </a>
                    <a href="{{ route('locale.switch','zh') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-[#F5E6B3]">
                        <span class="fi fi-cn"></span> 中文
                    </a>
                </div>

                {{-- MOBILE CURRENCY --}}
                {{-- <div class="py-2">
                    <select wire:model="currencyCode"
                            class="w-full px-3 py-2 rounded-md border border-gray-200 text-sm text-gray-700">
                        <option value="">Currency</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur['code'] }}">{{ $cur['code'] }}</option>
                        @endforeach
                    </select>
                </div> --}}
            </div>

            @guest
            <a href="/login" class="block py-2 bg-[#D4AF37] text-white rounded-lg text-center mt-2">
                Login
            </a>
            @endguest

            @auth
                <div class="relative group">
                    <button class="flex items-center text-gray-600 hover:text-[#D4AF37]">
                        <i class="fa-solid fa-user text-xl"></i>
                    </button>
                    <div class="absolute right-0 mt-1 bg-white shadow-md rounded-lg w-40 hidden group-hover:block z-50">
                        <a href="/orders"
                        class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                            <i class="fa-solid fa-box"></i> {{ __('ui.navbar.my_orders') }}
                        </a>
                        @if ($role == 'customer')
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" href="/chats/{{ $managerId }}"><i class="fa-solid fa-comments"></i> {{ __('ui.navbar.my_chats') }}</a>
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
                        <a href="/logout"
                        class="block px-4 py-2 text-gray-700 hover:bg-[#F5E6B3]">
                            <i class="fa-solid fa-right-from-bracket"></i> {{ __('ui.navbar.logout') }}
                        </a>
                    </div>

                </div>
            @endauth

        </div>
    </nav>

    {{-- ============================== --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================== --}}
    <script>
        const menuData = @json($menuData);

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
            document.getElementById('cat-' + key).classList.toggle('hidden');
        }

        function toggleMobileLangMenu() {
            document.getElementById('mobile-lang-menu').classList.toggle('hidden');
        }

        window.showSub = function (key) {
            const submenu = document.getElementById('temu-submenu');
            const items = menuData[key] || [];

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
