<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Server Error - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .error-bg {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .error-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="error-bg min-h-screen flex items-center justify-center p-4">
    <div class="error-card rounded-2xl shadow-2xl p-8 md:p-12 max-w-2xl w-full">
        <div class="text-center">
            <!-- Server Icon with Pulse -->
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 bg-white/30 rounded-full pulse"></div>
                <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Server Error
            </h1>

            <!-- Message -->
            <div class="space-y-4 mb-6">
                <p class="text-white/80">
                    Something went wrong on our servers. Our team has been notified and is working to fix the issue.
                </p>
                <p class="text-white/70 text-sm">
                    <strong>Reference ID:</strong> 
                    <span class="font-mono bg-white/20 px-2 py-1 rounded">
                        {{ request()->header('X-Request-ID') ?: substr(md5(uniqid()), 0, 8) }}
                    </span>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <button onclick="window.location.reload()" 
                        class="bg-white text-blue-600 font-semibold py-3 px-4 rounded-lg hover:bg-gray-100 
                               transition-all duration-300 transform hover:scale-[1.02]">
                    🔄 Try Again
                </button>
                
                <a href="{{ url()->previous() }}" 
                   class="bg-transparent border-2 border-white text-white font-semibold py-3 px-4 
                          rounded-lg hover:bg-white/10 transition-all duration-300">
                    ↩️ Go Back
                </a>
                
                <a href="{{ url('/') }}" 
                   class="bg-transparent border-2 border-white text-white font-semibold py-3 px-4 
                          rounded-lg hover:bg-white/10 transition-all duration-300">
                    🏠 Go Home
                </a>
            </div>

            <!-- Recovery Timeline -->
            <div class="mt-6 p-4 bg-white/10 rounded-lg">
                <div class="flex items-center justify-center space-x-2 mb-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-white/70">Estimated Recovery Time: 5-10 minutes</span>
                </div>
                
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-green-400 h-2 rounded-full w-3/4"></div>
                </div>
            </div>

            <!-- Status & Support -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-white/10 rounded-lg">
                    <h4 class="text-white font-semibold mb-2">System Status</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-white/70">Application:</span>
                            <span class="text-red-400 font-semibold">⚠️ Degraded</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-white/70">Database:</span>
                            <span class="text-yellow-400 font-semibold">🔄 Recovering</span>
                        </div>
                    </div>
                </div>
                
                {{-- <div class="p-4 bg-white/10 rounded-lg">
                    <h4 class="text-white font-semibold mb-2">Get Help</h4>
                    <div class="space-y-2 text-sm">
                        <a href="{{ config('app.support_url', '#') }}" 
                           class="block text-white/80 hover:text-white transition-colors">
                            📖 Check Status Page
                        </a>
                        @if(config('app.support_email'))
                        <a href="mailto:{{ config('app.support_email') }}" 
                           class="block text-white/80 hover:text-white transition-colors">
                            ✉️ Email Support
                        </a>
                        @endif
                    </div>
                </div> --}}
            </div>
        </div>
    </div>

    <!-- Auto-retry logic -->
    <script>
        let retryCount = 0;
        const maxRetries = 3;
        
        function retryWithBackoff() {
            if (retryCount < maxRetries) {
                retryCount++;
                const delay = Math.min(1000 * Math.pow(2, retryCount), 30000);
                
                setTimeout(() => {
                    console.log(`Retry attempt ${retryCount}/${maxRetries}...`);
                    window.location.reload();
                }, delay);
            }
        }
        
        // Auto-retry after 30 seconds
        setTimeout(retryWithBackoff, 30000);
    </script>
</body>
</html>