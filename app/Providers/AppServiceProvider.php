<?php

namespace App\Providers;

use App\Facades\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One instance per request: the store memoises the cache read, so every
        // Setting::get() after the first costs nothing.
        $this->app->singleton(Settings::class);
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

        // Laravel 12 ships no aliases array, and the facade is worth the short
        // name: settings are read from Blade all over the admin panel.
        AliasLoader::getInstance()->alias('Setting', Setting::class);
    }
}
