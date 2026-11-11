<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Livewire\Component;

class AboutUsPage extends Component
{
    public function render()
    {
        // Real counts. This page used to print "500+ clients satisfaits", "5 pays
        // couverts" and "10k+ produits livrés", none of which was measured anywhere.
        return view('livewire.about-us-page', [
            'vendorsCount' => Vendor::where('is_active', true)->count(),
            'productsCount' => VendorProduct::sellable()->distinct()->count('product_id'),
            'deliveredCount' => Order::where('status', 'delivered')->count(),
        ]);
    }
}
