<?php

namespace Tests\Feature\Security;

use App\Livewire\BlogPostPage;
use App\Livewire\SourcingDetailPage;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Sourcing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Product and service descriptions already went through HtmlSanitizer before being
 * printed as raw HTML. The blog and the sourcing pages printed their rich text with
 * {!! !!} directly: a script pasted into an article by anyone with access to the
 * admin form ran in every visitor's browser.
 */
class StoredHtmlIsSanitizedTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<p>Texte visible</p><script>alert("xss")</script><img src="x" onerror="alert(1)">';

    #[Test]
    public function a_blog_post_keeps_its_text_and_loses_its_script(): void
    {
        $category = BlogCategory::create(['slug' => 'conseils', 'name' => ['fr' => 'Conseils']]);
        $post = BlogPost::create([
            'slug' => 'mon-article',
            'title' => ['fr' => 'Mon article'],
            'content' => ['fr' => self::PAYLOAD],
            'category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $html = Livewire::test(BlogPostPage::class, ['post' => $post->slug])->html();

        $this->assertStringContainsString('Texte visible', $html);
        $this->assertStringNotContainsString('<script>alert("xss")', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    #[Test]
    public function a_sourcing_description_keeps_its_text_and_loses_its_script(): void
    {
        $sourcing = Sourcing::create([
            'slug' => 'communication',
            'title' => ['fr' => 'Support de communication'],
            'short_description' => ['fr' => 'Courte.'],
            'description' => ['fr' => self::PAYLOAD],
            'is_active' => true,
        ]);

        $html = Livewire::test(SourcingDetailPage::class, ['sourcing' => $sourcing->slug])->html();

        $this->assertStringContainsString('Texte visible', $html);
        $this->assertStringNotContainsString('<script>alert("xss")', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }
}
