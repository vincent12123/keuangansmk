<?php

namespace App\Exports;

use App\Services\Reports\SppArrearsReportService;
use App\Support\ReportHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TunggakanSppExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
        protected int|string|null $jurusanId = null,
        protected int|string|null $kelasId = null,
    ) {}

    public function array(): array
    {
        $report = app(SppArrearsReportService::class)->build(
            $this->bulan,
            $this->tahun,
            $this->jurusanId,
            $this->kelasId,
        );

        $rows = [
            ['SMK KARYA BANGSA SINTANG'],
            ['REKAP TUNGGAKAN SPP'],
            ['BULAN : ' . ReportHelper::monthNameUpper($this->bulan) . ' ' . $this->tahun],
            [''],
            ['Total Siswa Aktif', $report['total_siswa_aktif']],
            ['Sudah Bayar', $report['total_sudah_bayar']],
            ['Belum Bayar', $report['total_belum_bayar']],
            ['Total Nominal Masuk', $report['total_nominal_masuk']],
            ['Total Nominal Tunggakan', $report['total_nominal_tunggakan']],
            [''],
            ['Statistik Per Jurusan'],
            ['Jurusan', 'Kode', 'Total', 'Sudah Bayar', 'Belum Bayar', '% Lunas'],
        ];

        foreach ($report['stats_per_jurusan'] as $row) {
            $rows[] = [
                $row['jurusan'],
                $row['kode'],
                $row['total'],
                $row['sudah_bayar'],
                $row['belum_bayar'],
                $row['persen_lunas'],
            ];
        }

        $rows[] = [''];
        $rows[] = ['Detail Tunggakan'];
        $rows[] = ['No', 'NIS', 'Nama', 'Kelas', 'Jurusan', 'Nominal SPP', 'HP Wali'];

        foreach ($report['rows'] as $index => $row) {
            $rows[] = [
                $index + 1,
                "'" . $row['nis'],
                $row['nama'],
                $row['kelas'],
                $row['jurusan'],
                $row['nominal_spp'],
                $row['no_hp_wali'] ?: '-',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('F:F')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            12 => ['font' => ['bold' => true]],
        ];
    }
}
