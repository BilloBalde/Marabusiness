<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ResetPasswordPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The reset page's last line was `$status = Password::PASSWORD_RESET ? redirect(...) : flash(...)`:
 * an assignment, not a comparison, so it was always true. An expired or already-used link
 * sent the person to the login page as though their password had changed, and the error
 * message could never appear. The minimum length also lagged behind registration.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_valid_link_changes_the_password_and_goes_to_login(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Livewire::test(ResetPasswordPage::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'nouveau-mot-de-passe')
            ->set('password_confirmation', 'nouveau-mot-de-passe')
            ->call('save')
            ->assertRedirect('/login');

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
    }

    #[Test]
    public function an_invalid_link_is_reported_and_changes_nothing(): void
    {
        $user = User::factory()->create();
        $before = $user->password;

        Livewire::test(ResetPasswordPage::class, ['token' => 'jeton-perime'])
            ->set('email', $user->email)
            ->set('password', 'nouveau-mot-de-passe')
            ->set('password_confirmation', 'nouveau-mot-de-passe')
            ->call('save')
            ->assertNoRedirect()
            ->assertSee('invalide ou a expiré');

        $this->assertSame($before, $user->fresh()->password);
    }

    #[Test]
    public function a_password_shorter_than_registration_allows_is_refused(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Livewire::test(ResetPasswordPage::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'court7!')
            ->set('password_confirmation', 'court7!')
            ->call('save')
            ->assertHasErrors(['password' => 'min']);
    }
}
