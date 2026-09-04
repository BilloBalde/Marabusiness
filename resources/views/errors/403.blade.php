<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Access Denied - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .error-bg {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .error-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
        .lock-animation {
            animation: lockShake 2s infinite;
        }
        @keyframes lockShake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
    </style>
</head>
<body class="error-bg min-h-screen flex items-center justify-center p-4">
    <div class="error-card rounded-2xl shadow-2xl p-8 md:p-12 max-w-md w-full">
        <div class="text-center">
            <!-- Animated Lock Icon -->
            <div class="lock-animation w-24 h-24 mx-auto mb-6">
                <svg class="w-full h-full text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Access Denied
            </h1>

            <!-- Message -->
            <p class="text-white/80 mb-6">
                You don't have permission to access this page or resource.
            </p>

            <!-- Action Buttons -->
            <div class="space-y-3">
                @auth
                    <a href="{{ url('/orders') }}" 
                       class="w-full bg-white text-red-600 font-semibold py-3 px-6 rounded-lg hover:bg-gray-100 
                              transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
                        📊 Go to Dashboard
                    </a>
                @else
                    <a href="{{ url('/login') }}" 
                       class="w-full bg-white text-red-600 font-semibold py-3 px-6 rounded-lg hover:bg-gray-100 
                              transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
                        🔑 Login to Continue
                    </a>
                @endif
                
                <a href="{{ url()->previous() }}" 
                   class="inline-block w-full bg-transparent border-2 border-white text-white font-semibold 
                          py-3 px-6 rounded-lg hover:bg-white/10 transition-all duration-300">
                    ↩️ Go Back
                </a>
                
                <a href="{{ url('/') }}" 
                   class="inline-block w-full bg-transparent border-2 border-white text-white font-semibold 
                          py-3 px-6 rounded-lg hover:bg-white/10 transition-all duration-300">
                    🏠 Return Home
                </a>
            </div>

            <!-- Status Info -->
            <div class="mt-6 p-4 bg-white/10 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-white/70">Error Code:</span>
                    <span class="text-sm font-mono font-bold text-white">403 Forbidden</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-white/70">Your Role:</span>
                    <span class="text-sm font-semibold text-white">
                        {{ auth()->check() ? auth()->user()->roles->first()->name ?? 'User' : 'Guest' }}
                    </span>
                </div>
            </div>

            <!-- Contact Support -->
            @if(config('app.support_email'))
                <div class="mt-4 text-sm text-white/60">
                    Need access? Contact 
                    <a href="mailto:{{ config('app.support_email') }}" class="underline hover:text-white">
                        {{ config('app.support_email') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>