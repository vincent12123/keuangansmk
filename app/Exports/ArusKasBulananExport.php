<?php

namespace App\Exports;

use App\Services\Reports\CashFlowReportService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ArusKasBulananExport implements WithMultipleSheets
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
        protected bool $semuaBulan = false,
    ) {}

    public function sheets(): array
    {
        $service = app(CashFlowReportService::class);

        if (! $this->semuaBulan) {
            return [
                new ArusKasBulananSheet($this->bulan, $this->tahun, $service->build($this->bulan, $this->tahun)),
            ];
        }

        $sheets = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $report = $service->build($bulan, $this->tahun);
            $sheets[] = new ArusKasBulananSheet($bulan, $this->tahun, $report);
        }

        return $sheets;
    }
}
