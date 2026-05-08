<?php

namespace App\Providers;

use App\Support\LegacyClinicContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LegacyClinicContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'company.*',
            'employee.*',
            'surveillance.*',
            'audiometry.*',
            'report.*',
        ], function ($view): void {
            $request = $this->app['request'];
            $context = $this->app->make(LegacyClinicContext::class);
            $payload = $context->compose($view->getName(), $view->getData(), $request);

            if ($payload !== []) {
                $view->with($payload);
            }
        });
    }
}
