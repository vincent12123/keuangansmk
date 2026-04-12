<?php

namespace App\Providers\Filament;

use App\Filament\Resources\JurnalKasResource;
use App\Filament\Resources\KartuSppResource;
use App\Filament\Resources\KasKecilResource;
use App\Filament\Resources\KodeAkunResource;
use App\Filament\Resources\SiswaResource;
use App\Filament\Resources\JurusanResource;
use App\Filament\Resources\KelasResource;
use App\Filament\Widgets\RingkasanBulanWidget;
use App\Filament\Widgets\ArusKasBulananWidget;
use App\Filament\Widgets\TunggakanSppWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'gray'    => Color::Slate,
            ])
            ->brandName('Keuangan SMK Karya Bangsa')
            ->brandLogo(asset('images/logo-smk.png')) // Tambahkan logo SMK jika ada
            ->favicon(asset('favicon.ico'))
            ->darkMode(false) // Matikan dark mode untuk kemudahan penggunaan
            ->sidebarCollapsibleOnDesktop()

            // ─── Navigasi ─────────────────────────────────────
            ->navigationGroups([
                'Dashboard',
                'Transaksi',
                'Master Data',
                'Laporan',
                'Pengaturan',
            ])

            // ─── Resources ────────────────────────────────────
            ->resources([
                JurnalKasResource::class,
                KasKecilResource::class,
                KartuSppResource::class,
                KodeAkunResource::class,
                SiswaResource::class,
                JurusanResource::class,
                KelasResource::class,
            ])

            // ─── Pages ────────────────────────────────────────
            ->pages([
                Pages\Dashboard::class,
            ])

            // ─── Widgets di Dashboard ─────────────────────────
            ->widgets([
                RingkasanBulanWidget::class,
                ArusKasBulananWidget::class,
                TunggakanSppWidget::class,
            ])

            // ─── Middleware ───────────────────────────────────
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])

            // ─── Notifikasi ───────────────────────────────────
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
