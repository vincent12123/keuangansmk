<?php

namespace App\Services\Reports;

use App\Models\SaldoKasBulanan;

class SaldoKasService
{
    public function getOpeningBalance(int $bulan, int $tahun): array
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

        $cashFlow = app(CashFlowReportService::class)->build($prevMonth, $prevYear);

        return [
            'cash' => (float) $cashFlow['saldo_akhir_cash'],
            'bank' => (float) $cashFlow['saldo_akhir_bank'],
            'is_locked' => false,
            'source' => 'previous_locked',
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
        $report = app(CashFlowReportService::class)->build($bulan, $tahun);

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

    public function unlockPeriod(int $bulan, int $tahun): void
    {
        $record = SaldoKasBulanan::query()->bulanTahun($bulan, $tahun)->first();

        if (! $record) {
            return;
        }

        $record->update([
            'is_locked' => false,
        ]);

        [$nextMonth, $nextYear] = $this->nextPeriod($bulan, $tahun);
        $nextRecord = SaldoKasBulanan::query()->bulanTahun($nextMonth, $nextYear)->first();

        if ($nextRecord && ! $nextRecord->is_locked) {
            $nextRecord->update([
                'saldo_awal_cash' => 0,
                'saldo_awal_bank' => 0,
            ]);
        }
    }

    public function isLocked(int $bulan, int $tahun): bool
    {
        return (bool) SaldoKasBulanan::query()
            ->bulanTahun($bulan, $tahun)
            ->value('is_locked');
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
