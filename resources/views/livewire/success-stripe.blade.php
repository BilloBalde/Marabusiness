<div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    @if($processing)
        <!-- Processing Screen -->
        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full text-center">
            <div class="mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-blue-100 text-blue-500">
                <i class="fa-solid fa-spinner fa-spin text-4xl"></i>
            </div>
            
            <h1 class="text-2xl font-extrabold text-gray-800 mb-2">
                Processing Payment...
            </h1>
            
            <p class="text-gray-600 mb-6">
                Please wait while we verify your payment.
            </p>
            
            <div class="animate-pulse">
                <div class="h-2 bg-gray-200 rounded mb-2"></div>
                <div class="h-2 bg-gray-200 rounded mb-2"></div>
                <div class="h-2 bg-gray-200 rounded"></div>
            </div>
        </div>
        
    @elseif($error)
        <!-- Error Screen -->
        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full text-center">
            <div class="mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-red-100 text-red-500">
                <i class="fa-solid fa-exclamation-triangle text-4xl"></i>
            </div>
            
            <h1 class="text-2xl font-extrabold text-gray-800 mb-2">
                Payment Error
            </h1>
            
            <p class="text-gray-600 mb-6">
                {{ $error }}
            </p>
            
            <div class="mt-8 space-y-3">
                {{-- $orderId is null whenever this screen is reached with no
                    session_id at all (the most common case: someone opens this
                    page directly, or refreshes it after the session already
                    expired) — route('my-orders.show', null) against a required
                    {order_id} segment throws UrlGenerationException, turning a
                    "payment didn't go through" message into a 500. Only offer
                    this link when there is actually an order to point it at. --}}
                @if($orderId)
                    <a href="{{ route('my-orders.show', $orderId) }}"
                        class="block w-full py-3 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300 shadow">
                        ← Back to Order
                    </a>
                @endif

                <a href="{{ route('my-orders') }}"
                    class="block w-full py-3 bg-gray-800 text-white rounded-xl font-semibold hover:bg-gray-900 shadow">
                    View All Orders
                </a>
            </div>
        </div>

    @elseif($order && $payment)
        <!-- Success Screen -->
        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full text-center relative overflow-hidden">

            <!-- CONFETTI CANVAS -->
            <canvas id="confettiCanvas" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>

            <!-- BIG CHECK -->
            <div class="mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-[#D4AF37] text-white shadow-lg animate-bounce">
                <i class="fa-solid fa-check text-4xl"></i>
            </div>

            <!-- TITLE -->
            <h1 class="text-3xl font-extrabold text-[#D4AF37] mb-2">
                Paiement Réussi !
            </h1>

            <p class="text-gray-600 text-lg">
                Merci 🎉 votre paiement a été traité avec succès.
            </p>

            <!-- PAYMENT DETAILS CARD -->
            <div class="mt-6 bg-gray-100 rounded-xl p-5 text-left shadow-inner">
                <h2 class="font-semibold text-gray-700 text-lg mb-3">💳 Détails du paiement</h2>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Méthode:</span>
                        <span class="font-semibold">💳 Stripe</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Montant payé:</span>
                        <span class="font-bold text-green-600">
                            {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Transaction ID:</span>
                        <span class="text-sm font-mono">{{ substr($payment->transaction_id, 0, 20) }}...</span>
                    </div>
                </div>
            </div>

            <!-- ORDER CARD -->
            <div class="mt-4 bg-gray-100 rounded-xl p-5 text-left shadow-inner">
                <h2 class="font-semibold text-gray-700 text-lg mb-3">📦 Résumé de la commande</h2>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Numéro:</span>
                        <span class="font-bold text-indigo-600">#{{ $order->order_number }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Vendeur:</span>
                        <span class="font-semibold">{{ $order->vendor->store_name ?? 'Vendor' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Montant total:</span>
                        <span class="font-bold">{{ number_format($order->grand_total, 2) }} {{ $order->vendor->currency->code ?? 'USD' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Total payé:</span>
                        <span class="font-bold text-green-600">{{ number_format($order->total_paid, 2) }} {{ $order->vendor->currency->code ?? 'USD' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Reste:</span>
                        <span class="font-bold {{ $order->total_remaining > 0 ? 'text-red-500' : 'text-green-500' }}">
                            {{ number_format($order->total_remaining, 2) }} {{ $order->vendor->currency->code ?? 'USD' }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Statut:</span>
                        <span class="px-2 py-1 rounded text-xs font-semibold
                            {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                               ($order->payment_status === 'partial' ? 'bg-yellow-100 text-yellow-800' : 
                               'bg-red-100 text-red-800') }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="mt-8 space-y-3">
                <a href="{{ route('my-orders.show', $order->id) }}"
                    class="block w-full py-3 bg-[#D4AF37] text-white rounded-xl font-semibold hover:bg-[#D4AF37]/90 shadow transition">
                    👁️ Voir les détails de la commande
                </a>

                <a href="{{ route('my-orders') }}"
                    class="block w-full py-3 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300 shadow transition">
                    📋 Toutes mes commandes
                </a>

                <a href="{{ route('products') }}"
                    class="block w-full py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 shadow transition">
                    🛍️ Retour à la boutique
                </a>
            </div>
            
            <!-- EMAIL NOTE -->
            <div class="mt-6 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-envelope mr-2"></i>
                    Un email de confirmation a été envoyé à votre adresse email.
                </p>
            </div>
        </div>
    @else
        <!-- Default Error -->
        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full text-center">
            <div class="mx-auto mb-4 w-20 h-20 rounded-full flex items-center justify-center bg-gray-100 text-gray-400">
                <i class="fa-solid fa-question text-4xl"></i>
            </div>
            
            <h1 class="text-2xl font-extrabold text-gray-800 mb-2">
                Session Expired
            </h1>
            
            <p class="text-gray-600 mb-6">
                This payment session has expired or is invalid.
            </p>
            
            <a href="{{ route('home') }}"
                class="inline-block w-full py-3 bg-[#D4AF37] text-white rounded-xl font-semibold hover:bg-[#D4AF37]/90 shadow">
                Return to Home
            </a>
        </div>
    @endif
</div>

@if(!$processing && !$error && $order && $payment)
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        confetti({
            particleCount: 200,
            spread: 120,
            origin: { y: 0.6 }
        });
    }, 300);
});
</script>
@endif