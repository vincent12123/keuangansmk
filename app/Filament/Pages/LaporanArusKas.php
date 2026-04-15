<?php

namespace App\Filament\Pages;

use App\Exports\ArusKasBulananExport;
use App\Services\AuditTrailService;
use App\Services\ExportPdfService;
use App\Services\Integrations\SmartsisSppSyncService;
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
                    app(AuditTrailService::class)->logExport('arus_kas_excel', [
                        'bulan' => $this->bulan,
                        'tahun' => $this->tahun,
                    ]);

                    return Excel::download(
                        new ArusKasBulananExport($this->bulan, $this->tahun),
                        $filename,
                    );
                }),
            Actions\Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('pdf.arus-kas', [
                    'bulan' => $this->bulan,
                    'tahun' => $this->tahun,
                ]))
                ->openUrlInNewTab(),
            Actions\Action::make('sync_spp_smartsis')
                ->label('Sync SPP SmartSIS')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->isAdmin() && (bool) config('spp_integration.enabled'))
                ->requiresConfirmation()
                ->modalHeading('Sinkronkan pembayaran SPP')
                ->modalDescription('Tarik pembayaran SPP dari SmartSIS untuk bulan dan tahun yang sedang dipilih, lalu simpan ke jurnal kas tanpa duplikasi.')
                ->action(function () {
                    $this->authorizeAdminAction();

                    if (app(SaldoKasService::class)->isLocked($this->bulan, $this->tahun)) {
                        Notification::make()
                            ->title('Bulan ini sudah dikunci.')
                            ->body('Buka kunci bulan terlebih dahulu sebelum melakukan sinkronisasi SPP dari SmartSIS.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = app(SmartsisSppSyncService::class)
                        ->syncMonth($this->bulan, $this->tahun, auth()->id());

                    unset($this->reportData);

                    Notification::make()
                        ->title('Sinkronisasi SPP selesai.')
                        ->body(
                            sprintf(
                                'Diambil %d data. Baru: %d, diperbarui: %d, dilewati: %d, dihapus: %d.',
                                $result['fetched'],
                                $result['created'],
                                $result['updated'],
                                $result['skipped'],
                                $result['deleted'],
                            )
                        )
                        ->success()
                        ->send();

                    if ($result['errors'] !== []) {
                        Notification::make()
                            ->title('Ada data yang tidak tersinkron penuh.')
                            ->body(implode(' ', array_slice($result['errors'], 0, 3)))
                            ->warning()
                            ->send();
                    }
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
