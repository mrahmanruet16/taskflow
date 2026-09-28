<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            // Hashing happens automatically here via the User model's
            // `casts()` -> 'password' => 'hashed' cast (Laravel 13). Without
            // that cast, this line would store the raw plaintext password —
            // see docs/backend-concepts/authentication-and-sessions.md for
            // what that failure looks like.
            'password' => $validated['password'],
        ]);

        event(new Registered($user));

        // Log the user in immediately after registration — a session is
        // established the same way login does it (Auth::login + session
        // regeneration), so registration and login share one auth mechanism
        // rather than two.
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
