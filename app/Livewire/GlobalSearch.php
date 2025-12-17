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
