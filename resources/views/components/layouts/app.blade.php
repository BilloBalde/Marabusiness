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
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="flex flex-col min-h-screen font-sans text-gray-900 bg-gray-50">

    {{-- Navbar --}}
    @livewire('partials.navbar')

    {{-- Main Content --}}
    <main class="flex-grow">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="py-0 text-center text-white bg-gray-500">
        @livewire('partials.footer')
    </footer>
    @if (session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg dark:bg-green-800 dark:text-green-100 dark:border-green-700">
            {{ session('success') }}
        </div>
    @endif

    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
