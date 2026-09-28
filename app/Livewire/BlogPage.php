<?php

namespace App\Livewire;

use App\Models\BlogPost;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Blog - MARA BUSINESS')]
class BlogPage extends Component
{
    public function render()
    {
        $posts = BlogPost::where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->with('category')
            ->latest('published_at')
            ->get();

        return view('livewire.blog-page', [
            'posts' => $posts,
        ]);
    }
}
