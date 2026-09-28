<?php

namespace App\Livewire;

use App\Models\BlogPost;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Article - MARA BUSINESS')]
class BlogPostPage extends Component
{
    public $post;

    public function mount(string $post): void
    {
        $this->post = BlogPost::where('slug', $post)
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->with('category', 'author')
            ->firstOrFail();

        $this->post->registerView();
    }

    public function render()
    {
        return view('livewire.blog-post-page');
    }
}
