<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Masuk · Lumbung Beras')]
class Login extends Component
{
    public $email = '';

    public $password = '';

    public $remember = false;

    public function login(): mixed
    {
        $credentials = $this->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'boolean']);
        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.');

            return null;
        }
        unset($credentials['remember']);
        if (Auth::attempt($credentials, (bool) $this->remember)) {
            if (Auth::id() !== (int) User::min('id')) {
                Auth::logout();
            } else {
                RateLimiter::clear($key);
                session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        }
        RateLimiter::hit($key, 60);
        $this->addError('email', 'Email atau password tidak sesuai.');

        return null;
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
