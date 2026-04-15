<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KartuSppResource;
use App\Models\Jurusan;
use App\Services\Reports\SppArrearsReportService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Computed;

class TunggakanSpp extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = KartuSppResource::class;
    protected string $view = 'filament.resources.kartu-spps.tunggakan';

    public int $bulan;
    public int $tahun;
    public ?string $filterJurusan = null;
    public ?string $filterKelas = null;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    // Ambil data tunggakan berdasarkan filter
    #[Computed]
    public function tunggakanData(): array
    {
        return app(SppArrearsReportService::class)
            ->build($this->bulan, $this->tahun, $this->filterJurusan, $this->filterKelas)['rows'];
    }

    #[Computed]
    public function namaBulan(): string
    {
        return [
            1=>'Januari', 2=>'Februari', 3=>'Maret',
            4=>'April',   5=>'Mei',       6=>'Juni',
            7=>'Juli',    8=>'Agustus',   9=>'September',
            10=>'Oktober',11=>'November', 12=>'Desember',
        ][$this->bulan] ?? (string) $this->bulan;
    }

    #[Computed]
    public function jurusanOptions(): array
    {
        if ((bool) config('spp_integration.enabled')) {
            return app(SppArrearsReportService::class)->getIntegrationFilterOptions()['jurusan'];
        }

        return Jurusan::aktif()
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    #[Computed]
    public function kelasOptions(): array
    {
        if ((bool) config('spp_integration.enabled')) {
            $options = app(SppArrearsReportService::class)->getIntegrationFilterOptions();
            $kelas = $options['kelas'];

            if (! $this->filterJurusan) {
                return $kelas;
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

        return [];
    }
}
