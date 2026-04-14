<?php

namespace App\Filament\Pages;

use App\Exports\PivotKasKecilExport;
use App\Services\AuditTrailService;
use App\Services\ExportPdfService;
use App\Services\Reports\PettyCashReportService;
use App\Support\ReportHelper;
use Filament\Actions;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;

class LaporanKasKecil extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wallet';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Pivot Kas Kecil';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Pivot Rekap Kas Kecil Bulanan';

    protected static ?string $slug = 'laporan/pivot-kas-kecil';

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('export_laporan') ?? false)
                ->action(function () {
                    app(AuditTrailService::class)->logExport('pivot_kas_kecil_excel', [
                        'bulan' => $this->bulan,
                        'tahun' => $this->tahun,
                    ]);

                    return Excel::download(
                        new PivotKasKecilExport($this->bulan, $this->tahun),
                        'Pivot-Kas-Kecil-' . ReportHelper::monthName($this->bulan) . '-' . $this->tahun . '.xlsx',
                    );
                }),
            Actions\Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('pdf.rekap-kas-kecil', [
                    'bulan' => $this->bulan,
                    'tahun' => $this->tahun,
                ]))
                ->openUrlInNewTab(),
        ];
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
