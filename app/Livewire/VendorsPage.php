<?php

namespace App\Livewire;

use App\Models\Vendor;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class VendorsPage extends Component
{
    use WithPagination;

    public $search = '';

    public function updating($field)
    {
        $this->resetPage();
    }

    public function searchNow()
    {
        $this->resetPage();
    }

    public function render()
    {
        $vendors = Vendor::query()
            ->withCount(['followers', 'approvedVendorReviews'])
            ->when($this->search !== '', function ($q) {
                $q->where('store_name', 'like', '%' . $this->search . '%');
            })
            ->where('is_active', true)
            ->paginate(16);

        return view('livewire.vendors-page', [
            'vendors' => $vendors
        ]);
    }
}