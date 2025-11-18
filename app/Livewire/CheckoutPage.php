<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use Stripe\Stripe;
use Stripe\Checkout\Session;

#[Title('Checkout Page - MARA BUSINESS')]
class CheckoutPage extends Component
{
    use WithFileUploads;
    public $first_name;
    public $last_name;
    public $city;
    public $phone;
    public $street_address;
    public $state;
    public $zip_code;
    public $payment_method;
    public $image; // Changed from screenshot to image for consistency
    public $amount; // Default amount to 0

    public function mount(){
        $cart_items = CartManagement::getCartItemsFromCookie();
        if (count($cart_items) == 0) {
            return redirect()->route('products');
        }
    }
    public function placeOrder(){
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
            'amount' => $this->payment_method === 'om' ? 'required|numeric|min:1' : 'nullable',
            'image' => $this->payment_method === 'om' ? 'required|image|max:2048' : 'nullable',
        ]);

        //dd($this->first_name);
        // Here you would typically process the order, e.g., save it to the database
        // For now, we'll just clear the cart and redirect to a thank you page
        try {
            $cart_items = CartManagement::getCartItemsFromCookie();
            $line_items = [];
            foreach ($cart_items as $item) {
                $line_items[] = [
                    'price_data' =>[
                        'currency' => 'gnf',
                        'unit_amount' => $item['unit_amount'] * 100,
                        'product_data' => [
                            'name' => $item['name']],
                        ],
                    'quantity' => $item['quantity'],
                ];
            }
            $order = new Order();
            $order->order_number = Order::generateOrderNumber();
            $order->user_id = Auth::check() ? Auth::user()->id : User::first()->id;
            $order->grand_total = CartManagement::calculateGrandTotal($cart_items);
            $order->payment_method = $this->payment_method;
            $order->payment_status = 'pending';
            $order->status = 'new';
            $order->currency = 'GNF';
            $order->shipping_amount = 0;
            $order->shipping_method = 'none';
            $order->notes = 'Commande faite par ' . (Auth::check() ? Auth::user()->name : User::first()->name);

            $address = new Address();
            $address->first_name = $this->first_name;
            $address->last_name = $this->last_name;
            $address->city = $this->city;
            $address->phone = $this->phone;
            $address->street_address = $this->street_address;
            $address->state = $this->state;
            $address->zip_code = $this->zip_code;
            $user = Auth::check() ? Auth::user() : User::first();

            $redirect_url = '';

            if ($this->payment_method == 'stripe') {
                Stripe::setApiKey(env('STRIPE_SECRET'));
                $sessionCheckout = Session::create([
                    'payment_method_types' => ['card'],
                    'customer_email' => $user->email,
                    'line_items' => $line_items,
                    'mode' => 'payment',
                    'success_url' => route('success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('cancel'),
                ]);
                $redirect_url = $sessionCheckout->url;
            } else {
                $redirect_url = route('success');
            }
            $order->save();
            $address->order_id = $order->id;
            $address->save();
            $order->items()->createMany($cart_items);

            $paiement = \App\Models\Paiement::create([
                'order_id' => $order->id,
                'amount' => $this->amount ?? 0,
                'image' => $this->image ? $this->image->store('payments', 'public_uploads') : 'payments/default.png',
                'payment_method' => $this->payment_method,
                'currency' => 'GNF',
                'payment_status' => $this->amount >= $order->grand_total ? 'paid' : 'partial',
                'transaction_id' => \App\Models\Order::generateTransactionNumber(),
            ]);

            // 🔁 Recalculate total_paid and total_remaining
            $totalPaid = $order->paiements()->sum('amount');
            $totalRemaining = max(0, $order->grand_total - $totalPaid);

            // 🔄 Update the order
            $order->update([
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
            ]);

            // Clear the cart
            CartManagement::clearCartItems();
            Mail::to($user->email)->send(new \App\Mail\OrderPlaced($order));

            return redirect($redirect_url);
        } catch (\Exception $e) {
            Log::error('Order Placement Error: '.$e->getMessage());
            abort(500, 'Something went wrong while placing your order.');
        }
    }
    public function render()
    {
        $cart_items = CartManagement::getCartItemsFromCookie();
        $grand_total = CartManagement::calculateGrandTotal($cart_items);

        return view('livewire.checkout-page',[
            'cart_items' => $cart_items,
            'grand_total' => $grand_total,
        ]);
    }
}
