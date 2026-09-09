<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Two account-takeover paths the audit found in SocialAuthController:
 *
 * 1. The account lookup matched by (provider, provider_id) OR by email, with no
 *    restriction on who that email could belong to — an admin or vendor account
 *    sharing the exact mailbox a Google account reports would be silently linked
 *    and logged into, through a button that is only ever shown on the public
 *    customer login page.
 * 2. Neither this callback nor the plain login/register forms ever rotated the
 *    session id on authentication, so a session an attacker planted on a shared
 *    browser beforehand was inherited by whoever signed in next.
 */
class SocialAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Every path here either matches an existing customer or creates one —
        // SocialAuthController::handleCallback() and RegisterPage::save() both
        // call assignRole('customer') on a brand new account.
        Role::findOrCreate('customer', 'web');
    }

    private function fakeGoogleUser(string $id, string $email, string $name = 'Buyer'): void
    {
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = $id;
        $socialiteUser->email = $email;
        $socialiteUser->name = $name;
        $socialiteUser->avatar = 'https://example.com/avatar.jpg';

        $provider = \Mockery::mock();
        $provider->shouldReceive('setHttpClient')->andReturnSelf();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    #[Test]
    public function a_google_login_cannot_reach_an_admin_account_sharing_that_email(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['email' => 'admin@marabusiness.test']);
        $admin->assignRole('admin');

        $this->fakeGoogleUser('google-attacker-id', 'admin@marabusiness.test');

        $response = $this->get('/auth/google/callback');

        // The email-matched admin account is never touched or logged into.
        $this->assertGuest();
        $admin->refresh();
        $this->assertNull($admin->provider);
        $this->assertNull($admin->provider_id);

        // The controller's own User::create() then collides with the admin's email
        // (unique constraint) and is caught by its try/catch — a safe denial, not a
        // new account silently created under someone else's address either.
        $response->assertRedirect('/login');
        $this->assertSame(1, User::where('email', 'admin@marabusiness.test')->count());
    }

    #[Test]
    public function a_google_login_links_an_existing_customer_account_by_email(): void
    {
        // The legitimate case this lookup exists for: someone registered with a
        // password first, and is using "Sign in with Google" for the first time.
        Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create(['email' => 'buyer@marabusiness.test', 'provider' => null]);
        $customer->assignRole('customer');

        $this->fakeGoogleUser('google-buyer-id', 'buyer@marabusiness.test', 'Buyer Real Name');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($customer->fresh());
        $customer->refresh();
        $this->assertSame('google', $customer->provider);
        $this->assertSame('google-buyer-id', $customer->provider_id);
        $this->assertSame('Buyer Real Name', $customer->name);

        // No duplicate account was created for the same email.
        $this->assertSame(1, User::where('email', 'buyer@marabusiness.test')->count());
    }

    #[Test]
    public function a_returning_google_user_is_matched_by_provider_id_not_recreated(): void
    {
        Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create([
            'email' => 'returning@marabusiness.test',
            'provider' => 'google',
            'provider_id' => 'google-returning-id',
        ]);
        $customer->assignRole('customer');

        $this->fakeGoogleUser('google-returning-id', 'returning@marabusiness.test');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($customer->fresh());
        $this->assertSame(1, User::count());
    }

    #[Test]
    public function a_brand_new_google_user_gets_a_customer_account(): void
    {
        $this->fakeGoogleUser('google-new-id', 'new@marabusiness.test', 'New Person');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user = User::where('email', 'new@marabusiness.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_session_id_is_rotated_on_a_google_login(): void
    {
        $this->fakeGoogleUser('google-rotate-id', 'rotate@marabusiness.test');

        // Establish a session before authenticating, the way a fixation attack would.
        $this->get('/');
        $before = session()->getId();

        $this->get('/auth/google/callback');

        $this->assertNotSame($before, session()->getId());
    }

    #[Test]
    public function the_session_id_is_rotated_on_a_normal_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $this->get('/'); // establish a session first
        $before = session()->getId();

        \Livewire\Livewire::test(\App\Livewire\Auth\LoginPage::class)
            ->set('email', $user->email)
            ->set('password', 'correct-password')
            ->call('save');

        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_session_id_is_rotated_on_registration(): void
    {
        $this->get('/');
        $before = session()->getId();

        \Livewire\Livewire::test(\App\Livewire\Auth\RegisterPage::class)
            ->set('name', 'New Customer')
            ->set('email', 'brand-new@marabusiness.test')
            ->set('password', 'a-strong-password')
            ->set('password_confirmation', 'a-strong-password')
            ->call('save');

        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticated();
    }
}
