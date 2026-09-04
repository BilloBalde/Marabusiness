<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Session Expired - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .error-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="error-bg min-h-screen flex items-center justify-center p-4">
    <div class="error-card rounded-2xl shadow-2xl p-8 md:p-12 max-w-md w-full">
        <div class="text-center">
            <!-- Animated Icon -->
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 bg-white/20 rounded-full animate-ping"></div>
                <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Session Expired
            </h1>

            <!-- Message -->
            <p class="text-white/80 mb-6">
                Your session has expired due to inactivity. Please refresh the page to continue.
            </p>

            <!-- Action Button -->
            <div class="space-y-3">
                <button onclick="window.location.reload()" 
                        class="w-full bg-white text-purple-600 font-semibold py-3 px-6 rounded-lg hover:bg-gray-100 
                               transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
                    🔄 Refresh Page
                </button>
                
                <a href="{{ url('/') }}" 
                   class="inline-block w-full bg-transparent border-2 border-white text-white font-semibold 
                          py-3 px-6 rounded-lg hover:bg-white/10 transition-all duration-300">
                    🏠 Return Home
                </a>
            </div>

            <!-- Help Text -->
            <div class="mt-6 p-4 bg-white/10 rounded-lg">
                <p class="text-sm text-white/70">
                    <strong>Tip:</strong> This usually happens when:
                </p>
                <ul class="text-sm text-white/60 mt-2 space-y-1 text-left">
                    <li>• You've been inactive for too long</li>
                    <li>• You opened the page in multiple tabs</li>
                    <li>• Your browser cookies were cleared</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Auto-refresh after 10 seconds -->
    <!-- Add this to your 419.blade.php -->
    <script>
        // Auto-redirect to login after 5 seconds
        setTimeout(() => {
            window.location.href = "{{ $loginRoute ?? route('login') }}";
        }, 5000);
    </script>
</body>
</html>