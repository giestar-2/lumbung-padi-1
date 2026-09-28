<?php

namespace App\Providers;

use App\Http\Middleware\EnsureOwner;
use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\SummaryCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Model::preventLazyLoading(! app()->isProduction());
        Gate::define('owner', fn (User $user): bool => $user->id === (int) User::min('id'));
        Livewire::addPersistentMiddleware([EnsureOwner::class]);
        foreach ([Product::class, Sale::class, SaleItem::class,
            Payment::class, CashEntry::class, Payroll::class,
            ProductionBatch::class, Customer::class, User::class] as $model) {
            $model::saved(fn () => SummaryCache::changed());
            $model::deleted(fn () => SummaryCache::changed());
        }
    }
}
