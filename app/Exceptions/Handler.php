<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Filament\Facades\Filament;
use ErrorException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (ErrorException $e, $request) {
            $panels = ['admin', 'vendor'];
        
            foreach ($panels as $panel) {
                if (str_contains($request->path(), $panel) && 
                    str_contains($e->getMessage(), 'Attempt to read property "roles" on null')) {
                    
                    // Get the specific panel
                    $panelInstance = Filament::getPanel($panel);
                    
                    // Logout from this panel
                    $panelInstance->auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    // Redirect to correct login page
                    return redirect()->route("filament.{$panel}.auth.login")
                        ->with('error', 'Your session has expired. Please login again.');
                }
            }
        });

        // Log additional context for specific errors
        $this->reportable(function (Throwable $e) {
            // Log CSRF token mismatch with context
            if ($e instanceof TokenMismatchException) {
                Log::warning('CSRF Token Mismatch', [
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'method' => request()->method(),
                    'session_last_activity' => session('last_activity'),
                ]);
            }

            // Log 403 forbidden attempts
            if ($e instanceof HttpException && $e->getStatusCode() === 403) {
                Log::warning('403 Forbidden Access Attempt', [
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'role' => auth()->check() ? auth()->user()->roles->first()->name ?? 'none' : 'guest',
                ]);
            }

            // Log 500 server errors with more context
            if ($e instanceof HttpException && $e->getStatusCode() === 500) {
                Log::error('500 Server Error', [
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        });
    }

    public function render($request, Throwable $exception)
    {
        // Handle 419 CSRF Token Mismatch
        if ($exception instanceof TokenMismatchException) {
            return $this->renderTokenMismatchException($request, $exception);
        }

        // Handle 403 Forbidden
        if ($exception instanceof HttpException && $exception->getStatusCode() === 403) {
            return $this->renderForbiddenException($request, $exception);
        }

        // Handle 500 Server Error
        if ($exception instanceof HttpException && $exception->getStatusCode() === 500) {
            return $this->renderServerErrorException($request, $exception);
        }

        // Handle 503 Service Unavailable
        if ($exception instanceof HttpException && $exception->getStatusCode() === 503) {
            if (view()->exists('errors.503')) {
                $referenceId = substr(md5(uniqid()), 0, 8);
                return response()->view('errors.503', [
                    'referenceId' => $referenceId,
                    'isPanel' => $this->isPanelRequest($request),
                ], 503);
            }
        }

        // Handle other HTTP exceptions
        if ($exception instanceof HttpException) {
            return $this->renderHttpException($request, $exception);
        }

        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            return $this->renderAuthenticationException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Render 419 CSRF Token Mismatch Exception
     */
    protected function renderTokenMismatchException($request, TokenMismatchException $exception)
    {
        // Determine the appropriate login route based on current panel
        $loginRoute = $this->getLoginRoute($request);
        
        // Generate reference ID
        $referenceId = substr(md5(uniqid()), 0, 8);

        // Clear session
        if (auth()->check()) {
            auth()->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Handle Livewire requests
        if ($request->header('X-Livewire')) {
            return response('', 419)
                ->header('Livewire-Redirect', $loginRoute);
        }

        // Handle AJAX/JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your session has expired.',
                'redirect' => $loginRoute,
                'reference_id' => $referenceId,
            ], 419);
        }

        // Show custom error page
        if (view()->exists('errors.419')) {
            return response()->view('errors.419', [
                'loginRoute' => $loginRoute,
                'referenceId' => $referenceId,
            ], 419);
        }

        return redirect($loginRoute)
            ->with('error', 'Your session has expired. Please login again.');
    }

    /**
     * Render 403 Forbidden Exception
     */
    protected function renderForbiddenException($request, HttpException $exception)
    {
        $referenceId = substr(md5(uniqid()), 0, 8);
        $isPanel = $this->isPanelRequest($request);
        $panelName = $this->getPanelName($request);

        // AJAX/JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.',
                'status' => 403,
                'reference_id' => $referenceId,
                'user_role' => auth()->check() ? auth()->user()->roles->first()->name ?? 'user' : 'guest',
            ], 403);
        }

        // Show custom error page if it exists
        if (view()->exists('errors.403')) {
            return response()->view('errors.403', [
                'referenceId' => $referenceId,
                'isPanel' => $isPanel,
                'panelName' => $panelName,
                'userRole' => auth()->check() ? auth()->user()->roles->first()->name ?? 'User' : 'Guest',
                'supportEmail' => config('app.support_email'),
            ], 403);
        }

        // Fallback - redirect based on context
        if ($isPanel) {
            $panel = $this->getPanelName($request);
            return redirect()->route("filament.{$panel}.pages.dashboard")
                ->with('error', 'You do not have permission to access that page.')
                ->with('reference_id', $referenceId);
        }

        return redirect()->route('home')
            ->with('error', 'You do not have permission to access that page.')
            ->with('reference_id', $referenceId);
    }

    /**
     * Render 500 Server Error Exception
     */
    protected function renderServerErrorException($request, HttpException $exception)
    {
        $referenceId = substr(md5(uniqid()), 0, 8);
        
        // Log the full exception for debugging
        Log::error('500 Server Error Details', [
            'reference_id' => $referenceId,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
        ]);

        // AJAX/JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'An unexpected server error occurred. Please try again later.',
                'status' => 500,
                'reference_id' => $referenceId,
                'support_contact' => config('app.support_email'),
            ], 500);
        }

        // Show custom error page if it exists
        if (view()->exists('errors.500')) {
            return response()->view('errors.500', [
                'referenceId' => $referenceId,
                'supportEmail' => config('app.support_email'),
                'isPanel' => $this->isPanelRequest($request),
                'exception' => config('app.debug') ? $exception->getMessage() : null,
            ], 500);
        }

        // Fallback to generic error
        $message = config('app.debug') 
            ? $exception->getMessage() 
            : 'An unexpected error occurred. Please try again later.';

        return response()->view('errors.generic', [
            'message' => $message,
            'referenceId' => $referenceId,
        ], 500);
    }

    /**
     * Render generic HTTP exceptions
     */
    protected function renderHttpException($request, HttpException $exception)
    {
        $status = $exception->getStatusCode();
        
        // Check if a custom view exists for this status code
        if (view()->exists("errors.{$status}")) {
            return response()->view("errors.{$status}", [
                'status' => $status,
                'message' => $exception->getMessage(),
                'referenceId' => substr(md5(uniqid()), 0, 8),
            ], $status);
        }

        return parent::renderHttpException($request, $exception);
    }

    /**
     * Render authentication exceptions
     */
    protected function renderAuthenticationException($request, AuthenticationException $exception)
    {
        $loginRoute = $this->getLoginRoute($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login.',
                'redirect' => $loginRoute,
            ], 401);
        }

        return redirect()->guest($loginRoute)
            ->with('error', 'Please login to access this page.');
    }

    /**
     * Determine the appropriate login route based on current request
     */
    protected function getLoginRoute($request): string
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('filament.admin.auth.login');
        }

        if ($request->is('vendor') || $request->is('vendor/*')) {
            return route('filament.vendor.auth.login');
        }

        return route('login');
    }

    /**
     * Check if request is for a Filament panel
     */
    protected function isPanelRequest($request): bool
    {
        return $request->is('admin/*') || $request->is('vendor/*');
    }

    /**
     * Get the panel name from request
     */
    protected function getPanelName($request): ?string
    {
        if ($request->is('admin/*')) {
            return 'admin';
        }

        if ($request->is('vendor/*')) {
            return 'vendor';
        }

        return null;
    }
}