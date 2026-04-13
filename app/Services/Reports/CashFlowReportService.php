<?php

namespace App\Services\Reports;

use App\Models\JurnalKas;
use App\Models\KasKecil;
use App\Models\KodeAkun;
use App\Models\SaldoKasBulanan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashFlowReportService
{
    public function build(int $bulan, int $tahun): array
    {
        $opening = $this->resolveOpeningBalance($bulan, $tahun);
        $movements = $this->getMovementTotals($bulan, $tahun);

        $saldoAkhirCash = $opening['cash'] + $movements['masuk_cash'] - $movements['keluar_cash'] - $movements['kas_kecil'];
        $saldoAkhirBank = $opening['bank'] + $movements['masuk_bank'] - $movements['keluar_bank'];

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'is_locked' => $opening['is_locked'],
            'opening_source' => $opening['source'],
            'saldo_awal_cash' => $opening['cash'],
            'saldo_awal_bank' => $opening['bank'],
            'saldo_awal_total' => $opening['cash'] + $opening['bank'],
            'total_masuk_cash' => $movements['masuk_cash'],
            'total_masuk_bank' => $movements['masuk_bank'],
            'total_masuk' => $movements['masuk_cash'] + $movements['masuk_bank'],
            'total_keluar_besar_cash' => $movements['keluar_cash'],
            'total_keluar_besar_bank' => $movements['keluar_bank'],
            'total_keluar_besar' => $movements['keluar_cash'] + $movements['keluar_bank'],
            'total_kas_kecil' => $movements['kas_kecil'],
            'saldo_akhir_cash' => $saldoAkhirCash,
            'saldo_akhir_bank' => $saldoAkhirBank,
            'saldo_akhir_total' => $saldoAkhirCash + $saldoAkhirBank,
            'penerimaan' => $this->getGroupedJurnal($bulan, $tahun, 'masuk'),
            'pengeluaran_besar' => $this->getGroupedJurnal($bulan, $tahun, 'keluar'),
            'pengeluaran_kas_kecil' => $this->getGroupedKasKecil($bulan, $tahun),
        ];
    }

    public function saveOpeningBalance(int $bulan, int $tahun, float $cash, float $bank): SaldoKasBulanan
    {
        $record = SaldoKasBulanan::query()->bulanTahun($bulan, $tahun)->first();

        if ($record?->is_locked) {
            throw new \RuntimeException('Bulan ini sudah dikunci dan saldo awal tidak bisa diubah.');
        }

        return SaldoKasBulanan::updateOrCreate(
            ['bulan' => $bulan, 'tahun' => $tahun],
            [
                'saldo_awal_cash' => $cash,
                'saldo_awal_bank' => $bank,
                'is_locked' => false,
            ],
        );
    }

    public function lockPeriod(int $bulan, int $tahun): void
    {
        $report = $this->build($bulan, $tahun);

        SaldoKasBulanan::updateOrCreate(
            ['bulan' => $bulan, 'tahun' => $tahun],
            [
                'saldo_awal_cash' => $report['saldo_awal_cash'],
                'saldo_awal_bank' => $report['saldo_awal_bank'],
                'is_locked' => true,
            ],
        );

        [$nextMonth, $nextYear] = $this->nextPeriod($bulan, $tahun);

        $nextRecord = SaldoKasBulanan::query()->bulanTahun($nextMonth, $nextYear)->first();

        if (! $nextRecord || ! $nextRecord->is_locked) {
            SaldoKasBulanan::updateOrCreate(
                ['bulan' => $nextMonth, 'tahun' => $nextYear],
                [
                    'saldo_awal_cash' => $report['saldo_akhir_cash'],
                    'saldo_awal_bank' => $report['saldo_akhir_bank'],
                    'is_locked' => false,
                ],
            );
        }
    }

    protected function getMovementTotals(int $bulan, int $tahun): array
    {
        $masuk = JurnalKas::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'masuk')
            ->selectRaw('COALESCE(SUM(cash), 0) as cash_total, COALESCE(SUM(bank), 0) as bank_total')
            ->first();

        $keluar = JurnalKas::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'keluar')
            ->selectRaw('COALESCE(SUM(cash), 0) as cash_total, COALESCE(SUM(bank), 0) as bank_total')
            ->first();

        return [
            'masuk_cash' => (float) ($masuk->cash_total ?? 0),
            'masuk_bank' => (float) ($masuk->bank_total ?? 0),
            'keluar_cash' => (float) ($keluar->cash_total ?? 0),
            'keluar_bank' => (float) ($keluar->bank_total ?? 0),
            'kas_kecil' => (float) KasKecil::query()
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->sum('nominal'),
        ];
    }

    protected function getGroupedJurnal(int $bulan, int $tahun, string $jenis): Collection
    {
        return JurnalKas::query()
            ->select([
                'kode_akun_id',
                DB::raw('SUM(cash) as total_cash'),
                DB::raw('SUM(bank) as total_bank'),
                DB::raw('SUM(cash + bank) as total_nominal'),
            ])
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', $jenis)
            ->groupBy('kode_akun_id')
            ->orderBy('kode_akun_id')
            ->get()
            ->map(function (JurnalKas $row): array {
                return [
                    'kode' => $row->kodeAkun?->kode ?? '-',
                    'nama' => $row->kodeAkun?->nama ?? '-',
                    'cash' => (float) $row->total_cash,
                    'bank' => (float) $row->total_bank,
                    'total' => (float) $row->total_nominal,
                ];
            });
    }

    protected function getGroupedKasKecil(int $bulan, int $tahun): Collection
    {
        return KasKecil::query()
            ->select([
                'kode_akun_id',
                DB::raw('SUM(nominal) as total_nominal'),
            ])
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

    protected function resolveOpeningBalance(int $bulan, int $tahun): array
    {
        $current = SaldoKasBulanan::query()->bulanTahun($bulan, $tahun)->first();

        if ($current) {
            return [
                'cash' => (float) $current->saldo_awal_cash,
                'bank' => (float) $current->saldo_awal_bank,
                'is_locked' => (bool) $current->is_locked,
                'source' => 'stored',
            ];
        }

        [$prevMonth, $prevYear] = $this->previousPeriod($bulan, $tahun);
        $previous = SaldoKasBulanan::query()->bulanTahun($prevMonth, $prevYear)->first();

        if (! $previous || ! $previous->is_locked) {
            return [
                'cash' => 0.0,
                'bank' => 0.0,
                'is_locked' => false,
                'source' => 'default_zero',
            ];
        }

        $previousMovements = $this->getMovementTotals($prevMonth, $prevYear);

        return [
            'cash' => (float) $previous->saldo_awal_cash + $previousMovements['masuk_cash'] - $previousMovements['keluar_cash'] - $previousMovements['kas_kecil'],
            'bank' => (float) $previous->saldo_awal_bank + $previousMovements['masuk_bank'] - $previousMovements['keluar_bank'],
            'is_locked' => false,
            'source' => 'previous_locked',
        ];
    }

    protected function previousPeriod(int $bulan, int $tahun): array
    {
        return $bulan === 1
            ? [12, $tahun - 1]
            : [$bulan - 1, $tahun];
    }

    protected function nextPeriod(int $bulan, int $tahun): array
    {
        return $bulan === 12
            ? [1, $tahun + 1]
            : [$bulan + 1, $tahun];
    }
}
