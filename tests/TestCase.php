<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Spatie's role/permission lookups are cached in the container for the life of
     * the process, not per test. RefreshDatabase truncates the tables between tests
     * but never clears this cache, so a role created in one test can leave a stale
     * (or simply absent) entry that the next test's role() query or assignRole()
     * silently misses — a real failure that looks exactly like a logic bug in
     * whatever is under test. Clearing it before each test is what Spatie's own
     * docs recommend when using RefreshDatabase.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
