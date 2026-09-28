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
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Credential checking, rate limiting, and calling Auth::attempt()
        // live inside the Form Request (see LoginRequest::authenticate).
        // That keeps this controller readable as "orchestration only": the
        // controller doesn't know *how* credentials are checked, only that
        // they were.
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Invalidating the session (not just logging out the guard) removes
        // the session record from the `sessions` table entirely, and
        // regenerating the CSRF token prevents a stale token from a
        // previous session being replayed after logout.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
