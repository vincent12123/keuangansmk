<?php

namespace App\Filament\Pages;

use App\Services\Reports\PivotCashBankReportService;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class PivotCashBank extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Pivot Cash & Bank';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Pivot Rekap Cash & Bank';

    protected static ?string $slug = 'laporan/pivot-cash-bank';

    protected string $view = 'filament.pages.pivot-cash-bank';

    public int $bulan;

    public int $tahun;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isBendahara();
    }

    #[Computed]
    public function reportData(): array
    {
        return app(PivotCashBankReportService::class)->build($this->bulan, $this->tahun);
    }

    #[Computed]
    public function monthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
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
