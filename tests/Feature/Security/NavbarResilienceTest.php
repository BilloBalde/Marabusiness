<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The shared navbar partial, included on every full page, had two unguarded
 * lookups that could take the entire site down — not just the page that happened
 * to expose them, since every route rendering the standard layout includes this
 * partial:
 *
 *  1. `$user->roles->first()->name` — null if a signed-in account holds no role
 *     at all. Every registration path assigns 'customer' immediately, so this is
 *     always an anomaly rather than a designed state, but it is a real one: the
 *     audit found exactly one such account in production. Reading ->name on that
 *     null crashed every page for that one user.
 *  2. `User::role('manager')->value('id')`, run unconditionally on every request
 *     regardless of login state. Spatie's role() scope throws if the 'manager'
 *     role row itself doesn't exist — not just if nobody holds it — which would
 *     take the whole site down for every visitor, logged in or not, the moment
 *     that one seeded row was ever missing (a fresh deploy before seeders run,
 *     a rolled-back migration).
 */
class NavbarResilienceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_signed_in_user_with_no_role_at_all_does_not_crash_the_page(): void
    {
        Role::findOrCreate('customer', 'web');
        Role::findOrCreate('manager', 'web');

        $user = User::factory()->create(); // deliberately: no assignRole() call
        $this->actingAs($user);

        $this->get('/')->assertOk();
    }

    #[Test]
    public function a_missing_manager_role_row_does_not_crash_the_page_for_a_guest(): void
    {
        // No roles seeded at all — the "fresh deploy, seeders haven't run yet"
        // scenario. This is the unconditional, logged-out case: the most severe
        // version of the bug, since it would affect every single visitor.
        $this->get('/')->assertOk();
    }

    #[Test]
    public function a_missing_manager_role_row_does_not_crash_the_page_for_a_normal_customer(): void
    {
        Role::findOrCreate('customer', 'web');
        // 'manager' deliberately left unseeded.

        $user = User::factory()->create();
        $user->assignRole('customer');
        $this->actingAs($user);

        $this->get('/')->assertOk();
    }

    #[Test]
    public function normal_role_based_navigation_is_unaffected(): void
    {
        Role::findOrCreate('customer', 'web');
        Role::findOrCreate('manager', 'web');

        $user = User::factory()->create();
        $user->assignRole('customer');
        $this->actingAs($user);

        $this->get('/')->assertOk();
    }
}
