# Phase 3 — Export & Cetak
## Keuangan SMK Karya Bangsa · Laravel 12 + Filament 4

> Phase ini mengubah semua laporan dari Phase 2 menjadi file yang bisa diunduh dan dicetak.
> Tidak ada logika hitung baru — semua data sudah tersedia, tinggal diformat ulang.

---

## Daftar Fitur Phase 3

| No | Fitur | Package | Output | Prioritas |
|----|-------|---------|--------|-----------|
| 1 | Export Jurnal Cash & Bank | `maatwebsite/excel` | `.xlsx` | Tinggi |
| 2 | Export Arus Kas Bulanan | `maatwebsite/excel` | `.xlsx` | Tinggi |
| 3 | Export Rekap Kas Kecil | `maatwebsite/excel` | `.xlsx` | Tinggi |
| 4 | Export Tunggakan SPP | `maatwebsite/excel` | `.xlsx` | Tinggi |
| 5 | Export Pivot Cash & Bank | `maatwebsite/excel` | `.xlsx` | Sedang |
| 6 | Export Pivot Kas Kecil | `maatwebsite/excel` | `.xlsx` | Sedang |
| 7 | Cetak Kwitansi Pembayaran | `barryvdh/laravel-dompdf` | `.pdf` | Tinggi |
| 8 | Cetak Laporan Arus Kas | `barryvdh/laravel-dompdf` | `.pdf` | Tinggi |
| 9 | Cetak Rekap Kas Kecil | `barryvdh/laravel-dompdf` | `.pdf` | Sedang |
| 10 | Import Siswa dari Excel Lama | `maatwebsite/excel` | database | Tinggi |
| 11 | Import Histori SPP dari Excel Lama | `maatwebsite/excel` | database | Sedang |

---

## Instalasi Package

```bash
# Export/Import Excel
composer require maatwebsite/excel

# Export PDF
composer require barryvdh/laravel-dompdf

# Publish config DomPDF (opsional)
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

Tambahkan ke `config/app.php` (jika belum auto-discovered):
```php
'aliases' => [
    'Excel' => Maatwebsite\Excel\Facades\Excel::class,
    'PDF'   => Barryvdh\DomPDF\Facade\Pdf::class,
]
```

---

## Struktur Folder Export & Import

```
app/
├── Exports/
│   ├── JurnalKasExport.php
│   ├── ArusKasBulananExport.php
│   ├── KasKecilExport.php
│   ├── TunggakanSppExport.php
│   ├── PivotCashBankExport.php
│   └── PivotKasKecilExport.php
├── Imports/
│   ├── SiswaImport.php
│   └── HistoriSppImport.php
└── Services/
    └── ExportPdfService.php   ← helper untuk semua PDF

resources/views/
└── pdf/
    ├── kwitansi.blade.php
    ├── arus-kas-bulanan.blade.php
    ├── rekap-kas-kecil.blade.php
    └── partials/
        ├── kop-surat.blade.php
        └── tanda-tangan.blade.php
```

---

## BAGIAN A — EXPORT EXCEL

### Konsep Dasar Maatwebsite/Excel

Setiap export adalah satu class PHP. Class ini mengimplementasi beberapa `Concern` (interface) sesuai kebutuhan:

| Concern | Fungsi |
|---------|--------|
| `FromQuery` | Query Eloquent sebagai sumber data |
| `FromCollection` | Collection sebagai sumber data |
| `WithHeadings` | Baris header kolom |
| `WithMapping` | Transform tiap baris data |
| `WithStyles` | Styling cell (bold, warna, border) |
| `WithColumnWidths` | Lebar kolom |
| `WithTitle` | Nama sheet |
| `ShouldAutoSize` | Auto-resize kolom |
| `WithMultipleSheets` | Multiple sheet dalam satu file |
| `WithEvents` | Event hook untuk operasi lanjut |

Cara download dari Filament Action:
```php
return Excel::download(new JurnalKasExport($bulan, $tahun), 'jurnal-kas.xlsx');
```

Cara download dari controller:
```php
return Excel::download(new JurnalKasExport($bulan, $tahun), 'jurnal-kas.xlsx');
// Atau store ke disk:
Excel::store(new JurnalKasExport($bulan, $tahun), 'exports/jurnal-kas.xlsx');
```

---

### Export 1 — Jurnal Cash & Bank

**Tujuan:** Menghasilkan file Excel yang identik dengan sheet `PENERIMAAN` di file Excel lama.

**Trigger:** Action di `JurnalKasResource` → form pilih bulan & tahun → download.

**Kolom output (urutan persis seperti Excel lama):**

| Kolom | Sumber | Format |
|-------|--------|--------|
| No. Kwitansi / Referensi | `jurnal_kas.no_kwitansi` | Text |
| Tanggal | `jurnal_kas.tanggal` | dd/mm/yyyy |
| NIS | `jurnal_kas.nis` | Text (jaga leading zero) |
| Nama Siswa / Penyetor | `jurnal_kas.nama_penyetor` | Text |
| Kelas | `kelas.nama_kelas` | Text |
| Kode Akun | `kode_akun.kode` | Text |
| Uraian Transaksi | `jurnal_kas.uraian` | Text |
| Cash | `jurnal_kas.cash` | Rp #,##0 |
| Bank | `jurnal_kas.bank` | Rp #,##0 |
| Saldo Cash | dihitung running | Rp #,##0 |
| Saldo Bank | dihitung running | Rp #,##0 |

> **Catatan penting:** Kolom Saldo Cash dan Saldo Bank adalah **running balance** — dihitung secara kumulatif per baris, bukan per transaksi. Saldo Cash[n] = Saldo Cash[n-1] ± Cash[n] tergantung jenis transaksi.

```php
<?php

namespace App\Exports;

use App\Models\JurnalKas;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class JurnalKasExport implements
    FromQuery, WithHeadings, WithMapping,
    WithStyles, WithTitle
{
    private float $saldoCash = 0;
    private float $saldoBank = 0;

    public function __construct(
        private int $bulan,
        private int $tahun
    ) {}

    public function query()
    {
        return JurnalKas::with(['kodeAkun', 'kelas'])
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->orderBy('tanggal')
            ->orderBy('id');
    }

    public function headings(): array
    {
        $namaBulan = [
            1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL',
            5=>'MEI', 6=>'JUNI', 7=>'JULI', 8=>'AGUSTUS',
            9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER',
        ][$this->bulan];

        return [
            // Baris 1: judul
            ["SMK KARYA BANGSA SINTANG", '', '', '', '', '', '', '', '', '', ''],
            ["BUKU CASH DAN BANK", '', '', '', '', '', '', '', '', '', ''],
            ["BULAN : {$namaBulan} {$this->tahun}", '', '', '', '', '', '', '', '', '', ''],
            [''], // baris kosong
            // Baris 5: header kolom
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
    }

    public function map($row): array
    {
        // Hitung running balance
        if ($row->jenis === 'masuk') {
            $this->saldoCash += $row->cash;
            $this->saldoBank += $row->bank;
        } else {
            $this->saldoCash -= $row->cash;
            $this->saldoBank -= $row->bank;
        }

        return [
            $row->no_kwitansi ?? '-',
            $row->tanggal->format('d/m/Y'),
            "'" . ($row->nis ?? ''),     // prefix ' agar NIS tidak jadi angka
            $row->nama_penyetor ?? '',
            $row->kelas?->nama_kelas ?? '',
            $row->kodeAkun?->kode ?? '',
            $row->uraian,
            $row->cash > 0 ? $row->cash : '',
            $row->bank > 0 ? $row->bank : '',
            $this->saldoCash,
            $this->saldoBank,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cell judul
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');

        return [
            // Judul bold & center
            1 => ['font' => ['bold' => true, 'size' => 14],
                  'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12],
                  'alignment' => ['horizontal' => 'center']],
            3 => ['alignment' => ['horizontal' => 'center']],

            // Header kolom (baris 5): bold + background biru muda
            5 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1F497D']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            ],

            // Kolom H, I, J, K — format currency Rp
            'H' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'I' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'J' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'K' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
        ];
    }

    public function title(): string
    {
        $bulan = str_pad($this->bulan, 2, '0', STR_PAD_LEFT);
        return "Jurnal {$bulan}-{$this->tahun}";
    }
}
```

**Cara menambahkan Action di JurnalKasResource:**

```php
// Di dalam method table(), tambahkan di headerActions:
Tables\Actions\Action::make('export_excel')
    ->label('Export Excel')
    ->icon('heroicon-o-arrow-down-tray')
    ->color('success')
    ->form([
        Forms\Components\Select::make('bulan')
            ->label('Bulan')
            ->options([
                1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
                5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
                9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember',
            ])
            ->default(now()->month)
            ->required(),
        Forms\Components\Select::make('tahun')
            ->label('Tahun')
            ->options(fn() => array_combine(
                range(now()->year, now()->year - 2),
                range(now()->year, now()->year - 2)
            ))
            ->default(now()->year)
            ->required(),
    ])
    ->action(function (array $data) {
        $namaBulan = [
            1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
            5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
            9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember',
        ][$data['bulan']];

        return Excel::download(
            new JurnalKasExport($data['bulan'], $data['tahun']),
            "Jurnal-CashBank-{$namaBulan}-{$data['tahun']}.xlsx"
        );
    }),
```

---

### Export 2 — Arus Kas Bulanan

**Tujuan:** Menghasilkan file Excel yang identik dengan sheet JAN/FEB/dll di file Arus Kas lama. Format A-B-C-D.

**Menggunakan `WithMultipleSheets`** karena bisa export beberapa bulan sekaligus dalam satu file.

```php
<?php

namespace App\Exports;

use App\Services\ArusKasBulananService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ArusKasExport implements WithMultipleSheets
{
    public function __construct(
        private int $bulan,
        private int $tahun,
        private bool $semuaBulan = false // jika true, export Jan-Des dalam satu file
    ) {}

    public function sheets(): array
    {
        if ($this->semuaBulan) {
            $sheets = [];
            for ($b = 1; $b <= 12; $b++) {
                $data = app(ArusKasBulananService::class)->generate($b, $this->tahun);
                if ($data['total_penerimaan'] > 0 || $data['total_pengeluaran'] > 0) {
                    $sheets[] = new ArusKasBulananSheet($b, $this->tahun, $data);
                }
            }
            return $sheets;
        }

        $data = app(ArusKasBulananService::class)->generate($this->bulan, $this->tahun);
        return [new ArusKasBulananSheet($this->bulan, $this->tahun, $data)];
    }
}
```

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArusKasBulananSheet implements WithTitle, WithStyles, WithEvents
{
    private static array $NAMA_BULAN = [
        1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL',
        5=>'MEI', 6=>'JUNI', 7=>'JULI', 8=>'AGUSTUS',
        9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER',
    ];

    public function __construct(
        private int $bulan,
        private int $tahun,
        private array $data   // hasil dari ArusKasBulananService::generate()
    ) {}

    public function title(): string
    {
        return substr(self::$NAMA_BULAN[$this->bulan], 0, 3); // JAN, FEB, dll
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->buildSheet($sheet);
            },
        ];
    }

    private function buildSheet(Worksheet $sheet): void
    {
        $namaBulan = self::$NAMA_BULAN[$this->bulan];

        // ── Header ────────────────────────────────────────
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'SMK KARYA BANGSA SINTANG');
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'LAPORAN PENERIMAAN DAN PENGELUARAN');
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', "BULAN : {$namaBulan} {$this->tahun}");

        $baris = 5;

        // ── A. Saldo Awal ─────────────────────────────────
        $saldoAwal = ($this->data['saldo_awal']->saldo_awal_cash ?? 0)
                   + ($this->data['saldo_awal']->saldo_awal_bank ?? 0);

        $sheet->setCellValue("B{$baris}", 'A');
        $sheet->setCellValue("C{$baris}", 'SALDO AWAL OPERASIONAL');
        $sheet->setCellValue("F{$baris}", $saldoAwal);
        $baris++;

        // ── B. Penerimaan ─────────────────────────────────
        $baris++;
        $sheet->setCellValue("B{$baris}", 'B');
        $sheet->setCellValue("C{$baris}", 'PENERIMAAN');
        $baris++;

        // B1. Penerimaan Pendidikan
        $sheet->setCellValue("C{$baris}", '1');
        $sheet->setCellValue("D{$baris}", 'Penerimaan Pendidikan');
        $baris++;

        $totalPendidikan = 0;
        $kodesPendidikan = [
            '4.01.01.00' => 'Jurusan RPL',
            '4.01.02.00' => 'Jurusan TBSM',
            '4.01.03.00' => 'Jurusan Perhotelan',
            '4.01.04.00' => 'Kursus',
            '4.01.05.00' => 'Seragam',
            '4.01.06.00' => 'Kegiatan',
            '4.01.07.00' => 'Pendapatan Bunga / Bagi Hasil Bank',
            '4.01.08.00' => 'Penjualan Buku Tulis',
            '4.01.09.00' => 'Penjualan Logo Sekolah',
            '4.01.10.00' => 'Penjualan Buku Paket',
            '4.01.11.00' => 'Pendapatan Sewa Ruangan',
        ];

        foreach ($kodesPendidikan as $kode => $label) {
            $nominal = $this->getNominalByKode($kode, 'penerimaan');
            $sheet->setCellValue("D{$baris}", $nominal !== null ? ($nominal + 1 - 1) : 1); // nomor urut
            $sheet->setCellValue("E{$baris}", $label);
            $sheet->setCellValue("F{$baris}", $nominal ?? 0); // kolom detail
            $totalPendidikan += $nominal ?? 0;
            $baris++;
        }

        // Subtotal Pendidikan
        $sheet->setCellValue("E{$baris}", 'Total Penerimaan Pendidikan');
        $sheet->setCellValue("G{$baris}", $totalPendidikan); // kolom total
        $baris += 2;

        // B2. Penerimaan Non Pendidikan ... (pola sama)
        // B3. Pinjaman ... (pola sama)

        // Total Seluruh Penerimaan
        $sheet->setCellValue("E{$baris}", 'Total Seluruh Penerimaan');
        $sheet->setCellValue("G{$baris}", $this->data['total_penerimaan']);
        $baris += 2;

        // ── C. Pengeluaran ────────────────────────────────
        // (pola sama dengan penerimaan, iterate kode 5.xx)
        // ...

        // ── D. Saldo Akhir ────────────────────────────────
        $baris += 2;
        $sheet->setCellValue("B{$baris}", 'D');
        $sheet->setCellValue("C{$baris}", 'SALDO AKHIR OPERASIONAL');
        $sheet->setCellValue("G{$baris}", $this->data['saldo_akhir']);
        $baris++;

        $sheet->setCellValue("D{$baris}", '1');
        $sheet->setCellValue("E{$baris}", 'Kas Kecil');
        $sheet->setCellValue("F{$baris}", $this->data['saldo_kas_kecil']);
        $baris++;

        $sheet->setCellValue("D{$baris}", '2');
        $sheet->setCellValue("E{$baris}", 'Kas Besar');
        $sheet->setCellValue("F{$baris}", $this->data['saldo_kas_besar']);
        $baris++;

        $sheet->setCellValue("E{$baris}", 'Jumlah Saldo Kas');
        $sheet->setCellValue("F{$baris}", $this->data['saldo_akhir']);

        // Tanda tangan
        $this->addTandaTangan($sheet, $baris + 3);
    }

    private function getNominalByKode(string $kode, string $sumber): ?float
    {
        // Cari kode_akun_id berdasarkan kode string
        $item = $this->data[$sumber]->first(fn($i) => $i->kodeAkun?->kode === $kode);
        return $item?->total;
    }

    private function addTandaTangan(Worksheet $sheet, int $baris): void
    {
        $sheet->setCellValue("B{$baris}", 'Mengetahui,');
        $sheet->setCellValue("F{$baris}", 'Sintang, ' . now()->format('d F Y'));
        $baris++;
        $sheet->setCellValue("B{$baris}", 'Kepala Sekolah');
        $sheet->setCellValue("F{$baris}", 'Bendahara');
        $baris += 4; // ruang tanda tangan
        $sheet->setCellValue("B{$baris}", '(______________________)');
        $sheet->setCellValue("F{$baris}", '(______________________)');
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            3 => ['alignment' => ['horizontal' => 'center']],
            'F' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'G' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
        ];
    }
}
```

---

### Export 3 — Rekap Kas Kecil

**Tujuan:** Menghasilkan file Excel identik dengan sheet bulan di file Kas Kecil lama.

**Kolom output:**

| Kolom | Sumber | Format |
|-------|--------|--------|
| No. | urut | Integer |
| Tanggal | `kas_kecil.tanggal` | dd/mm/yyyy |
| Kode Akun | `kode_akun.kode` | Text |
| Uraian | `kas_kecil.uraian` | Text |
| Ref | `kas_kecil.no_ref` | Text |
| Debet | `pengisian_kas_kecil.nominal` | Rp #,##0 |
| Kredit | `kas_kecil.nominal` | Rp #,##0 |
| Saldo | dihitung running | Rp #,##0 |

> **Aturan baris khusus:** Sebelum transaksi pertama, ada 2 baris:
> - Baris "Saldo Awal" → nominal dari pengisian awal bulan atau sisa bulan lalu
> - Baris "Pengisian Kas Kecil" → total pengisian bulan ini
>
> Di akhir: baris **Total Pengeluaran** = SUM kolom Kredit

```php
<?php

namespace App\Exports;

use App\Models\KasKecil;
use App\Models\PengisianKasKecil;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KasKecilExport implements WithTitle, WithStyles, WithEvents
{
    public function __construct(
        private int $bulan,
        private int $tahun
    ) {}

    public function title(): string
    {
        return [
            1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL',
            5=>'MEI', 6=>'JUNI', 7=>'JULI', 8=>'AGUSTUS',
            9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER',
        ][$this->bulan];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->buildSheet($sheet);
            },
        ];
    }

    private function buildSheet(Worksheet $sheet): void
    {
        $namaBulan = $this->title();

        // Header
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'SMK KARYA BANGSA SINTANG');
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'BUKU KAS KECIL');
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', "BULAN : {$namaBulan} {$this->tahun}");

        // Header kolom
        $baris = 5;
        $headers = ['No.', 'Tanggal', 'Kode Acc.', 'Uraian', 'Ref', 'Debet', 'Kredit', 'Saldo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, $baris, $h);
        }
        $baris++;

        // Baris "Saldo Awal"
        $sheet->setCellValue("D{$baris}", 'Saldo Awal');
        $baris++;

        // Baris "Pengisian Kas Kecil"
        $totalPengisian = PengisianKasKecil::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->sum('nominal');
        $sheet->setCellValue("D{$baris}", 'Pengisian Kas Kecil');
        $sheet->setCellValue("F{$baris}", $totalPengisian); // kolom Debet
        $baris++;

        // Transaksi
        $transaksi = KasKecil::with('kodeAkun')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $no = 1;
        $saldo = $totalPengisian;
        foreach ($transaksi as $t) {
            $saldo -= $t->nominal;
            $sheet->setCellValue("A{$baris}", $no++);
            $sheet->setCellValue("B{$baris}", $t->tanggal->format('d/m/Y'));
            $sheet->setCellValue("C{$baris}", $t->kodeAkun?->kode ?? '');
            $sheet->setCellValue("D{$baris}", $t->uraian);
            $sheet->setCellValue("E{$baris}", $t->no_ref);
            $sheet->setCellValue("G{$baris}", $t->nominal); // Kredit
            $sheet->setCellValue("H{$baris}", $saldo);      // Saldo
            $baris++;
        }

        // Baris kosong
        $baris++;

        // Total Pengeluaran
        $totalKeluar = $transaksi->sum('nominal');
        $sheet->mergeCells("A{$baris}:E{$baris}");
        $sheet->setCellValue("A{$baris}", 'Total Pengeluaran');
        $sheet->setCellValue("F{$baris}", 0);             // Debet total = 0
        $sheet->setCellValue("G{$baris}", $totalKeluar);  // Kredit total

        // Style baris Total
        $sheet->getStyle("A{$baris}:H{$baris}")->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'top' => ['borderStyle' => 'thin'],
                'bottom' => ['borderStyle' => 'double'],
            ],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            5 => ['font' => ['bold' => true],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1F497D']],
                  'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true],
                  'alignment' => ['horizontal' => 'center']],
            'F' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'G' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'H' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
        ];
    }
}
```

---

### Export 4 — Tunggakan SPP

**Tujuan:** Daftar siswa belum bayar SPP per bulan, siap dicetak untuk rapat atau dikirim ke wali kelas.

```php
<?php

namespace App\Exports;

use App\Models\Siswa;
use App\Models\KartuSpp;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TunggakanSppExport implements
    FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, WithColumnWidths
{
    public function __construct(
        private int $bulan,
        private int $tahun
    ) {}

    public function collection()
    {
        $sudahBayar = KartuSpp::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->pluck('nis');

        return Siswa::aktif()
            ->whereNotIn('nis', $sudahBayar)
            ->with(['kelas', 'jurusan'])
            ->orderBy('jurusan_id')
            ->orderBy('kelas_id')
            ->orderBy('nama')
            ->get();
    }

    public function headings(): array
    {
        $namaBulan = [
            1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
            5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
            9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember',
        ][$this->bulan];

        return [
            ["REKAP TUNGGAKAN SPP — {$namaBulan} {$this->tahun}", '', '', '', '', '', ''],
            ["SMK KARYA BANGSA SINTANG", '', '', '', '', '', ''],
            [''],
            ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Jurusan', 'Nominal SPP', 'No HP Wali'],
        ];
    }

    public function map($siswa): array
    {
        static $no = 0;
        $no++;
        return [
            $no,
            "'" . $siswa->nis,
            $siswa->nama,
            $siswa->kelas?->nama_kelas ?? '',
            $siswa->jurusan?->kode ?? '',
            $siswa->nominal_spp,
            $siswa->no_hp_wali ?? '-',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 14, 'C' => 35, 'D' => 15, 'E' => 12, 'F' => 16, 'G' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        return [
            1 => ['font' => ['bold' => true, 'size' => 13], 'alignment' => ['horizontal' => 'center']],
            2 => ['alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFC00000']]],
            'F' => ['numberFormat' => ['formatCode' => '"Rp "#,##0']],
        ];
    }

    public function title(): string
    {
        return 'Tunggakan SPP';
    }
}
```

---

### Export 5 & 6 — Pivot Cash & Bank dan Pivot Kas Kecil

Struktur identik, berbeda sumber data. Gunakan class yang sama dengan parameter berbeda:

```php
<?php

namespace App\Exports;

use App\Models\JurnalKas;
use App\Models\KasKecil;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PivotExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    // tipe: 'cash_bank' | 'kas_kecil'
    public function __construct(
        private int $bulan,
        private int $tahun,
        private string $tipe = 'cash_bank'
    ) {}

    public function collection()
    {
        if ($this->tipe === 'cash_bank') {
            return JurnalKas::with('kodeAkun')
                ->where('bulan', $this->bulan)
                ->where('tahun', $this->tahun)
                ->selectRaw('kode_akun_id, SUM(cash) as total_cash, SUM(bank) as total_bank, SUM(cash+bank) as total')
                ->groupBy('kode_akun_id')
                ->orderBy('kode_akun_id')
                ->get();
        }

        // kas_kecil
        return KasKecil::with('kodeAkun')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->selectRaw('kode_akun_id, SUM(nominal) as total')
            ->groupBy('kode_akun_id')
            ->orderBy('kode_akun_id')
            ->get();
    }

    public function headings(): array
    {
        if ($this->tipe === 'cash_bank') {
            return [['Kode Akun', 'Nama Akun', 'Cash', 'Bank', 'Total']];
        }
        return [['Kode Akun', 'Nama Akun', 'Total Kredit']];
    }

    public function map($row): array
    {
        if ($this->tipe === 'cash_bank') {
            return [
                $row->kodeAkun?->kode ?? '',
                $row->kodeAkun?->nama ?? '',
                $row->total_cash,
                $row->total_bank,
                $row->total,
            ];
        }
        return [
            $row->kodeAkun?->kode ?? '',
            $row->kodeAkun?->nama ?? '',
            $row->total,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1F497D']],
                  'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true]],
            'C' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'D' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
            'E' => ['numberFormat' => ['formatCode' => '#,##0;(#,##0);"-"']],
        ];
    }

    public function title(): string
    {
        return $this->tipe === 'cash_bank' ? 'Pivot Cash-Bank' : 'Pivot Kas Kecil';
    }
}
```

---

## BAGIAN B — EXPORT PDF (DomPDF)

### Konsep Dasar

DomPDF mengkonversi HTML/CSS menjadi PDF. Alurnya:

```
Blade View (HTML + CSS) → DomPDF::loadView() → file PDF
```

**Tips penting untuk DomPDF:**
1. Gunakan CSS `inline` atau `<style>` di dalam HTML — external CSS tidak di-load
2. Hindari Flexbox dan CSS Grid — DomPDF tidak mendukung penuh
3. Gunakan tabel HTML untuk layout grid
4. Font default adalah DejaVu — untuk karakter khusus, embed font
5. Ukuran kertas diset di konfigurasi atau via opsi

### Konfigurasi DomPDF (`config/dompdf.php`)

```php
return [
    'defines' => [
        'font_dir'                 => storage_path('fonts/'),
        'font_cache'               => storage_path('fonts/'),
        'temp_dir'                 => sys_get_temp_dir(),
        'chroot'                   => realpath(base_path()),
        'allowed_remote_hosts'     => null,
        'log_output_file'          => null,
        'font_height_ratio'        => 1.1,
        'enable_php'               => false,
        'enable_javascript'        => true,
        'enable_remote'            => false,
        'default_media_type'       => 'screen',
        'default_paper_size'       => 'a4',
        'default_paper_orientation' => 'portrait',
        'default_font'             => 'DejaVu Sans',
        'dpi'                      => 96,
        'enable_html5_parser'      => true,
        'enable_css_float'         => true,
    ],
];
```

### Helper Service

```php
<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ExportPdfService
{
    /**
     * Download PDF
     */
    public function download(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', false);

        return $pdf->download($filename);
    }

    /**
     * Stream PDF (buka di browser langsung)
     */
    public function stream(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation);

        return $pdf->stream($filename);
    }

    /**
     * Simpan ke storage
     */
    public function store(string $view, array $data, string $path, string $orientation = 'portrait'): string
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation);

        $pdf->save(storage_path("app/{$path}"));
        return $path;
    }
}
```

---

### PDF 1 — Kwitansi Pembayaran

**Format:** A5 (148mm × 210mm) atau setengah A4 landscape.
**Trigger:** Action per baris di `JurnalKasResource` → klik ikon print di baris transaksi.
**Isi:** Per transaksi — cocok untuk dicetak dan diberikan kepada siswa sebagai bukti bayar.

**Blade view (`resources/views/pdf/kwitansi.blade.php`):**

```blade
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #000; }

  /* Kop surat */
  .kop { display: table; width: 100%; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 10px; }
  .kop-logo { display: table-cell; width: 70px; vertical-align: middle; }
  .kop-logo img { width: 60px; height: 60px; }
  .kop-teks { display: table-cell; vertical-align: middle; padding-left: 10px; }
  .kop-nama { font-size: 16px; font-weight: bold; }
  .kop-alamat { font-size: 10px; color: #333; }

  /* Judul kwitansi */
  .judul { text-align: center; font-size: 14px; font-weight: bold;
           margin: 10px 0; border: 1px solid #000; padding: 5px;
           letter-spacing: 3px; }

  /* Nomor kwitansi */
  .nomor { text-align: right; font-size: 11px; margin-bottom: 12px; }

  /* Detail pembayaran */
  .detail { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  .detail td { padding: 4px 6px; vertical-align: top; }
  .detail .label { width: 120px; }
  .detail .titik { width: 10px; }
  .detail .nilai { font-weight: bold; }

  /* Box nominal */
  .nominal-box { border: 1px solid #000; padding: 8px 12px; text-align: center;
                 font-size: 14px; font-weight: bold; margin: 10px 0;
                 background: #f9f9f9; }

  /* Tanda tangan */
  .ttd { width: 100%; margin-top: 20px; }
  .ttd td { width: 50%; text-align: center; vertical-align: top; }
  .ttd .nama-ttd { font-weight: bold; border-top: 1px solid #000;
                   padding-top: 4px; margin-top: 50px; display: inline-block;
                   min-width: 120px; }
</style>
</head>
<body>

{{-- Kop Surat --}}
<div class="kop">
  <div class="kop-logo">
    {{-- <img src="{{ public_path('images/logo-smk.png') }}"> --}}
    {{-- Jika logo tidak ada, kosongi --}}
  </div>
  <div class="kop-teks">
    <div class="kop-nama">SMK KARYA BANGSA SINTANG</div>
    <div class="kop-alamat">
      Jl. [Alamat Sekolah], Sintang, Kalimantan Barat<br>
      Telp: [No Telp] | Email: [Email Sekolah]
    </div>
  </div>
</div>

{{-- Judul --}}
<div class="judul">KWITANSI PEMBAYARAN</div>

{{-- Nomor Kwitansi --}}
<div class="nomor">No: <strong>{{ $transaksi->no_kwitansi ?? '-' }}</strong></div>

{{-- Detail --}}
<table class="detail">
  <tr>
    <td class="label">Diterima dari</td>
    <td class="titik">:</td>
    <td class="nilai">{{ $transaksi->nama_penyetor }}</td>
  </tr>
  @if($transaksi->nis)
  <tr>
    <td class="label">NIS</td>
    <td class="titik">:</td>
    <td>{{ $transaksi->nis }}</td>
  </tr>
  <tr>
    <td class="label">Kelas</td>
    <td class="titik">:</td>
    <td>{{ $transaksi->kelas?->nama_kelas }}</td>
  </tr>
  @endif
  <tr>
    <td class="label">Tanggal</td>
    <td class="titik">:</td>
    <td>{{ $transaksi->tanggal->translatedFormat('d F Y') }}</td>
  </tr>
  <tr>
    <td class="label">Uraian</td>
    <td class="titik">:</td>
    <td class="nilai">{{ $transaksi->uraian }}</td>
  </tr>
  <tr>
    <td class="label">Kode Akun</td>
    <td class="titik">:</td>
    <td>{{ $transaksi->kodeAkun?->kode }} — {{ $transaksi->kodeAkun?->nama }}</td>
  </tr>
</table>

{{-- Nominal --}}
<div class="nominal-box">
  Sebesar: Rp {{ number_format($transaksi->cash + $transaksi->bank, 0, ',', '.') }}
  <br>
  <small style="font-weight:normal;font-size:11px;">
    {{ $terbilang }}
    {{-- Helper: terbilang(400000) → "Empat Ratus Ribu Rupiah" --}}
  </small>
</div>

@if($transaksi->cash > 0 && $transaksi->bank > 0)
<div style="font-size:10px;color:#555;margin-bottom:8px;">
  Cash: Rp {{ number_format($transaksi->cash, 0, ',', '.') }} |
  Transfer: Rp {{ number_format($transaksi->bank, 0, ',', '.') }}
</div>
@endif

{{-- Tanda Tangan --}}
<table class="ttd">
  <tr>
    <td>
      Penyetor,<br>
      <span class="nama-ttd">{{ $transaksi->nama_penyetor }}</span>
    </td>
    <td>
      Sintang, {{ $transaksi->tanggal->translatedFormat('d F Y') }}<br>
      Bendahara,<br>
      <span class="nama-ttd">{{ $namaBendahara }}</span>
    </td>
  </tr>
</table>

</body>
</html>
```

**Action di JurnalKasResource (cetak kwitansi per baris):**

```php
Tables\Actions\Action::make('cetak_kwitansi')
    ->label('Kwitansi')
    ->icon('heroicon-o-printer')
    ->color('info')
    ->action(function (JurnalKas $record) {
        $terbilang = app(TerbilangService::class)
            ->convert($record->cash + $record->bank);

        return app(ExportPdfService::class)->stream(
            'pdf.kwitansi',
            [
                'transaksi'     => $record->load(['kodeAkun', 'kelas']),
                'terbilang'     => $terbilang,
                'namaBendahara' => auth()->user()->name,
            ],
            "kwitansi-{$record->no_kwitansi}.pdf",
            'portrait'
        );
    }),
```

---

### PDF 2 — Laporan Arus Kas Bulanan

**Format:** A4 portrait.
**Trigger:** Action di halaman Laporan Arus Kas (Phase 2 Page) → klik "Cetak PDF".
**Isi:** Format A-B-C-D lengkap + tanda tangan kepala sekolah dan bendahara.

**Blade view (`resources/views/pdf/arus-kas-bulanan.blade.php`):**

```blade
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 20px; }

  @include('pdf.partials.kop-surat')

  h2 { text-align: center; font-size: 13px; margin: 8px 0 4px; }
  h3 { text-align: center; font-size: 11px; margin: 0 0 12px; font-weight: normal; }

  table { width: 100%; border-collapse: collapse; }
  .tbl-laporan td { padding: 3px 6px; }
  .tbl-laporan .kode { width: 80px; font-family: monospace; }
  .tbl-laporan .label { width: 250px; }
  .tbl-laporan .nominal { text-align: right; width: 100px; }
  .tbl-laporan .total { text-align: right; width: 110px; }

  .section-head { font-weight: bold; background: #dce6f1; padding: 4px 6px; }
  .sub-head { font-weight: bold; padding: 3px 6px; padding-left: 16px; }
  .item-row td { padding: 2px 6px; padding-left: 24px; }
  .subtotal td { font-weight: bold; border-top: 1px solid #aaa; }
  .grandtotal td { font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; background: #f2f2f2; }

  .ttd-section { width: 100%; margin-top: 30px; }
  .ttd-section td { width: 50%; text-align: center; padding-top: 10px; }
  .ttd-section .garis-ttd { border-top: 1px solid #000; display: inline-block; min-width: 140px; margin-top: 50px; }
</style>
</head>
<body>

@include('pdf.partials.kop-surat')

<h2>LAPORAN PENERIMAAN DAN PENGELUARAN</h2>
<h3>BULAN : {{ strtoupper($namaBulan) }} {{ $tahun }}</h3>

<table class="tbl-laporan">

  {{-- A. Saldo Awal --}}
  <tr>
    <td colspan="2" class="section-head">A &nbsp;&nbsp; SALDO AWAL OPERASIONAL</td>
    <td class="total">{{ number_format($data['saldo_awal']->saldo_awal_cash + $data['saldo_awal']->saldo_awal_bank, 0, ',', '.') }}</td>
  </tr>

  {{-- B. Penerimaan --}}
  <tr><td colspan="3" style="height:8px"></td></tr>
  <tr><td colspan="3" class="section-head">B &nbsp;&nbsp; PENERIMAAN</td></tr>

  {{-- B1. Pendidikan --}}
  <tr><td colspan="3" class="sub-head">&nbsp;&nbsp; 1 &nbsp; Penerimaan Pendidikan</td></tr>
  @foreach($penerimaan_pendidikan as $item)
  <tr class="item-row">
    <td class="kode">{{ $item->kodeAkun->kode }}</td>
    <td class="label">{{ $item->kodeAkun->nama }}</td>
    <td class="nominal">{{ number_format($item->total, 0, ',', '.') }}</td>
  </tr>
  @endforeach
  <tr class="subtotal">
    <td colspan="2" class="label" style="padding-left:16px">Total Penerimaan Pendidikan</td>
    <td class="total">{{ number_format($total_pendidikan, 0, ',', '.') }}</td>
  </tr>

  {{-- B2. Non Pendidikan --}}
  <tr><td colspan="3" class="sub-head">&nbsp;&nbsp; 2 &nbsp; Penerimaan Non Pendidikan</td></tr>
  @foreach($penerimaan_non_pendidikan as $item)
  <tr class="item-row">
    <td class="kode">{{ $item->kodeAkun->kode }}</td>
    <td class="label">{{ $item->kodeAkun->nama }}</td>
    <td class="nominal">{{ number_format($item->total, 0, ',', '.') }}</td>
  </tr>
  @endforeach
  <tr class="subtotal">
    <td colspan="2" class="label" style="padding-left:16px">Total Penerimaan Non Pendidikan</td>
    <td class="total">{{ number_format($total_non_pendidikan, 0, ',', '.') }}</td>
  </tr>

  <tr class="grandtotal">
    <td colspan="2">TOTAL SELURUH PENERIMAAN</td>
    <td class="total">{{ number_format($data['total_penerimaan'], 0, ',', '.') }}</td>
  </tr>

  {{-- C. Pengeluaran --}}
  <tr><td colspan="3" style="height:8px"></td></tr>
  <tr><td colspan="3" class="section-head">C &nbsp;&nbsp; PENGELUARAN</td></tr>

  @foreach($kelompok_pengeluaran as $kelompok)
  <tr><td colspan="3" class="sub-head">&nbsp;&nbsp; {{ $loop->iteration }} &nbsp; {{ $kelompok['nama'] }}</td></tr>
  @foreach($kelompok['items'] as $item)
  <tr class="item-row">
    <td class="kode">{{ $item['kode'] }}</td>
    <td class="label">{{ $item['nama'] }}</td>
    <td class="nominal">{{ number_format($item['total'], 0, ',', '.') }}</td>
  </tr>
  @endforeach
  @endforeach

  <tr class="grandtotal">
    <td colspan="2">JUMLAH PENGELUARAN</td>
    <td class="total">{{ number_format($data['total_pengeluaran'], 0, ',', '.') }}</td>
  </tr>

  {{-- Selisih --}}
  <tr>
    <td colspan="2" style="padding:4px 6px;font-weight:bold">SELISIH PENERIMAAN DENGAN PENGELUARAN</td>
    <td class="total" style="font-weight:bold;{{ $data['selisih'] < 0 ? 'color:red' : '' }}">
      {{ number_format($data['selisih'], 0, ',', '.') }}
    </td>
  </tr>

  {{-- D. Saldo Akhir --}}
  <tr><td colspan="3" style="height:8px"></td></tr>
  <tr>
    <td colspan="2" class="section-head">D &nbsp;&nbsp; SALDO AKHIR OPERASIONAL</td>
    <td class="total section-head">{{ number_format($data['saldo_akhir'], 0, ',', '.') }}</td>
  </tr>
  <tr class="item-row">
    <td></td><td class="label">Kas Kecil</td>
    <td class="nominal">{{ number_format($data['saldo_kas_kecil'], 0, ',', '.') }}</td>
  </tr>
  <tr class="item-row">
    <td></td><td class="label">Kas Besar</td>
    <td class="nominal">{{ number_format($data['saldo_kas_besar'], 0, ',', '.') }}</td>
  </tr>
  <tr class="subtotal">
    <td colspan="2" style="padding-left:16px">Jumlah Saldo Kas</td>
    <td class="total">{{ number_format($data['saldo_akhir'], 0, ',', '.') }}</td>
  </tr>

</table>

{{-- Tanda Tangan --}}
<table class="ttd-section">
  <tr>
    <td>
      Mengetahui,<br>Kepala Sekolah<br>
      <div class="garis-ttd">{{ $namaKepalaSekolah ?? '(______________________)' }}</div>
    </td>
    <td>
      Sintang, {{ now()->translatedFormat('d F Y') }}<br>Bendahara<br>
      <div class="garis-ttd">{{ $namaBendahara }}</div>
    </td>
  </tr>
</table>

</body>
</html>
```

---

### PDF 3 — Rekap Kas Kecil

**Format:** A4 portrait (jika kolom sedikit) atau A4 landscape (jika banyak kolom).
**Struktur:** Mirip sheet bulan di Excel Kas Kecil — No, Tanggal, Kode, Uraian, Ref, Kredit, Saldo.

```blade
{{-- resources/views/pdf/rekap-kas-kecil.blade.php --}}
{{-- Struktur mirip kwitansi, tapi berisi tabel transaksi --}}
{{-- Kolom: No | Tanggal | Kode Akun | Uraian | No Ref | Kredit | Saldo --}}
{{-- Footer: Total Pengeluaran + Tanda Tangan --}}
```

---

### Partial: Kop Surat (dipakai di semua PDF)

```blade
{{-- resources/views/pdf/partials/kop-surat.blade.php --}}
<table style="width:100%;border-bottom:2px solid #000;margin-bottom:12px;padding-bottom:8px;">
  <tr>
    <td style="width:70px;vertical-align:middle;">
      {{-- <img src="{{ public_path('images/logo-smk.png') }}" style="width:60px;height:60px;"> --}}
    </td>
    <td style="vertical-align:middle;padding-left:10px;">
      <div style="font-size:16px;font-weight:bold;">SMK KARYA BANGSA SINTANG</div>
      <div style="font-size:10px;color:#444;">
        Jl. [Alamat Sekolah], Sintang, Kalimantan Barat 78611<br>
        Telp: [No Telp] | Email: [Email] | Web: [Website]<br>
        NPSN: [NPSN] | Akreditasi: A
      </div>
    </td>
  </tr>
</table>
```

---

## BAGIAN C — IMPORT EXCEL

### Import 1 — Data Siswa dari File D1/D2

**Tujuan:** Migrasi data siswa dari file Excel lama ke database baru. Dilakukan sekali saat pertama deploy, bisa diulang untuk update data tahun ajaran baru.

**Format file Excel input (D1/D2):**

```
Kolom A: No
Kolom B: NIS
Kolom C: NAMA
Kolom D: KELAS   (contoh: "X RPL", "XI TBSM", "XII Perhotelan")
Kolom E+: Bulan-bulan (Juli, Agustus, ... Juni)
           Nilai: kosong = belum bayar, angka = sudah bayar sejumlah itu
```

```php
<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Jurusan;
use App\Models\KartuSpp;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;

class SiswaImport implements
    ToModel, WithHeadingRow, WithValidation,
    SkipsOnError, SkipsOnFailure
{
    use Importable;

    // Mapping header Excel → bulan (untuk kolom histori SPP)
    // Sesuaikan dengan format header di file D1/D2 aktual
    private array $bulanMapping = [
        'juli'     => ['bulan' => 7,  'tahun' => 2024],
        'agustus'  => ['bulan' => 8,  'tahun' => 2024],
        'september'=> ['bulan' => 9,  'tahun' => 2024],
        'oktober'  => ['bulan' => 10, 'tahun' => 2024],
        'november' => ['bulan' => 11, 'tahun' => 2024],
        'desember' => ['bulan' => 12, 'tahun' => 2024],
        'januari'  => ['bulan' => 1,  'tahun' => 2025],
        'februari' => ['bulan' => 2,  'tahun' => 2025],
        'maret'    => ['bulan' => 3,  'tahun' => 2025],
        'april'    => ['bulan' => 4,  'tahun' => 2025],
        'mei'      => ['bulan' => 5,  'tahun' => 2025],
        'juni'     => ['bulan' => 6,  'tahun' => 2025],
    ];

    private array $errors = [];
    private array $failures = [];

    public function model(array $row): ?Siswa
    {
        // Skip baris jika NIS kosong (bisa karena baris total atau header sub-jurusan)
        if (empty($row['nis'])) return null;

        // Parse kelas: "X RPL" → cari kelas_id
        $namaKelas = trim($row['kelas'] ?? '');
        $kelas = $this->resolveKelas($namaKelas);

        if (! $kelas) {
            $this->errors[] = "NIS {$row['nis']}: Kelas '{$namaKelas}' tidak ditemukan";
            return null;
        }

        // Upsert siswa
        $siswa = Siswa::updateOrCreate(
            ['nis' => (string) $row['nis']],
            [
                'nama'        => $row['nama'] ?? '',
                'kelas_id'    => $kelas->id,
                'jurusan_id'  => $kelas->jurusan_id,
                'angkatan'    => $this->parseAngkatan($namaKelas),
                'nominal_spp' => 400000,
                'status'      => 'aktif',
            ]
        );

        // Import histori pembayaran SPP dari kolom bulan
        $this->importHistoriSpp($siswa, $row);

        return null; // return null karena sudah handle sendiri dengan updateOrCreate
    }

    private function resolveKelas(string $namaKelas): ?Kelas
    {
        // Normalisasi: "X RPL" → tingkat=X, jurusan=RPL
        $namaKelas = strtoupper(trim($namaKelas));

        // Cari exact match dulu
        $kelas = Kelas::where('nama_kelas', $namaKelas)->first();
        if ($kelas) return $kelas;

        // Coba parse: "X RPL", "XI TBSM", "XII PERHOTELAN"
        if (preg_match('/^(X{1,3}I{0,3}|I{1,3}X?)\s+(.+)$/', $namaKelas, $m)) {
            $tingkat  = $m[1];
            $kodeJur  = strtoupper(trim($m[2]));

            // Map nama panjang ke kode
            $kodeMap = ['PERHOTELAN' => 'PHT', 'RPL' => 'RPL', 'TBSM' => 'TBSM'];
            $kodeJur = $kodeMap[$kodeJur] ?? $kodeJur;

            $jurusan = Jurusan::where('kode', $kodeJur)->first();
            if (! $jurusan) return null;

            return Kelas::where('jurusan_id', $jurusan->id)
                ->where('tingkat', $tingkat)
                ->first();
        }

        return null;
    }

    private function parseAngkatan(string $namaKelas): int
    {
        // X = masuk 2025, XI = masuk 2024, XII = masuk 2023
        // Sesuaikan dengan tahun ajaran berjalan
        $tahunIni = now()->year;
        if (str_starts_with(strtoupper($namaKelas), 'XII')) return $tahunIni - 3;
        if (str_starts_with(strtoupper($namaKelas), 'XI'))  return $tahunIni - 2;
        return $tahunIni - 1; // X
    }

    private function importHistoriSpp(Siswa $siswa, array $row): void
    {
        foreach ($this->bulanMapping as $header => $info) {
            // Cek apakah kolom ini ada di row dan ada nilainya
            if (! isset($row[$header]) || empty($row[$header])) continue;

            $nominal = is_numeric($row[$header]) ? (float) $row[$header] : null;
            if (! $nominal || $nominal <= 0) continue;

            // Insert ke kartu_spp sebagai data historis
            KartuSpp::updateOrCreate(
                [
                    'nis'   => $siswa->nis,
                    'bulan' => $info['bulan'],
                    'tahun' => $info['tahun'],
                ],
                [
                    'nominal'       => $nominal,
                    'tgl_bayar'     => now(), // tanggal import, bukan tanggal asli
                    'keterangan'    => 'Import dari Excel D1/D2 lama',
                    'jurnal_kas_id' => null,  // tidak ada link ke jurnal
                ]
            );
        }
    }

    public function rules(): array
    {
        return [
            'nis'  => 'nullable|string|max:20',
            'nama' => 'nullable|string|max:100',
        ];
    }

    public function onError(\Throwable $e): void
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures): void
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    public function getErrors(): array { return $this->errors; }
    public function getFailures(): array { return $this->failures; }
}
```

**Action Import di SiswaResource:**

```php
Tables\Actions\Action::make('import_siswa')
    ->label('Import dari Excel')
    ->icon('heroicon-o-arrow-up-tray')
    ->color('warning')
    ->visible(fn () => auth()->user()->isAdmin()) // hanya admin
    ->form([
        Forms\Components\FileUpload::make('file')
            ->label('File Excel (D1 atau D2)')
            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->required()
            ->maxSize(5120), // 5MB
        Forms\Components\Select::make('angkatan')
            ->label('Angkatan')
            ->options([
                2023 => '2023/2024',
                2024 => '2024/2025',
                2025 => '2025/2026',
            ])
            ->required()
            ->helperText('Angkatan siswa di file ini'),
    ])
    ->action(function (array $data) {
        $import = new SiswaImport();

        Excel::import($import, $data['file']);

        $errors = $import->getErrors();
        $failures = $import->getFailures();

        if (count($errors) > 0) {
            Notification::make()
                ->warning()
                ->title('Import selesai dengan ' . count($errors) . ' error')
                ->body(implode("\n", array_slice($errors, 0, 5)))
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Import berhasil')
                ->body('Data siswa berhasil diimpor dari Excel')
                ->send();
        }
    }),
```

---

## Helper: Fungsi Terbilang (Angka ke Kata)

Dipakai di kwitansi PDF. Buat sebagai Service:

```php
<?php

namespace App\Services;

class TerbilangService
{
    private array $satuan = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh',
        'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
        'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas',
    ];

    public function convert(float $angka): string
    {
        $angka = (int) abs($angka);
        if ($angka === 0) return 'Nol Rupiah';

        $hasil = $this->terbilang($angka);
        return ucfirst(trim($hasil)) . ' Rupiah';
    }

    private function terbilang(int $n): string
    {
        if ($n < 20) return $this->satuan[$n];
        if ($n < 100) return $this->satuan[(int)($n/10)*10 === 10 ? 10 : (int)($n/10)] .
            (in_array((int)($n/10), [1]) ? 'belas' : ' puluh') . ' ' . $this->satuan[$n % 10];
        if ($n < 200) return 'seratus ' . $this->terbilang($n - 100);
        if ($n < 1000) return $this->satuan[(int)($n/100)] . ' ratus ' . $this->terbilang($n % 100);
        if ($n < 2000) return 'seribu ' . $this->terbilang($n - 1000);
        if ($n < 1000000) return $this->terbilang((int)($n/1000)) . ' ribu ' . $this->terbilang($n % 1000);
        if ($n < 1000000000) return $this->terbilang((int)($n/1000000)) . ' juta ' . $this->terbilang($n % 1000000);
        return $this->terbilang((int)($n/1000000000)) . ' miliar ' . $this->terbilang($n % 1000000000);
    }
}
```

Daftarkan di `AppServiceProvider::register()`:
```php
$this->app->singleton(TerbilangService::class);
```

---

## Ringkasan Mapping Export → Filament Action

| Laporan | Resource / Page | Jenis Action | Trigger |
|---------|----------------|-------------|---------|
| Export Jurnal Cash & Bank | `JurnalKasResource` | `headerActions` | Form bulan + tahun |
| Export Arus Kas | `LaporanArusKas` (Page) | `headerActions` | Filter sudah aktif |
| Export Kas Kecil | `KasKecilResource` | `headerActions` | Form bulan + tahun |
| Export Tunggakan SPP | `LaporanTunggakanSpp` (Page) | `headerActions` | Filter sudah aktif |
| Export Pivot | `PivotCashBank` (Page) | `headerActions` | Filter sudah aktif |
| Cetak Kwitansi | `JurnalKasResource` | `actions` (per baris) | Per record |
| Cetak Arus Kas PDF | `LaporanArusKas` (Page) | `headerActions` | Filter sudah aktif |
| Cetak Rekap KK PDF | `PivotKasKecil` (Page) | `headerActions` | Filter sudah aktif |
| Import Siswa | `SiswaResource` | `headerActions` | Upload file |

---

## Aturan Akses Export / Import

| Fitur | Admin | Bendahara | Kepala Sekolah |
|-------|-------|-----------|---------------|
| Export semua laporan ke Excel | ✓ | ✓ | ✓ |
| Cetak kwitansi PDF | ✓ | ✓ | ✗ |
| Cetak laporan arus kas PDF | ✓ | ✓ | ✓ |
| Import data siswa dari Excel | ✓ | ✗ | ✗ |
| Import histori SPP dari Excel | ✓ | ✗ | ✗ |

Permission yang digunakan: `export_laporan` (untuk semua export), `create_siswa` (untuk import).
