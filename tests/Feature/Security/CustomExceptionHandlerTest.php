<?php

namespace Tests\Feature\Security;

use App\Exceptions\Handler;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * app/Exceptions/Handler.php — ~250 lines of custom 419/403/500/503 pages and
 * CSRF/403/500 logging — was never actually wired up. Laravel 11 no longer
 * auto-binds a class of this name the way Laravel 10 did; bootstrap/app.php's
 * withExceptions() binds the framework's own base Handler instead, and nothing
 * ever told the container to use this subclass. Wiring it up (AppServiceProvider)
 * surfaced a second, independent problem: renderHttpException() overrode the
 * parent method with an incompatible signature (two parameters and a narrower
 * type than the one the base class actually declares), which is a fatal
 * "Declaration ... must be compatible with ..." error at class-load time. This
 * class could not have been loaded successfully by anything, ever, regardless of
 * whether it was bound as the handler — fixed by matching the base signature
 * exactly.
 *
 * A third problem surfaced once the class actually loaded: register()'s
 * reportable() closure, meant to log CSRF/403/500 attempts, could never run.
 * Laravel's report() checks shouldReport() before ever consulting a reportable()
 * callback, and TokenMismatchException plus every HttpException (which is how
 * abort(403)/abort(500) actually throw) are unconditionally in the framework's
 * own $internalDontReport list — no condition inside that closure could ever be
 * reached. Moved into the render*() methods instead, the same way 500's logging
 * always worked (it was already inline there).
 */
class CustomExceptionHandlerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_container_resolves_our_subclass_not_the_framework_default(): void
    {
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        $this->assertInstanceOf(Handler::class, $handler);
    }

    #[Test]
    public function a_403_renders_the_custom_page_and_is_logged(): void
    {
        Log::spy();

        Route::middleware('web')->get('/__test_403', fn () => abort(403));

        $response = $this->get('/__test_403');

        $response->assertStatus(403);
        Log::shouldHaveReceived('warning')
            ->with('403 Forbidden Access Attempt', \Mockery::any())
            ->once();
    }

    #[Test]
    public function a_500_renders_the_custom_page_and_is_logged(): void
    {
        Log::spy();

        Route::middleware('web')->get('/__test_500', fn () => abort(500));

        $response = $this->get('/__test_500');

        $response->assertStatus(500);
        Log::shouldHaveReceived('error')
            ->with('500 Server Error Details', \Mockery::any())
            ->once();
    }

    #[Test]
    public function a_419_renders_the_custom_page_and_is_logged(): void
    {
        // Laravel's own VerifyCsrfToken middleware skips itself entirely while
        // running unit tests (Handler::runningUnitTests()), so a real
        // TokenMismatchException cannot be provoked end-to-end through the HTTP
        // kernel in this suite — invoked directly instead, exactly as the
        // framework calls it from render().
        Log::spy();

        $handler = new Handler(app());
        $method = new ReflectionMethod($handler, 'renderTokenMismatchException');
        $method->setAccessible(true);

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session')->driver());

        $response = $method->invoke($handler, $request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(419, $response->getStatusCode());
        Log::shouldHaveReceived('warning')->with('CSRF Token Mismatch', \Mockery::any())->once();
    }

    #[Test]
    public function a_stale_role_crash_inside_a_panel_logs_the_user_out_and_redirects_to_that_panels_login(): void
    {
        // Reproduces the scenario register()'s renderable() closure exists for:
        // a Filament page reading a role that no longer resolves. Rather than a
        // generic 500, the user is logged out and sent back to sign in.
        $user = User::factory()->create();
        $this->actingAs($user);

        Route::middleware('web')->get('/admin/__test_stale_role', function () {
            throw new \ErrorException('Attempt to read property "roles" on null');
        });

        $response = $this->get('/admin/__test_stale_role');

        $response->assertRedirect('http://localhost:8000/admin/login');
        $this->assertGuest();
    }

    #[Test]
    public function an_unauthenticated_web_request_redirects_to_login(): void
    {
        Route::middleware(['web', 'auth'])->get('/__test_auth_web', fn () => 'secret');

        $this->get('/__test_auth_web')->assertRedirect(route('login'));
    }

    #[Test]
    public function an_unauthenticated_api_request_gets_a_clean_json_401(): void
    {
        Route::middleware(['api', 'auth:sanctum'])->get('/__test_auth_api', fn () => 'secret');

        $response = $this->getJson('/__test_auth_api');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message', 'redirect']);
    }

    #[Test]
    public function a_503_renders_the_custom_maintenance_page(): void
    {
        Route::middleware('web')->get('/__test_503', fn () => abort(503));

        $this->get('/__test_503')->assertStatus(503);
    }

    #[Test]
    public function a_plain_404_is_unaffected(): void
    {
        // No errors.404 view exists in this project — confirms the custom
        // renderHttpException() correctly falls through to Laravel's own default
        // 404 page rather than crashing on a missing view.
        $this->get('/__this_route_does_not_exist_anywhere')->assertStatus(404);
    }
}
