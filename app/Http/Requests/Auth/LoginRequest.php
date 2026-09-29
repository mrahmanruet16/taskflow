<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Rate limiting lives here (not in the controller) because it is part of
     * *this request's* validation concern — "is this login attempt allowed
     * to proceed at all" — before we even touch business logic.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Email (not password — never the password) is worth logging
            // here specifically: a pattern of failed attempts against the
            // same email, or against many different emails from the same
            // IP, is exactly what security monitoring looks for. This is
            // the one log call in this app that deliberately includes a
            // value close to PII — justified because it's the log's whole
            // purpose (detecting credential-stuffing/brute-force patterns),
            // not incidental.
            Log::warning('Failed login attempt', [
                'email' => $this->string('email')->toString(),
                'ip' => $this->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * Without this, an attacker can brute-force passwords by submitting
     * the login form thousands of times per second — Eloquent/bcrypt alone
     * do not protect against this; bcrypt only slows down each guess, it
     * does not limit the *number* of guesses over HTTP.
     *
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key, scoped per email+IP so one user's
     * lockout can't be used to deny service to a different user sharing an IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
