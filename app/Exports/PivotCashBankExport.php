<?php

namespace App\Exports;

use App\Services\Reports\PivotCashBankReportService;
use App\Support\ReportHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PivotCashBankExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
    ) {}

    public function array(): array
    {
        $report = app(PivotCashBankReportService::class)->build($this->bulan, $this->tahun);

        $rows = [
            ['SMK KARYA BANGSA SINTANG'],
            ['PIVOT CASH & BANK'],
            ['BULAN : ' . ReportHelper::monthNameUpper($this->bulan) . ' ' . $this->tahun],
            [''],
            ['Kode', 'Nama Akun', 'Cash', 'Bank', 'Total'],
        ];

        foreach ($report['rows'] as $row) {
            $rows[] = [
                $row['kode'],
                $row['nama'],
                $row['cash'],
                $row['bank'],
                $row['total'],
            ];
        }

        $rows[] = ['TOTAL', '', $report['grand_total_cash'], $report['grand_total_bank'], $report['grand_total']];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('C:E')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            5 => ['font' => ['bold' => true]],
        ];
    }
}
