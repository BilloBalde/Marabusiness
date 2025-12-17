<?php
// app/Livewire/RfqButton.php
namespace App\Livewire;

use App\Models\BulkRfq;
use App\Models\Product;
use App\Models\VendorProduct;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;

class RfqButton extends Component
{
    public $productId;
    public $vendorProductId;
    public $vendorId;
    
    public $showModal = false;
    
    // RFQ Form fields
    #[Validate('required|integer|min:1')]
    public $quantity = 1;
    
    #[Validate('nullable|numeric|min:0')]
    public $target_price;
    
    #[Validate('required|string|max:100')]
    public $shipping_country;
    
    #[Validate('nullable|string|max:100')]
    public $shipping_city;
    
    #[Validate('nullable|string|max:100')]
    public $shipping_port;
    
    public $needs_customization = false;
    
    #[Validate('nullable|string|max:1000')]
    public $customization_notes;
    
    public $currency = 'USD';
    
    public function mount($productId, $vendorProductId, $vendorId)
    {
        $this->productId = $productId;
        $this->vendorProductId = $vendorProductId;
        $this->vendorId = $vendorId;
        
        // Set default shipping country from user profile
        if (Auth::check()) {
            $this->shipping_country = Auth::user()->country ?? 'Guinea';
            $this->shipping_city = Auth::user()->city ?? '';
        }
    }
    
    public function openRfqModal()
    {
        if (!Auth::check()) {
            // Redirect to login with return URL
            session()->put('redirect_after_login', url()->current());
            return redirect()->route('customer_login')->with('success', 'Connectez-vous');
        }
        
        $this->showModal = true;
    }
    
    public function submitRfq()
    {
        $this->validate();
        
        if (!Auth::check()) {
            $this->addError('auth', 'Please login to submit RFQ');
            return;
        }
        
        try {
            $rfq = BulkRfq::create([
                'user_id' => Auth::id(),
                'vendor_id' => $this->vendorId,
                'product_id' => $this->productId,
                'vendor_product_id' => $this->vendorProductId,
                'quantity' => $this->quantity,
                'target_price' => $this->target_price,
                'currency' => $this->currency,
                'shipping_country' => $this->shipping_country,
                'shipping_city' => $this->shipping_city,
                'shipping_port' => $this->shipping_port,
                'needs_customization' => $this->needs_customization,
                'customization_notes' => $this->customization_notes,
                'status' => 'pending',
            ]);
            
            // Add initial message from buyer
            $rfq->messages()->create([
                'sender_type' => 'App\Models\User',
                'sender_id' => Auth::id(),
                'message' => 'RFQ submitted for ' . $this->quantity . ' units. ' . 
                           ($this->needs_customization ? 'Customization requested.' : ''),
            ]);
            
            // Send notification to vendor (you'll implement this)
            // Notification::send($rfq->vendor->user, new NewRfqNotification($rfq));
            
            $this->showModal = false;
            $this->reset(['quantity', 'target_price', 'customization_notes']);
            
            session()->flash('success', 'Your RFQ has been submitted successfully! The vendor will contact you soon.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit RFQ: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.rfq-button');
    }
}