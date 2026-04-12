<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\KartuSppResource;
use App\Models\Siswa;
use App\Models\KartuSpp;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Computed;

class TunggakanSpp extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = KartuSppResource::class;
    protected string $view = 'filament.resources.kartu-spp.tunggakan';

    public int $bulan;
    public int $tahun;
    public ?string $filterJurusan = null;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    // Ambil data tunggakan berdasarkan filter
    #[Computed]
    public function tunggakanData(): array
    {
        $bulan = $this->bulan;
        $tahun = $this->tahun;

        // Ambil semua siswa aktif
        $query = Siswa::aktif()->with(['kelas', 'jurusan']);
        if ($this->filterJurusan) {
            $query->where('jurusan_id', $this->filterJurusan);
        }

        $semua = $query->get();

        // Ambil NIS yang sudah bayar untuk bulan & tahun ini
        $sudahBayar = KartuSpp::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('nis')
            ->toArray();

        $tunggakan = $semua->filter(fn ($s) => ! in_array($s->nis, $sudahBayar));

        return $tunggakan->map(fn ($s) => [
            'nis'         => $s->nis,
            'nama'        => $s->nama,
            'kelas'       => $s->kelas->nama_kelas ?? '-',
            'jurusan'     => $s->jurusan->kode ?? '-',
            'nominal_spp' => $s->nominal_spp,
            'no_hp_wali'  => $s->no_hp_wali,
            'nama_wali'   => $s->nama_wali,
        ])->values()->toArray();
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
}
