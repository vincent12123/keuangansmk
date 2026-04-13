<?php

namespace App\Filament\Pages;

use App\Services\Reports\PettyCashReportService;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class LaporanKasKecil extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wallet';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rekap Kas Kecil';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Rekap Kas Kecil Bulanan';

    protected string $view = 'filament.pages.laporan-kas-kecil';

    public int $bulan;

    public int $tahun;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('view_laporan_kas_kecil') ?? false;
    }

    #[Computed]
    public function reportData(): array
    {
        return app(PettyCashReportService::class)->build($this->bulan, $this->tahun);
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
