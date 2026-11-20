<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Cegah N+1 query secara otomatis pada lingkungan non-production
        Model::preventLazyLoading(! $this->app->isProduction());

        // Standar panjang password aplikasi sesuai NIST SP 800-63B Rev. 4 (15-64 karakter)
        Password::defaults(function () {
            return Password::min(15)->max(64);
        });
    }
}
