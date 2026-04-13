<?php

namespace App\Filament\Pages;

use App\Exports\ArusKasBulananExport;
use App\Services\ExportPdfService;
use App\Services\Reports\CashFlowReportService;
use App\Services\Reports\SaldoKasService;
use App\Support\ReportHelper;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;

class LaporanArusKas extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Arus Kas Bulanan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Laporan Arus Kas Bulanan';

    protected static ?string $slug = 'laporan/arus-kas';

    protected string $view = 'filament.pages.laporan-arus-kas';

    public int $bulan;

    public int $tahun;

    public float $saldoAwalCash = 0;

    public float $saldoAwalBank = 0;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;

        $this->syncOpeningBalance();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('view_laporan_arus_kas') ?? false;
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
                    $filename = 'Arus-Kas-' . ReportHelper::monthName($this->bulan) . '-' . $this->tahun . '.xlsx';

                    return Excel::download(
                        new ArusKasBulananExport($this->bulan, $this->tahun),
                        $filename,
                    );
                }),
            Actions\Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function () {
                    return app(ExportPdfService::class)->stream(
                        'pdf.arus-kas-bulanan',
                        [
                            'report' => $this->reportData,
                            'namaBulan' => ReportHelper::monthName($this->bulan),
                            'tahun' => $this->tahun,
                            'namaBendahara' => 'Bendahara SMK',
                            'namaKepalaSekolah' => 'Kepala Sekolah',
                        ],
                        'Arus-Kas-' . ReportHelper::monthName($this->bulan) . '-' . $this->tahun . '.pdf',
                    );
                }),
        ];
    }

    public function updatedBulan(): void
    {
        $this->syncOpeningBalance();
    }

    public function updatedTahun(): void
    {
        $this->syncOpeningBalance();
    }

    public function simpanSaldoAwal(): void
    {
        $this->authorizeAdminAction();
        $service = app(SaldoKasService::class);

        $service->saveOpeningBalance(
            $this->bulan,
            $this->tahun,
            (float) $this->saldoAwalCash,
            (float) $this->saldoAwalBank,
        );

        Notification::make()
            ->title('Saldo awal berhasil disimpan.')
            ->success()
            ->send();

        unset($this->reportData);
    }

    public function kunciBulan(): void
    {
        $this->authorizeAdminAction();
        $service = app(SaldoKasService::class);

        $service->saveOpeningBalance(
            $this->bulan,
            $this->tahun,
            (float) $this->saldoAwalCash,
            (float) $this->saldoAwalBank,
        );
        $service->lockPeriod($this->bulan, $this->tahun);
        $this->syncOpeningBalance();

        Notification::make()
            ->title('Bulan berhasil dikunci dan saldo bulan berikutnya sudah disiapkan.')
            ->success()
            ->send();
    }

    public function bukaKunciBulan(): void
    {
        $this->authorizeAdminAction();

        app(SaldoKasService::class)->unlockPeriod($this->bulan, $this->tahun);
        $this->syncOpeningBalance();

        Notification::make()
            ->title('Bulan berhasil dibuka. Saldo awal bulan berikutnya direset untuk perhitungan ulang.')
            ->success()
            ->send();
    }

    #[Computed]
    public function reportData(): array
    {
        return app(CashFlowReportService::class)->build($this->bulan, $this->tahun);
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

    protected function syncOpeningBalance(): void
    {
        $opening = app(SaldoKasService::class)->getOpeningBalance($this->bulan, $this->tahun);

        $this->saldoAwalCash = (float) $opening['cash'];
        $this->saldoAwalBank = (float) $opening['bank'];
    }

    protected function authorizeAdminAction(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }
    }
}
