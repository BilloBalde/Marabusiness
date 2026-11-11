<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * GET /logout used to be a separate copy of the POST route that had lost its
 * Auth::logout() call along the way: it redirected to the homepage — exactly what a
 * successful logout looks like — while leaving the session fully authenticated.
 * Anyone who opened "Déconnexion" in a new tab (a plain <a href> reaches this route
 * directly; only a real click, JS enabled, submits the POST form instead), or who
 * had bookmarked or been sent the URL, was left signed in without knowing it — a
 * real risk on a shared computer.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_logout_actually_signs_the_user_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    #[Test]
    public function post_logout_still_signs_the_user_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    #[Test]
    public function after_get_logout_a_protected_page_asks_to_sign_in_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/logout');

        $this->get('/orders')->assertRedirect(route('login'));
    }

    #[Test]
    public function the_session_is_invalidated_not_merely_the_guard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $sessionIdBefore = session()->getId();

        $this->get('/logout');

        // A fresh session id proves session()->invalidate() actually ran, rather than
        // Auth::logout() alone leaving the same session (and its data) in place.
        $this->assertNotSame($sessionIdBefore, session()->getId());
    }
}
