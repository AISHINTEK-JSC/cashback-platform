<?php

namespace App\Providers;

use App\Services\Affiliate\AffiliateManager;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(AffiliateManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Carbon::setLocale(config('app.locale'));

        if ($this->app->isProduction()) {
            URL::forceScheme('https');

            // Canonical, sitemap, og:url luôn dùng domain chính thức kể cả khi truy cập qua www hay domain phụ của hosting
            if (! str_contains((string) config('app.url'), 'localhost')) {
                URL::forceRootUrl(config('app.url'));
            }
        }

        Event::listen(DiagnosingHealth::class, fn () => DB::select('select 1'));
    }
}
