<?php

namespace App\Exports;

use App\Services\Reports\PettyCashReportService;
use App\Support\ReportHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KasKecilExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
    ) {}

    public function array(): array
    {
        $report = app(PettyCashReportService::class)->build($this->bulan, $this->tahun);
        $saldo = $report['total_pengisian'];

        $rows = [
            ['SMK KARYA BANGSA SINTANG'],
            ['REKAP KAS KECIL'],
            ['BULAN : ' . ReportHelper::monthNameUpper($this->bulan) . ' ' . $this->tahun],
            [''],
            ['Total Pengisian', $report['total_pengisian']],
            ['Total Pengeluaran', $report['total_pengeluaran']],
            ['Saldo Akhir', $report['saldo']],
            ['Validasi ke Arus Kas', $report['validation_diff']],
            [''],
            ['No', 'Tanggal', 'Kode Akun', 'Uraian', 'No Ref', 'Kredit', 'Saldo'],
        ];

        foreach ($report['transactions'] as $index => $row) {
            $saldo -= (float) $row['nominal'];

            $rows[] = [
                $index + 1,
                $row['tanggal']?->format('d/m/Y'),
                $row['kode'],
                $row['uraian'],
                $row['no_ref'],
                $row['nominal'],
                $saldo,
            ];
        }

        $rows[] = [''];
        $rows[] = ['Pivot Rekap'];
        $rows[] = ['Kode', 'Nama Akun', 'Total'];

        foreach ($report['pivot'] as $pivot) {
            $rows[] = [$pivot['kode'], $pivot['nama'], $pivot['total']];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F:G')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            10 => ['font' => ['bold' => true]],
        ];
    }
}
