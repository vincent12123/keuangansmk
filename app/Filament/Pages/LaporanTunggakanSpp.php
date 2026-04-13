<?php

namespace App\Filament\Pages;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Services\Reports\SppArrearsReportService;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class LaporanTunggakanSpp extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rekap Tunggakan SPP';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Rekap Tunggakan SPP';

    protected string $view = 'filament.pages.laporan-tunggakan-spp';

    public int $bulan;

    public int $tahun;

    public ?int $filterJurusan = null;

    public ?int $filterKelas = null;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('view_laporan_spp') ?? false;
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
        return Jurusan::query()
            ->aktif()
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    #[Computed]
    public function kelasOptions(): array
    {
        return Kelas::query()
            ->aktif()
            ->when($this->filterJurusan, fn ($query) => $query->where('jurusan_id', $this->filterJurusan))
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas', 'id')
            ->all();
    }
}
