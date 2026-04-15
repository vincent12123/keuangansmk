<?php

namespace App\Filament\Pages;

use App\Exports\TunggakanSppExport;
use App\Filament\Concerns\InteractsWithSmartsisSyncStatus;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Services\AuditTrailService;
use App\Services\Reports\SppArrearsReportService;
use App\Support\ReportHelper;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;

class LaporanTunggakanSpp extends Page
{
    use InteractsWithSmartsisSyncStatus;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rekap Tunggakan SPP';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Rekap Tunggakan SPP';

    protected static ?string $slug = 'laporan/tunggakan-spp';

    protected string $view = 'filament.pages.laporan-tunggakan-spp';

    public int $bulan;

    public int $tahun;

    public ?string $filterJurusan = null;

    public ?string $filterKelas = null;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('view_laporan_spp') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_smartsis')
                ->label('Sync Semua Data SmartSIS')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn (): bool => (bool) config('spp_integration.enabled'))
                ->requiresConfirmation()
                ->action(fn () => $this->queueSmartsisSyncForSelectedYear()),
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('export_laporan') ?? false)
                ->action(function () {
                    app(AuditTrailService::class)->logExport('tunggakan_spp_excel', [
                        'bulan' => $this->bulan,
                        'tahun' => $this->tahun,
                        'jurusan_id' => $this->filterJurusan,
                        'kelas_id' => $this->filterKelas,
                    ]);

                    return Excel::download(
                        new TunggakanSppExport($this->bulan, $this->tahun, $this->filterJurusan, $this->filterKelas),
                        'Tunggakan-SPP-' . ReportHelper::monthName($this->bulan) . '-' . $this->tahun . '.xlsx',
                    );
                }),
        ];
    }

    public function updatedFilterJurusan(): void
    {
        $this->filterKelas = null;
    }

    #[Computed]
    public function reportData(): array
    {
        return app(SppArrearsReportService::class)->build(
            $this->bulan,
            $this->tahun,
            $this->filterJurusan,
            $this->filterKelas,
        );
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

    #[Computed]
    public function jurusanOptions(): array
    {
        if ((bool) config('spp_integration.enabled')) {
            return app(SppArrearsReportService::class)->getIntegrationFilterOptions()['jurusan'];
        }

        return Jurusan::query()
            ->aktif()
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    #[Computed]
    public function kelasOptions(): array
    {
        if ((bool) config('spp_integration.enabled')) {
            if (! $this->filterJurusan) {
                return app(SppArrearsReportService::class)->getIntegrationFilterOptions()['kelas'];
            }

            $rows = app(SppArrearsReportService::class)->build($this->bulan, $this->tahun, $this->filterJurusan)['rows'];

            return collect($rows)
                ->pluck('kelas')
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn (string $name) => [$name => $name])
                ->all();
        }

        return Kelas::query()
            ->aktif()
            ->when($this->filterJurusan, fn ($query) => $query->where('jurusan_id', $this->filterJurusan))
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas', 'id')
            ->all();
    }
}
