<?php

namespace Tests\Feature\Security;

use App\Livewire\Auth\LoginPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Neither the customer login form nor the mobile API login stopped repeated wrong
 * passwords — a named 'login' rate limiter existed in RateLimiterServiceProvider but
 * was never attached to anything. The admin and vendor panels were already safe
 * (Filament's own Login page throttles at 5/60s by IP via danharrin/livewire-rate-
 * limiting); these two public entry points were not.
 *
 * Both are now keyed by email+IP, not IP alone: a guessing spree against one account
 * trips the limit without locking out a different customer behind the same shared
 * connection.
 */
class LoginThrottlingTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'buyer@marabusiness.test'): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => bcrypt('correct-password'),
        ]);
    }

    // --- Web (LoginPage) -----------------------------------------------------

    #[Test]
    public function five_wrong_passwords_are_allowed_the_sixth_is_blocked_even_if_correct(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(LoginPage::class)
                ->set('email', $user->email)
                ->set('password', 'wrong')
                ->call('save')
                ->assertSet('email', $user->email);

            $this->assertGuest();
        }

        // The 6th call uses the CORRECT password — proving the block happens
        // before Auth::attempt() is ever reached again, not because the password
        // kept being wrong.
        Livewire::test(LoginPage::class)
            ->set('email', $user->email)
            ->set('password', 'correct-password')
            ->call('save');

        $this->assertGuest();
    }

    #[Test]
    public function a_correct_password_under_the_limit_still_logs_in(): void
    {
        $user = $this->user();

        Livewire::test(LoginPage::class)->set('email', $user->email)->set('password', 'wrong')->call('save');
        Livewire::test(LoginPage::class)->set('email', $user->email)->set('password', 'wrong')->call('save');

        Livewire::test(LoginPage::class)
            ->set('email', $user->email)
            ->set('password', 'correct-password')
            ->call('save');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_counter_is_scoped_per_email_not_shared_across_accounts(): void
    {
        $victim = $this->user('victim@marabusiness.test');
        $bystander = $this->user('bystander@marabusiness.test');

        // Exhaust the limit against the victim's email only.
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(LoginPage::class)
                ->set('email', $victim->email)
                ->set('password', 'wrong')
                ->call('save');
        }

        // A different customer, same test IP, logging into their own account is
        // unaffected by the victim's exhausted attempts.
        Livewire::test(LoginPage::class)
            ->set('email', $bystander->email)
            ->set('password', 'correct-password')
            ->call('save');

        $this->assertAuthenticatedAs($bystander);
    }

    #[Test]
    public function a_successful_login_clears_the_counter(): void
    {
        $user = $this->user();

        Livewire::test(LoginPage::class)->set('email', $user->email)->set('password', 'wrong')->call('save');
        Livewire::test(LoginPage::class)->set('email', $user->email)->set('password', 'wrong')->call('save');
        Livewire::test(LoginPage::class)->set('email', $user->email)->set('password', 'wrong')->call('save');

        Livewire::test(LoginPage::class)
            ->set('email', $user->email)
            ->set('password', 'correct-password')
            ->call('save');
        $this->assertAuthenticatedAs($user);

        auth()->logout();

        // If the earlier count (3 wrong) had NOT been cleared, 4 more wrong
        // attempts would already trip the 5-attempt limit on the 5th call below,
        // and it would be blocked regardless of the password being correct. It
        // only succeeds if the reset actually happened.
        for ($i = 0; $i < 4; $i++) {
            Livewire::test(LoginPage::class)
                ->set('email', $user->email)
                ->set('password', 'wrong')
                ->call('save');
        }

        Livewire::test(LoginPage::class)
            ->set('email', $user->email)
            ->set('password', 'correct-password')
            ->call('save');

        $this->assertAuthenticatedAs($user);
    }

    // --- API (AuthController::login) ------------------------------------------

    #[Test]
    public function the_api_login_allows_five_wrong_attempts_then_blocks_the_sixth_with_429(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        // 6th attempt, correct password this time — still blocked.
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['message', 'retry_after']);
    }

    #[Test]
    public function the_api_login_counter_is_scoped_per_email(): void
    {
        $victim = $this->user('api-victim@marabusiness.test');
        $bystander = $this->user('api-bystander@marabusiness.test');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $victim->email,
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $bystander->email,
            'password' => 'correct-password',
        ])->assertStatus(200);
    }

    #[Test]
    public function a_successful_api_login_under_the_limit_returns_a_token_and_clears_the_counter(): void
    {
        $user = $this->user();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['user', 'token', 'token_type']);

        // The counter was cleared: a fresh batch of 5 wrong guesses still gets
        // ordinary validation errors, not an immediate 429.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ])->assertStatus(422);
        }
    }
}
