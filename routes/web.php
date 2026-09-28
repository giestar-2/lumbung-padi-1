<?php

use App\Http\Controllers\SaleReceiptController;
use App\Http\Middleware\EnsureOwner;
use App\Livewire\Auth\Login;
use App\Livewire\Batches\Show;
use App\Livewire\Dashboard;
use App\Livewire\Products\Form;
use App\Livewire\Products\Index;
use App\Livewire\Sales\Pos;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated Routes
Route::middleware(['auth', 'auth.session', EnsureOwner::class])->group(function () {
    // Dashboard
    Route::get('/', Dashboard::class)->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');

    Route::get('/products', Index::class)->name('products.index');
    Route::get('/products/form/{id?}', Form::class)->name('products.form');
    Route::get('/batches', App\Livewire\Batches\Index::class)->name('batches.index');
    Route::get('/batches/form', App\Livewire\Batches\Form::class)->name('batches.form');
    Route::get('/batches/{id}', Show::class)->name('batches.show');
    Route::get('/sales', App\Livewire\Sales\Index::class)->name('sales.index');
    Route::get('/sales/pos', Pos::class)->name('sales.pos');
    Route::get('/sales/{sale}/receipt', SaleReceiptController::class)->name('sales.receipt');
    Route::get('/cash', App\Livewire\Cash\Index::class)->name('cash.index');
    Route::get('/payrolls', App\Livewire\Payrolls\Index::class)->name('payrolls.index');
    Route::get('/customers', App\Livewire\Customers\Index::class)->name('customers.index');
    Route::get('/employees', App\Livewire\Employees\Index::class)->name('employees.index');
    Route::get('/settings', Settings::class)->name('settings');
});
