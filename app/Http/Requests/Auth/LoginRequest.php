<?php

namespace App\Http\Requests\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:4'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->input('email'));
        $password = $this->input('password');

        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'username';

        // 1. Try matching with primary field (email or username)
        $attempted = Auth::attempt([$field => $login, 'password' => $password], $this->boolean('remember'));

        // 2. Fallback: if username was tried, also attempt opposite or email prefix
        if (!$attempted && !$isEmail) {
            $userByEmailPrefix = User::where('email', 'like', "{$login}@%")->first();
            if ($userByEmailPrefix) {
                $attempted = Auth::attempt(['email' => $userByEmailPrefix->email, 'password' => $password], $this->boolean('remember'));
            }
        } elseif (!$attempted && $isEmail) {
            // maybe email matches username
            $attempted = Auth::attempt(['username' => $login, 'password' => $password], $this->boolean('remember'));
        }

        if (! $attempted) {
            RateLimiter::hit($this->throttleKey());

            // If attempting central login with an email or username belonging to a tenant shop
            if (!tenancy()->initialized) {
                $tenant = Tenant::where('email', $login)->orWhere('id', $login)->first();
                if ($tenant) {
                    $domain = $tenant->domains->firstWhere('domain', 'like', '%.localhost')?->domain 
                        ?? $tenant->domains->first()?->domain 
                        ?? ($tenant->id . '.localhost');
                    
                    if (str_ends_with($domain, '.127.0.0.1')) {
                        $domain = str_replace('.127.0.0.1', '.localhost', $domain);
                    }

                    $port = request()->getPort();
                    $portSuffix = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';
                    $tenantUrl = request()->getScheme() . "://{$domain}{$portSuffix}/login";

                    throw ValidationException::withMessages([
                        'email' => "আপনার অ্যাকাউন্টটি '{$tenant->name}' শপের সাথে যুক্ত। আপনার শপের লগইন লিঙ্ক: {$tenantUrl}",
                    ]);
                }
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
