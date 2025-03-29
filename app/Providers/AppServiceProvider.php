<?php

namespace App\Providers;

use App\Services\EarthquakeScraperService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(EarthquakeScraperService::class, function ($app) {
        //     return new EarthquakeScraperService();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
