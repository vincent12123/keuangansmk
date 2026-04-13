<?php

namespace App\Services\Reports;

use App\Models\JurnalKas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PivotCashBankReportService
{
    public function build(int $bulan, int $tahun): array
    {
        $rows = JurnalKas::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select([
                'kode_akun_id',
                DB::raw('SUM(cash) as total_cash'),
                DB::raw('SUM(bank) as total_bank'),
                DB::raw('SUM(cash + bank) as total_nominal'),
            ])
            ->with('kodeAkun')
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

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rows' => $rows,
            'grand_total_cash' => (float) $rows->sum('cash'),
            'grand_total_bank' => (float) $rows->sum('bank'),
            'grand_total' => (float) $rows->sum('total'),
        ];
    }
}
