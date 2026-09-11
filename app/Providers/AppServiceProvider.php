<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Lazy loading is an N+1 waiting to happen on the invitation payload
        // query. Fail loudly everywhere except production.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::shouldBeStrict(! $this->app->isProduction());

        // docs/04 § 11: super-admin passes everything. Granting it here rather
        // than assigning every permission means new permissions are covered the
        // moment they exist, and there is one place to audit.
        // Returning null (not false) lets the normal checks run for everyone else.
        Gate::before(fn (User $user, string $ability) => $user->hasRole('super-admin') ? true : null);
    }
}
