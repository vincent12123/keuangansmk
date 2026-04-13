<?php

namespace App\Exports;

use App\Models\JurnalKas;
use App\Services\Reports\SaldoKasService;
use App\Support\ReportHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JurnalKasExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected int $bulan,
        protected int $tahun,
    ) {}

    public function array(): array
    {
        $opening = app(SaldoKasService::class)->getOpeningBalance($this->bulan, $this->tahun);
        $saldoCash = (float) $opening['cash'];
        $saldoBank = (float) $opening['bank'];

        $rows = [
            ['SMK KARYA BANGSA SINTANG'],
            ['BUKU CASH DAN BANK'],
            ['BULAN : ' . ReportHelper::monthNameUpper($this->bulan) . ' ' . $this->tahun],
            [''],
            [
                'No. Kwitansi / Referensi',
                'Tanggal',
                'NIS',
                'Nama Siswa / Penyetor',
                'Kelas',
                'Kode Akun',
                'Uraian Transaksi',
                'Cash',
                'Bank',
                'Saldo Cash',
                'Saldo Bank',
            ],
        ];

        $records = JurnalKas::query()
            ->with(['kodeAkun', 'kelas'])
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            if ($record->jenis === 'masuk') {
                $saldoCash += (float) $record->cash;
                $saldoBank += (float) $record->bank;
            } else {
                $saldoCash -= (float) $record->cash;
                $saldoBank -= (float) $record->bank;
            }

            $rows[] = [
                $record->no_kwitansi ?? '-',
                $record->tanggal?->format('d/m/Y'),
                $record->nis ? "'" . $record->nis : '',
                $record->nama_penyetor ?? '',
                $record->kelas?->nama_kelas ?? '',
                $record->kodeAkun?->kode ?? '',
                $record->uraian,
                (float) $record->cash,
                (float) $record->bank,
                $saldoCash,
                $saldoBank,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');
        $sheet->getStyle('H:K')->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            3 => ['alignment' => ['horizontal' => 'center']],
            5 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'FF1F497D'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Jurnal ' . str_pad((string) $this->bulan, 2, '0', STR_PAD_LEFT) . '-' . $this->tahun;
    }
}
