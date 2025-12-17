<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Filament\Facades\Filament;
use ErrorException;
use Illuminate\Session\TokenMismatchException;

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
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof HttpException && $exception->getStatusCode() === 403) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return response()->view('errors.custom-403', [], 403);
            }
        }

        if ($exception instanceof TokenMismatchException) {
            // Session expired, send user back to the appropriate login screen
            $loginRoute = $request->is('admin') || $request->is('admin/*')
                ? route('filament.admin.auth.login')
                : ($request->is('vendor') || $request->is('vendor/*')
                    ? route('filament.vendor.auth.login')
                    : route('login'));

            // Livewire requests expect JSON; instruct Livewire to redirect instead of showing 419 prompt
            if ($request->header('X-Livewire')) {
                return response('', 419)->header('Livewire-Redirect', $loginRoute);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please sign in again.',
                    'redirect' => $loginRoute,
                ], 419);
            }

            return redirect()->guest($loginRoute)->withErrors([
                'session' => 'Your session expired. Please sign in again.',
            ]);
        }

        return parent::render($request, $exception);
    }
}
