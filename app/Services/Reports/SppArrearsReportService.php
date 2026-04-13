<?php

namespace App\Services\Reports;

use App\Models\KartuSpp;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;

class SppArrearsReportService
{
    public function build(int $bulan, int $tahun, ?int $jurusanId = null, ?int $kelasId = null): array
    {
        $baseQuery = Siswa::query()
            ->aktif()
            ->with(['kelas', 'jurusan'])
            ->when($jurusanId, fn (Builder $query) => $query->where('jurusan_id', $jurusanId))
            ->when($kelasId, fn (Builder $query) => $query->where('kelas_id', $kelasId));

        $allStudents = $baseQuery->get();
        $paidNis = KartuSpp::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('nis')
            ->all();

        $arrears = $allStudents
            ->reject(fn (Siswa $siswa) => in_array($siswa->nis, $paidNis, true))
            ->values();

        $totalAktif = $allStudents->count();
        $totalBelumBayar = $arrears->count();
        $totalSudahBayar = $totalAktif - $totalBelumBayar;
        $totalNominal = (float) $arrears->sum('nominal_spp');

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_siswa_aktif' => $totalAktif,
            'total_sudah_bayar' => $totalSudahBayar,
            'total_belum_bayar' => $totalBelumBayar,
            'persen_sudah_bayar' => $totalAktif > 0 ? round(($totalSudahBayar / $totalAktif) * 100, 2) : 0,
            'persen_belum_bayar' => $totalAktif > 0 ? round(($totalBelumBayar / $totalAktif) * 100, 2) : 0,
            'total_nominal_tunggakan' => $totalNominal,
            'rows' => $arrears->map(function (Siswa $siswa): array {
                return [
                    'nis' => $siswa->nis,
                    'nama' => $siswa->nama,
                    'kelas' => $siswa->kelas?->nama_kelas ?? '-',
                    'jurusan' => $siswa->jurusan?->kode ?? '-',
                    'nama_jurusan' => $siswa->jurusan?->nama ?? '-',
                    'nominal_spp' => (float) $siswa->nominal_spp,
                    'no_hp_wali' => $siswa->no_hp_wali,
                    'nama_wali' => $siswa->nama_wali,
                ];
            }),
        ];
    }
}
