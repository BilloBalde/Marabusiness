<?php

namespace Tests\Feature\Blog;

use App\Filament\Resources\BlogCategoryResource\Pages\CreateBlogCategory;
use App\Filament\Resources\BlogCategoryResource\Pages\ListBlogCategories;
use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\ListBlogPosts;
use App\Livewire\BlogPage;
use App\Livewire\BlogPostPage;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function actingAsAdminPanel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    #[Test]
    public function the_blog_categories_list_page_renders(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(ListBlogCategories::class)->assertSuccessful();
    }

    #[Test]
    public function the_blog_posts_list_page_renders(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(ListBlogPosts::class)->assertSuccessful();
    }

    #[Test]
    public function a_blog_post_can_be_created_and_the_author_is_set_automatically(): void
    {
        $admin = $this->admin();
        $this->actingAsAdminPanel($admin);

        $category = BlogCategory::create(['slug' => 'conseils', 'name' => ['fr' => 'Conseils']]);

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'slug' => 'mon-article',
                'title.fr' => 'Mon article',
                'content.fr' => '<p>Contenu.</p>',
                'category_id' => $category->id,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::where('slug', 'mon-article')->first();

        $this->assertNotNull($post);
        $this->assertSame($admin->id, $post->author_id);
        $this->assertSame($category->id, $post->category_id);
    }

    #[Test]
    public function the_blog_listing_shows_only_published_posts(): void
    {
        BlogPost::create([
            'slug' => 'publie',
            'title' => ['fr' => 'Article publié'],
            'content' => ['fr' => '...'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        BlogPost::create([
            'slug' => 'brouillon',
            'title' => ['fr' => 'Article brouillon'],
            'content' => ['fr' => '...'],
            'is_published' => false,
        ]);
        BlogPost::create([
            'slug' => 'programme',
            'title' => ['fr' => 'Article programmé'],
            'content' => ['fr' => '...'],
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        Livewire::test(BlogPage::class)
            ->assertSee('Article publié')
            ->assertDontSee('Article brouillon')
            ->assertDontSee('Article programmé');
    }

    #[Test]
    public function visiting_a_published_post_shows_its_content_and_counts_a_view(): void
    {
        $post = BlogPost::create([
            'slug' => 'mon-article',
            'title' => ['fr' => 'Mon article'],
            'content' => ['fr' => '<p>Contenu détaillé.</p>'],
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        Livewire::test(BlogPostPage::class, ['post' => 'mon-article'])
            ->assertSee('Mon article')
            ->assertSee('Contenu détaillé.');

        $this->assertSame(1, $post->fresh()->views);
    }

    #[Test]
    public function an_unpublished_post_is_not_reachable(): void
    {
        BlogPost::create([
            'slug' => 'brouillon',
            'title' => ['fr' => 'Brouillon'],
            'content' => ['fr' => '...'],
            'is_published' => false,
        ]);

        $this->get('/blog/brouillon')->assertNotFound();
    }
}
