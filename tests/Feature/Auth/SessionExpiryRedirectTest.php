<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionExpiryRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function expireSession(): void
    {
        $lifetimeSeconds = config('session.lifetime', 120) * 60;
        session(['last_activity' => now()->subSeconds($lifetimeSeconds + 60)->timestamp]);
    }

    #[Test]
    public function an_expired_session_redirects_back_to_login_with_the_page_the_visitor_was_on(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->expireSession();

        $response = $this->get('/products/produit-test/1');

        $response->assertRedirect(route('login', ['redirect' => url('/products/produit-test/1')]));
    }

    #[Test]
    public function the_login_page_remembers_the_redirect_target_from_the_query_string(): void
    {
        // This is the other half of the fix: the middleware now supplies
        // ?redirect=, and LoginPage::mount() must actually store it — this is
        // what save() later reads to send the visitor back where they were,
        // instead of always landing on the homepage after re-authenticating.
        $productUrl = url('/products/produit-test/1');

        $this->get('/login?redirect=' . urlencode($productUrl));

        $this->assertSame($productUrl, session('url.intended'));
    }
}
