<!DOCTYPE html>
<html lang="en" class="h-full" class="scroll-smooth" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'MARA BUSINESS' }}</title>
    <meta property="og:title" content="{{ $title ?? 'MARA BUSINESS' }}">
    <meta property="og:description" content="{{ $description ?? 'MARA BUSINESS - Plateforme de vente de telephones et accessoires.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('assets/images/og-image.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* Carousel dots */
        .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #cbd5e1; /* Tailwind slate-300 */
        cursor: pointer;
        transition: background-color 0.3s ease;
        }
        .dot.active {
        background-color: #2563eb; /* Tailwind blue-600 */
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons/css/flag-icons.min.css">
</head>
<body class="flex flex-col min-h-screen font-sans text-gray-900 bg-gray-50">

    {{-- Navbar --}}
    <livewire:partials.navbar />

    {{-- Main Content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="py-0 text-center text-white bg-gray-500">
        <livewire:partials.footer />
    </footer>
    @if (session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg dark:bg-green-800 dark:text-green-100 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    @livewireScripts
    @livewireScriptConfig

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- <script>
    document.addEventListener('navbar-refresh-triggered', () => {
        Livewire.dispatch('cart-updated');
    });
    </script> --}}


    <script>
    document.addEventListener('livewire:init', () => {

        // SweetAlert after adding to cart
        Livewire.on('cart-added', () => {
            Swal.fire({
                title: 'Ajouté au panier !',
                text: 'Le produit a été ajouté avec succès.',
                icon: 'success',
                confirmButtonText: 'OK',
                timer: 1000,
                showConfirmButton: false,
            });
        });
        Livewire.on('cart-removed', () => {
            Swal.fire({
                title: 'Suppression au panier !',
                text: 'Le produit a été retiré avec succès.',
                icon: 'success',
                confirmButtonText: 'OK',
                timer: 1000,
                showConfirmButton: false,
            });
        });

    });
    </script>


</body>
</html>
