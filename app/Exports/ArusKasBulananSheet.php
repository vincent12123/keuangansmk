<?php

namespace App\Exports;

use App\Support\ReportHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArusKasBulananSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
        protected array $report,
    ) {}

    public function array(): array
    {
        $rows = [
            ['SMK KARYA BANGSA SINTANG'],
            ['LAPORAN PENERIMAAN DAN PENGELUARAN'],
            ['BULAN : ' . ReportHelper::monthNameUpper($this->bulan) . ' ' . $this->tahun],
            [''],
            ['Bagian', 'Kode', 'Uraian', 'Nominal'],
            ['A', '', 'Saldo Awal Operasional', $this->report['saldo_awal_total']],
            [''],
            ['B', '', 'PENERIMAAN', ''],
        ];

        foreach ($this->report['penerimaan_sections'] as $sectionKey => $section) {
            $rows[] = [$sectionKey, '', $section['title'], ''];

            foreach ($section['rows'] as $row) {
                $rows[] = ['', $row['kode'], $row['nama'], $row['total']];
            }

            $rows[] = ['', '', 'Subtotal ' . $section['title'], $section['total']];
            $rows[] = [''];
        }

        $rows[] = ['B', '', 'TOTAL SELURUH PENERIMAAN', $this->report['total_masuk']];
        $rows[] = [''];
        $rows[] = ['C', '', 'PENGELUARAN', ''];

        foreach ($this->report['pengeluaran_sections'] as $sectionKey => $section) {
            $rows[] = [$sectionKey, '', $section['title'], ''];

            foreach ($section['rows'] as $row) {
                $rows[] = ['', $row['kode'], $row['nama'], $row['total']];
            }

            $rows[] = ['', '', 'Subtotal ' . $section['title'], $section['total']];
            $rows[] = [''];
        }

        $rows[] = ['C', '', 'JUMLAH PENGELUARAN', $this->report['total_pengeluaran']];
        $rows[] = ['', '', 'Selisih Penerimaan dengan Pengeluaran', $this->report['selisih']];
        $rows[] = [''];
        $rows[] = ['D', '', 'SALDO AKHIR OPERASIONAL', $this->report['saldo_akhir_total']];
        $rows[] = ['', '', 'Kas Kecil', $this->report['saldo_kas_kecil']];
        $rows[] = ['', '', 'Kas Besar', $this->report['saldo_kas_besar']];
        $rows[] = ['', '', 'Jumlah Saldo Kas', $this->report['saldo_akhir_total']];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A3:D3');
        $sheet->getStyle('D:D')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            3 => ['alignment' => ['horizontal' => 'center']],
            5 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFDCE6F1']],
            ],
        ];
    }

    public function title(): string
    {
        return strtoupper(substr(ReportHelper::monthName($this->bulan), 0, 3));
    }
}
