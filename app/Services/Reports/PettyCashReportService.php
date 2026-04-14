<?php

namespace App\Services\Reports;

use App\Models\KasKecil;
use App\Models\PengisianKasKecil;
use Illuminate\Support\Arr;
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
            'validation_diff' => abs($pengeluaran - app(CashFlowReportService::class)->build($bulan, $tahun)['total_kas_kecil']),
            'transactions' => $this->getTransactions($bulan, $tahun),
            'pivot' => $this->getPivot($bulan, $tahun),
        ];
    }

    protected function getTransactions(int $bulan, int $tahun): Collection
    {
        $runningBalances = $this->getRunningBalances($bulan, $tahun);

        return KasKecil::query()
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(function (KasKecil $kasKecil) use ($runningBalances): array {
                return [
                    'id' => $kasKecil->id,
                    'tanggal' => $kasKecil->tanggal,
                    'no_ref' => $kasKecil->no_ref,
                    'kode' => $kasKecil->kodeAkun?->kode ?? '-',
                    'nama' => $kasKecil->kodeAkun?->nama ?? '-',
                    'uraian' => $kasKecil->uraian,
                    'nominal' => (float) $kasKecil->nominal,
                    'saldo' => (float) Arr::get($runningBalances, $kasKecil->id, 0),
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

    protected function getRunningBalances(int $bulan, int $tahun): array
    {
        $events = collect();

        PengisianKasKecil::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->each(function (PengisianKasKecil $pengisian) use ($events): void {
                $events->push([
                    'type' => 'pengisian',
                    'id' => $pengisian->id,
                    'sort_date' => $pengisian->tanggal?->format('Y-m-d') ?? '',
                    'sort_weight' => 0,
                    'nominal' => (float) $pengisian->nominal,
                ]);
            });

        KasKecil::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->each(function (KasKecil $kasKecil) use ($events): void {
                $events->push([
                    'type' => 'pengeluaran',
                    'id' => $kasKecil->id,
                    'sort_date' => $kasKecil->tanggal?->format('Y-m-d') ?? '',
                    'sort_weight' => 1,
                    'nominal' => (float) $kasKecil->nominal,
                ]);
            });

        $saldo = 0.0;
        $runningBalances = [];

        foreach ($events->sortBy([
            ['sort_date', 'asc'],
            ['sort_weight', 'asc'],
            ['id', 'asc'],
        ]) as $event) {
            if ($event['type'] === 'pengisian') {
                $saldo += $event['nominal'];

                continue;
            }

            $saldo -= $event['nominal'];
            $runningBalances[$event['id']] = $saldo;
        }

        return $runningBalances;
    }
}
