<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

class CategoriesPage extends Component
{
    #[Title('Categories Page - MARA BUSINESS')]
    public function render()
    {
        $categories = \App\Models\Category::where('is_active', 1)->get();
        return view('livewire.categories-page', [
            'categories' => $categories,
        ]);
    }
}
