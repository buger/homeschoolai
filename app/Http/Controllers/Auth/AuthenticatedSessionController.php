<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Get the intended URL and check if it's a valid user destination
        $intendedUrl = session()->pull('url.intended');

        // If intended URL is an API/JSON endpoint or asset, redirect to dashboard instead
        if ($intendedUrl && (
            str_contains($intendedUrl, '.json') ||
            str_contains($intendedUrl, '/api/') ||
            str_contains($intendedUrl, '/lang/') ||
            str_ends_with($intendedUrl, '.js') ||
            str_ends_with($intendedUrl, '.css')
        )) {
            return redirect()->route('dashboard', absolute: false);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
