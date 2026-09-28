<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    public $name = '';

    public $email = '';

    public $store_name = '';

    public $current_password = '';

    public $new_password = '';

    public $new_password_confirmation = '';

    public function mount(): void
    {
        $this->fill(Auth::user()->only('name', 'email', 'store_name'));
    }

    public function updateStore(): void
    {
        $this->validate(['store_name' => 'required|string|max:150']);
        DB::transaction(fn () => Auth::user()->update(['store_name' => $this->store_name]));
        session()->flash('message', 'Nama toko diperbarui. Nota dan navigasi akan memakai nama baru.');
        $this->redirectRoute('settings');
    }

    public function updateProfile(): void
    {
        $data = $this->validate(['name' => 'required|string|max:150', 'email' => 'required|email|max:255|unique:users,email,'.Auth::id()]);
        DB::transaction(fn () => Auth::user()->update($data));
        session()->flash('message', 'Profil diperbarui.');
    }

    public function updatePassword(): mixed
    {
        $this->validate(['current_password' => 'required|current_password', 'new_password' => 'required|string|min:12|confirmed']);
        $user = Auth::user();
        DB::transaction(function () use ($user): void {
            $user->forceFill(['password' => Hash::make($this->new_password), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('message', 'Password diperbarui. Silakan masuk kembali.');

        return redirect()->route('login');
    }

    public function render(): View
    {
        return view('livewire.settings');
    }
}
