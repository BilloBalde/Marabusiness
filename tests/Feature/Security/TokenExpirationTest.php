<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * config/sanctum.php had 'expiration' => null: API tokens never expired. With the
 * mobile app keeping the token in unencrypted SharedPreferences on a build with
 * android:allowBackup="true", one token lifted from a device backup meant
 * permanent, unrevokable access to that account.
 *
 * All 8 tokens live when this was introduced were already stale (most recent use:
 * 113 days earlier), so no real session was cut by turning expiry on.
 */
class TokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-device')->plainTextToken;
    }

    #[Test]
    public function a_fresh_token_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer ' . $this->tokenFor($user))
            ->getJson('/api/v1/user/profile')
            ->assertOk();
    }

    #[Test]
    public function a_token_is_still_accepted_just_before_it_expires(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->travel(89)->days();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user/profile')
            ->assertOk();
    }

    #[Test]
    public function a_token_older_than_the_expiry_window_is_refused(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->travel(91)->days();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user/profile')
            ->assertUnauthorized();
    }

    #[Test]
    public function the_expiry_window_is_configured_at_all(): void
    {
        // The regression this guards is the config going back to null.
        $this->assertNotNull(config('sanctum.expiration'));
        $this->assertGreaterThan(0, config('sanctum.expiration'));
    }
}
