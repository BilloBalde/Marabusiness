<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use Livewire\Attributes\Title;
use Livewire\Component;

class HomePage extends Component
{
    #[Title('Home Page - MMB FOGO')]
    public function render()
    {
        $title = 'Acheter les télephones et accéssoires de meilleurs qualité chez ';
        $description = 'Trouvez tout le nécessaire pour vos projets de construction en un seul endroit. Nous proposons une large gamme de matériaux de construction et d’équipements professionnels : ciment, sable, gravier, fer à béton, briques, peinture, outils électriques, équipements de protection, et bien plus encore. Que vous soyez un particulier ou un professionnel du bâtiment, nous avons les produits qu’il vous faut pour mener à bien vos travaux, de la fondation à la finition.';
        // Get all active brands
        $brands = Brand::where('is_active', 1)->get();
        // describe all brand
        $descriptionBrands = ' Découvrez nos marques partenaires, reconnues pour leur qualité et leur fiabilité. Que vous ayez besoin de matériaux de construction, d’outils ou d’équipements, nous avons tout ce qu’il vous faut pour réaliser vos projets en toute sérénité.';

        // Get all active categories
        $categories = Category::where('is_active', 1)->get();
        // describe all categories
        $descriptionCategories = 'Our Categories';
        // Render the view with the data
        // Add services section
        $services = \App\Models\Service::all();
        $featuredProducts = \App\Models\Product::where('is_featured', 1)
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('livewire.home-page', [
            'brands' => $brands,
            'title' => $title,
            'description' => $description,
            'descriptionBrands' => $descriptionBrands,
            'descriptionCategories' => $descriptionCategories,
            'categories' => $categories,
            'services' => $services,
            'featuredProducts' => $featuredProducts
        ]);
    }
}
