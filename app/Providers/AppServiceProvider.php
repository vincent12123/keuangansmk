<?php

namespace App\Providers;

use App\Models\JurnalKas;
use App\Observers\JurnalKasObserver;
use App\Services\TerbilangService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TerbilangService::class);
    }

    public function boot(): void
    {
        // Daftarkan observer
        JurnalKas::observe(JurnalKasObserver::class);

        // Format currency Indonesia
        \Filament\Support\Facades\FilamentColor::register([]);
    }
}
