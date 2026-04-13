<?php

namespace App\Services\Reports;

use App\Models\Anggaran;
use App\Models\JurnalKas;
use App\Models\KasKecil;
use App\Models\KodeAkun;
use App\Models\SaldoKasBulanan;
use Illuminate\Support\Collection;

class DashboardTahunanReportService
{
    public function build(int $tahun): array
    {
        $jurnal = JurnalKas::query()
            ->where('tahun', $tahun)
            ->selectRaw('bulan, kode_akun_id, SUM(cash + bank) as total')
            ->groupBy('bulan', 'kode_akun_id')
            ->get();

        $kasKecil = KasKecil::query()
            ->where('tahun', $tahun)
            ->selectRaw('bulan, kode_akun_id, SUM(nominal) as total')
            ->groupBy('bulan', 'kode_akun_id')
            ->get();

        $matrix = [];

        foreach ($jurnal as $row) {
            $matrix[$row->kode_akun_id][$row->bulan] = ($matrix[$row->kode_akun_id][$row->bulan] ?? 0) + (float) $row->total;
        }

        foreach ($kasKecil as $row) {
            $matrix[$row->kode_akun_id][$row->bulan] = ($matrix[$row->kode_akun_id][$row->bulan] ?? 0) + (float) $row->total;
        }

        $budgets = Anggaran::query()
            ->where('tahun', $tahun)
            ->pluck('target', 'kode_akun_id');

        $rows = KodeAkun::query()
            ->transaksional()
            ->orderBy('kode')
            ->get()
            ->map(function (KodeAkun $kodeAkun) use ($matrix, $budgets): array {
                $months = [];

                for ($bulan = 1; $bulan <= 12; $bulan++) {
                    $months[$bulan] = (float) ($matrix[$kodeAkun->id][$bulan] ?? 0);
                }

                $akumulasi = array_sum($months);
                $anggaran = (float) ($budgets[$kodeAkun->id] ?? 0);
                $persen = $anggaran > 0 ? round(($akumulasi / $anggaran) * 100, 2) : null;

                return [
                    'kode' => $kodeAkun->kode,
                    'nama' => $kodeAkun->nama,
                    'tipe' => $kodeAkun->tipe,
                    'kategori' => $kodeAkun->kategori,
                    'months' => $months,
                    'akumulasi' => $akumulasi,
                    'anggaran' => $anggaran,
                    'persen' => $persen,
                    'selisih' => $akumulasi - $anggaran,
                    'status' => $this->resolveBudgetStatus($kodeAkun->tipe, $persen),
                ];
            });

        $openingBalances = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $record = SaldoKasBulanan::query()->bulanTahun($bulan, $tahun)->first();
            $openingBalances[$bulan] = $record
                ? (float) $record->saldo_awal_cash + (float) $record->saldo_awal_bank
                : 0.0;
        }

        return [
            'tahun' => $tahun,
            'opening_balances' => $openingBalances,
            'rows' => $rows,
            'month_totals' => $this->buildMonthTotals($rows),
        ];
    }

    protected function resolveBudgetStatus(string $tipe, ?float $persen): string
    {
        if ($persen === null) {
            return 'gray';
        }

        if ($tipe === 'pendapatan') {
            return match (true) {
                $persen >= 100 => 'success',
                $persen >= 80 => 'warning',
                default => 'danger',
            };
        }

        return match (true) {
            $persen >= 100 => 'danger',
            $persen >= 80 => 'warning',
            default => 'success',
        };
    }

    protected function buildMonthTotals(Collection $rows): array
    {
        $totals = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $totals[$bulan] = (float) $rows->sum(fn (array $row) => $row['months'][$bulan] ?? 0);
        }

        return $totals;
    }
}
