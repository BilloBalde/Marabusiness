<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class GlobalSearch extends Component
{
    public $query = '';
    public $results = [];

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }

        $this->results = Product::where('name', 'LIKE', '%' . $this->query . '%')
            ->where('is_active', 1)
            // A catalogue product with no active vendor listing has nothing to link
            // to — the dropdown builds its href from vendorProducts[0], which 500s
            // the whole page hosting the search bar without this guard.
            ->whereHas('vendorProducts', fn ($q) => $q->where('is_active', 1))
            ->with(['vendorProducts' => fn ($q) => $q->where('is_active', 1)])
            ->limit(8)
            ->get(['id', 'name', 'slug', 'images']);
    }

    public function search()
    {
        return redirect()->to('/products?search=' . urlencode($this->query));
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
