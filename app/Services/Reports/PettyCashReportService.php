<?php

namespace App\Services\Reports;

use App\Models\KasKecil;
use App\Models\PengisianKasKecil;
use Illuminate\Support\Collection;

class PettyCashReportService
{
    public function build(int $bulan, int $tahun): array
    {
        $pengisian = (float) PengisianKasKecil::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sum('nominal');

        $pengeluaran = (float) KasKecil::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sum('nominal');

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_pengisian' => $pengisian,
            'total_pengeluaran' => $pengeluaran,
            'saldo' => $pengisian - $pengeluaran,
            'transactions' => $this->getTransactions($bulan, $tahun),
            'pivot' => $this->getPivot($bulan, $tahun),
        ];
    }

    protected function getTransactions(int $bulan, int $tahun): Collection
    {
        return KasKecil::query()
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(function (KasKecil $kasKecil): array {
                return [
                    'tanggal' => $kasKecil->tanggal,
                    'no_ref' => $kasKecil->no_ref,
                    'kode' => $kasKecil->kodeAkun?->kode ?? '-',
                    'nama' => $kasKecil->kodeAkun?->nama ?? '-',
                    'uraian' => $kasKecil->uraian,
                    'nominal' => (float) $kasKecil->nominal,
                ];
            });
    }

    protected function getPivot(int $bulan, int $tahun): Collection
    {
        return KasKecil::query()
            ->selectRaw('kode_akun_id, SUM(nominal) as total_nominal')
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->groupBy('kode_akun_id')
            ->orderBy('kode_akun_id')
            ->get()
            ->map(function (KasKecil $row): array {
                return [
                    'kode' => $row->kodeAkun?->kode ?? '-',
                    'nama' => $row->kodeAkun?->nama ?? '-',
                    'total' => (float) $row->total_nominal,
                ];
            });
    }
}
