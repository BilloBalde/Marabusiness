<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // Left uncommented since day one — RefreshDatabase disabled and never re-enabled.
    // The in-memory sqlite test connection starts with no tables at all, and the
    // real homepage has always queried real ones (currencies, categories, ...), so
    // this never actually could have passed.
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
