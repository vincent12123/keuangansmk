<?php

namespace App\Exports;

use App\Services\Reports\PettyCashReportService;
use App\Support\ReportHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PivotKasKecilExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
    ) {}

    public function array(): array
    {
        $report = app(PettyCashReportService::class)->build($this->bulan, $this->tahun);

        $rows = [
            ['SMK KARYA BANGSA SINTANG'],
            ['PIVOT KAS KECIL'],
            ['BULAN : ' . ReportHelper::monthNameUpper($this->bulan) . ' ' . $this->tahun],
            [''],
            ['Total Pengisian', $report['total_pengisian']],
            ['Total Pengeluaran', $report['total_pengeluaran']],
            ['Saldo', $report['saldo']],
            ['Validasi ke Arus Kas', $report['validation_diff']],
            [''],
            ['Kode', 'Nama Akun', 'Total'],
        ];

        foreach ($report['pivot'] as $row) {
            $rows[] = [$row['kode'], $row['nama'], $row['total']];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        $sheet->getStyle('B:C')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            10 => ['font' => ['bold' => true]],
        ];
    }
}
