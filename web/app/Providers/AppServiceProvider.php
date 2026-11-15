<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\StockService::class);
        $this->app->singleton(\App\Services\LedgerService::class);
        $this->app->singleton(\App\Services\AuditService::class);
        $this->app->singleton(\App\Services\DocumentNumberService::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'customer' => Customer::class,
            'supplier' => Supplier::class,
        ]);

        Paginator::defaultView('pagination');

        Gate::before(function (User $user) {
            if ($user->is_super_admin) {
                return true;
            }
        });

        foreach (array_keys(\Database\Seeders\PermissionSeeder::moduleNames()) as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $key = $module.'.'.$action;
                Gate::define($key, fn (User $user) => $user->hasPermission($key));
            }
        }
    }
}
