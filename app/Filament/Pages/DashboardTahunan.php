<?php

namespace App\Filament\Pages;

use App\Services\Reports\DashboardTahunanReportService;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class DashboardTahunan extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Dashboard Tahunan';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Dashboard Tahunan Monitoring Anggaran';

    protected static ?string $slug = 'laporan/tahunan';

    protected string $view = 'filament.pages.dashboard-tahunan';

    public int $tahun;
    public string $quarter = 'ALL';

    public function mount(): void
    {
        $this->tahun = now()->year;
    }

    public function setQuarter(string $quarter): void
    {
        if (! in_array($quarter, ['ALL', 'Q1', 'Q2', 'Q3', 'Q4'], true)) {
            return;
        }

        $this->quarter = $quarter;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('view_dashboard') ?? false;
    }

    #[Computed]
    public function reportData(): array
    {
        return app(DashboardTahunanReportService::class)->build($this->tahun);
    }

    #[Computed]
    public function yearOptions(): array
    {
        $year = now()->year;

        return array_combine(
            range($year + 1, $year - 5),
            range($year + 1, $year - 5),
        );
    }
}
