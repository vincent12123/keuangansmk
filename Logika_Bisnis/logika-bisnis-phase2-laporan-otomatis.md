# Phase 2 — Laporan Otomatis
## Keuangan SMK Karya Bangsa · Laravel 12 + Filament 4

> Semua laporan di phase ini **di-generate otomatis** dari data yang sudah diinput di Phase 1.
> Tidak ada input manual baru. Semua dihitung real-time dari `jurnal_kas`, `kas_kecil`, dan `saldo_kas_bulanan`.

---

## Daftar Laporan

| No | Nama Laporan | Setara Excel Lama | Akses |
|----|-------------|-------------------|-------|
| 1 | Arus Kas Bulanan | Sheet JAN / FEB / MAR / ... | Admin, Bendahara, Kepala Sekolah |
| 2 | Dashboard Tahunan (Monitoring Anggaran) | Sheet DETAIL di Arus Kas | Admin, Bendahara, Kepala Sekolah |
| 3 | Pivot Rekap Cash & Bank | Sheet PIVOT di Buku Cash & Bank | Admin, Bendahara |
| 4 | Rekap Tunggakan SPP | Sheet D1/D2 (versi laporan) | Admin, Bendahara, Kepala Sekolah |
| 5 | Pivot Rekap Kas Kecil Bulanan | Sheet PIVOT 1–10 di Kas Kecil | Admin, Bendahara |

---

## Laporan 1 — Arus Kas Bulanan

### Tujuan
Menampilkan posisi keuangan lengkap setiap bulan: dari mana uang masuk, ke mana uang keluar, dan berapa saldo yang tersisa. Format ini yang dilaporkan ke kepala sekolah dan yayasan setiap bulan.

### Struktur Laporan (Format A–B–C–D)

```
A. SALDO AWAL OPERASIONAL
B. PENERIMAAN
   B1. Penerimaan Pendidikan
       - Jurusan RPL             (4.01.01.00)
       - Jurusan TBSM            (4.01.02.00)
       - Jurusan Perhotelan      (4.01.03.00)
       - Kursus                  (4.01.04.00)
       - Seragam                 (4.01.05.00)
       - Kegiatan                (4.01.06.00)
       - Buku Paket              (4.01.10.00)
       ─────────────────────────────────────
       JUMLAH PENERIMAAN PENDIDIKAN

   B2. Penerimaan Non Pendidikan
       - Pendaftaran/Biaya Gedung (4.02.01.00)
       - Sumbangan                (4.02.02.00)
       - Pendapatan Lain-Lain     (4.02.03.00)
       ─────────────────────────────────────
       JUMLAH PENERIMAAN NON PENDIDIKAN

   B3. Pinjaman
       - Pinjaman Bank            (4.03.01.00)
       - Pinjaman Pihak Ketiga    (4.03.02.00)
       ─────────────────────────────────────
       TOTAL PINJAMAN

   ═════════════════════════════════════════
   TOTAL SELURUH PENERIMAAN = B1 + B2 + B3

C. PENGELUARAN
   C1.  Gaji dan Tunjangan        (5.01.01.xx + 5.01.02.xx)
   C2.  Beban Pegawai Lainnya     (5.01.03.xx)
   C3.  Beban Operasional Kantor  (5.02.xx)
   C4.  Beban Pemasaran           (5.03.xx)
   C5.  Kontrak Pelayanan         (5.04.xx)
   C6.  Asuransi                  (5.05.xx)
   C7.  Pengadaan Fasilitas       (5.06.xx)
   C8.  Kegiatan Siswa            (5.07.xx)
   C9.  Kegiatan Sosial           (5.08.xx)
   C10. Perjalanan Dinas          (5.09.xx)
   C11. Pendidikan dan Latihan    (5.10.xx)
   C12. Biaya Lain-lain           (5.11.xx)
   ═════════════════════════════════════════
   JUMLAH PENGELUARAN = SUM(C1–C12)

   SELISIH = TOTAL PENERIMAAN − JUMLAH PENGELUARAN

D. SALDO AKHIR OPERASIONAL = A + SELISIH
   D1. Kas Kecil   = Saldo pengisian − pengeluaran kas kecil
   D2. Kas Besar   = D − D1
   ─────────────────────────────────────
   JUMLAH SALDO KAS = D1 + D2
```

### Sumber Data per Bagian

| Bagian | Sumber Tabel | Query |
|--------|-------------|-------|
| A — Saldo Awal | `saldo_kas_bulanan` | `WHERE bulan=N AND tahun=Y` |
| B — Penerimaan | `jurnal_kas` | `WHERE jenis='masuk' AND bulan=N AND tahun=Y GROUP BY kode_akun_id` |
| C — Pengeluaran besar | `jurnal_kas` | `WHERE jenis='keluar' AND bulan=N AND tahun=Y GROUP BY kode_akun_id` |
| C — Pengeluaran kas kecil | `kas_kecil` | `WHERE bulan=N AND tahun=Y GROUP BY kode_akun_id` |
| D1 — Saldo Kas Kecil | `pengisian_kas_kecil` − `kas_kecil` | SUM per bulan |

### Cara Penggabungan Pengeluaran (C)

Untuk setiap kode akun pengeluaran (5.xx), nilai yang ditampilkan adalah:

```
total_kode = COALESCE(jurnal_kas.total, 0) + COALESCE(kas_kecil.total, 0)
```

Contoh kode `5.02.01.00` (BBM):
- Di `jurnal_kas` bulan Januari: Rp 0 (tidak ada transaksi besar BBM via bank)
- Di `kas_kecil` bulan Januari: Rp 53.508 (beli bensin tunai)
- **Total yang ditampilkan: Rp 53.508**

Contoh kode `5.01.01.01` (Gaji Guru Tetap):
- Di `jurnal_kas` bulan Januari: Rp 39.106.560 (transfer gaji)
- Di `kas_kecil` bulan Januari: Rp 0 (gaji tidak dibayar dari kas kecil)
- **Total yang ditampilkan: Rp 39.106.560**

### Formula Saldo

```
Saldo Akhir (D) = Saldo Awal (A) + Total Penerimaan (B) − Total Pengeluaran (C)
Saldo Akhir bulan N  =  Saldo Awal bulan N+1
```

**Verifikasi dari data Excel asli:**
- Januari 2025: 0 + 50.890.000 − 52.694.583 = **−1.804.583** ✓
- Februari 2025: −1.804.583 + 61.450.000 − 65.782.818 = **−6.137.401** ✓

### Mekanisme Saldo Awal (Kunci Bulan)

```
Alur normal per bulan:
1. Bulan berjalan → transaksi bebas diinput
2. Akhir bulan → admin review laporan
3. Admin klik "Kunci Bulan" (lock)
4. Sistem:
   a. Set saldo_kas_bulanan[bulan=N].is_locked = true
   b. Hitung saldo akhir bulan N
   c. Insert/update saldo_kas_bulanan[bulan=N+1].saldo_awal = saldo_akhir_N
5. Bulan N+1 siap digunakan, saldo awal sudah terisi otomatis
```

**Aturan setelah dikunci:**
- Tidak bisa tambah transaksi baru untuk bulan yang dikunci
- Tidak bisa edit/hapus transaksi di bulan yang dikunci
- Hanya admin yang bisa membuka kunci (`unlock`) jika ada koreksi darurat

**Untuk bulan pertama (Januari / bulan awal penggunaan):**
Admin menginput saldo awal secara manual di menu `Saldo Kas Bulanan`. Ini hanya dilakukan sekali saat pertama kali sistem digunakan untuk migrasi dari sistem lama.

### Implementasi Laravel (Pseudocode)

```php
// Service class: ArusKasBulananService.php

public function generate(int $bulan, int $tahun): array
{
    // A. Saldo Awal
    $saldoAwal = SaldoKasBulanan::where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->first();

    // B. Penerimaan dari jurnal_kas
    $penerimaan = JurnalKas::where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->where('jenis', 'masuk')
        ->selectRaw('kode_akun_id, SUM(cash + bank) as total')
        ->groupBy('kode_akun_id')
        ->with('kodeAkun')
        ->get()
        ->keyBy('kode_akun_id');

    // C. Pengeluaran dari jurnal_kas
    $pengeluaranBesar = JurnalKas::where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->where('jenis', 'keluar')
        ->selectRaw('kode_akun_id, SUM(cash + bank) as total')
        ->groupBy('kode_akun_id')
        ->get()
        ->keyBy('kode_akun_id');

    // C. Pengeluaran dari kas_kecil
    $pengeluaranKasKecil = KasKecil::where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->selectRaw('kode_akun_id, SUM(nominal) as total')
        ->groupBy('kode_akun_id')
        ->get()
        ->keyBy('kode_akun_id');

    // Gabungkan pengeluaran dari dua sumber
    $pengeluaran = $this->mergeByKodeAkun(
        $pengeluaranBesar,
        $pengeluaranKasKecil
    );

    // D. Hitung saldo
    $totalPenerimaan = $penerimaan->sum('total');
    $totalPengeluaran = $pengeluaran->sum('total');
    $saldoAkhir = $saldoAwal->saldo_awal_cash
                + $saldoAwal->saldo_awal_bank
                + $totalPenerimaan
                - $totalPengeluaran;

    // D1. Kas Kecil
    $pengisianKasKecil = PengisianKasKecil::where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->sum('nominal');
    $keluarKasKecil = KasKecil::where('bulan', $bulan)
        ->where('tahun', $tahun)
        ->sum('nominal');
    $saldoKasKecil = $pengisianKasKecil - $keluarKasKecil;

    return [
        'saldo_awal'        => $saldoAwal,
        'penerimaan'        => $penerimaan,
        'pengeluaran'       => $pengeluaran,
        'total_penerimaan'  => $totalPenerimaan,
        'total_pengeluaran' => $totalPengeluaran,
        'selisih'           => $totalPenerimaan - $totalPengeluaran,
        'saldo_akhir'       => $saldoAkhir,
        'saldo_kas_kecil'   => $saldoKasKecil,
        'saldo_kas_besar'   => $saldoAkhir - $saldoKasKecil,
    ];
}
```

---

## Laporan 2 — Dashboard Tahunan (Monitoring Anggaran)

### Tujuan
Melihat seluruh pergerakan keuangan dalam satu tahun dalam format tabel besar. Membandingkan realisasi dengan target anggaran. Setara sheet **DETAIL** di file Arus Kas Excel lama.

### Struktur Tabel

```
Kolom: Kode Akun | Nama Akun | Jan | Feb | Mar | Apr | Mei | Jun |
       Jul | Agt | Sep | Okt | Nov | Des | Akumulasi | Anggaran | % | Selisih
```

### Cara Hitung per Kolom

| Kolom | Formula | Catatan |
|-------|---------|---------|
| `Jan` s/d `Des` | SUM transaksi kode akun tersebut bulan itu | Gabungan `jurnal_kas` + `kas_kecil` |
| `Akumulasi` | SUM semua bulan yang datanya tidak null | Year-to-date, bukan seluruh 12 bulan |
| `Anggaran` | `anggaran.target` WHERE `kode_akun_id` AND `tahun=Y` | 0 jika belum diinput |
| `%` | `(Akumulasi / Anggaran) × 100` | Tampilkan "-" jika anggaran = 0 |
| `Selisih` | `Akumulasi − Anggaran` | Positif = realisasi melebihi anggaran |

### Baris Khusus: Saldo Awal per Bulan (Baris I)

Baris pertama laporan adalah **Saldo Awal Operasional** per bulan:

```
Saldo Awal Jan = 0 (atau input manual)
Saldo Awal Feb = Saldo Akhir Jan
Saldo Awal Mar = Saldo Akhir Feb
... dst
```

Data ini diambil dari `saldo_kas_bulanan.saldo_awal_cash + saldo_awal_bank` per bulan.

### Implementasi Query Efisien

**JANGAN lakukan ini** (12 × N query, sangat lambat):
```php
// SALAH — query terpisah per bulan
for ($bulan = 1; $bulan <= 12; $bulan++) {
    $data[$bulan] = JurnalKas::where('bulan', $bulan)->where('tahun', $tahun)->get();
}
```

**LAKUKAN ini** (1 query besar, pivot di PHP):
```php
// BENAR — satu query, pivot di Collection
$jurnal = JurnalKas::where('tahun', $tahun)
    ->selectRaw('bulan, kode_akun_id, SUM(cash + bank) as total')
    ->groupBy('bulan', 'kode_akun_id')
    ->get();

$kasKecil = KasKecil::where('tahun', $tahun)
    ->selectRaw('bulan, kode_akun_id, SUM(nominal) as total')
    ->groupBy('bulan', 'kode_akun_id')
    ->get();

// Pivot: [kode_akun_id][bulan] = total
$matrix = [];
foreach ($jurnal as $row) {
    $matrix[$row->kode_akun_id][$row->bulan] =
        ($matrix[$row->kode_akun_id][$row->bulan] ?? 0) + $row->total;
}
foreach ($kasKecil as $row) {
    $matrix[$row->kode_akun_id][$row->bulan] =
        ($matrix[$row->kode_akun_id][$row->bulan] ?? 0) + $row->total;
}

// Hitung akumulasi per kode
foreach ($matrix as $kodeId => $bulanan) {
    $matrix[$kodeId]['akumulasi'] = array_sum($bulanan);
}
```

### Tampilan Warna Pencapaian Anggaran

| Kondisi | Warna | Arti |
|---------|-------|------|
| % ≥ 100% | Merah | Realisasi melebihi anggaran (over budget) |
| 80% ≤ % < 100% | Kuning | Mendekati batas anggaran |
| % < 80% | Hijau | Masih dalam batas aman |
| Anggaran = 0 | Abu-abu | Belum diset anggaran |

> Untuk baris **Penerimaan** (4.xx), logika warna dibalik:
> % ≥ 100% = Hijau (realisasi melebihi target penerimaan = bagus),
> % < 80% = Merah (penerimaan kurang dari target = perlu perhatian).

---

## Laporan 3 — Pivot Rekap Cash & Bank Bulanan

### Tujuan
Rekap total transaksi per kode akun untuk satu bulan tertentu, dipisah antara kolom **Cash** dan kolom **Bank**. Setara sheet **PIVOT** di file Buku Cash & Bank Excel lama.

### Struktur Output

```
Kode Akun      | Nama Akun                    | Cash        | Bank        | Total
─────────────────────────────────────────────────────────────────────────────────
4.01.01.00     | Jurusan RPL                  | 18.500.000  | 12.800.000  | 31.300.000
4.01.02.00     | Jurusan TBSM                 |  9.600.000  |  1.600.000  | 11.200.000
4.01.03.00     | Jurusan Perhotelan           |  3.200.000  |          0  |  3.200.000
4.01.05.00     | Seragam                      |    990.000  |          0  |    990.000
4.01.06.00     | Kegiatan                     |    750.000  |  1.500.000  |  2.250.000
4.02.01.00     | Pendaftaran/Biaya Gedung      |  1.950.000  |          0  |  1.950.000
5.01.01.01     | Gaji Guru Tetap              |          0  | 39.106.560  | 39.106.560
5.01.01.04     | Gaji Tenaga Kependidikan      |          0  |  4.344.010  |  4.344.010
5.02.02.00     | Rekening Listrik              |          0  |  2.938.096  |  2.938.096
...
─────────────────────────────────────────────────────────────────────────────────
GRAND TOTAL                                   | 34.990.000  | 62.288.666  | 97.278.666
```

> Catatan: Kolom Cash dan Bank **hanya dari `jurnal_kas`**.
> Pengeluaran dari `kas_kecil` tidak masuk ke pivot ini karena kas kecil tidak membedakan cash vs bank (semuanya tunai).
> Pivot kas kecil ada di **Laporan 5** terpisah.

### Query

```php
$pivot = JurnalKas::where('bulan', $bulan)
    ->where('tahun', $tahun)
    ->selectRaw('
        kode_akun_id,
        SUM(cash) as total_cash,
        SUM(bank) as total_bank,
        SUM(cash + bank) as total
    ')
    ->groupBy('kode_akun_id')
    ->with('kodeAkun')
    ->orderBy('kode_akun_id') // urut by kode agar 4.xx dulu baru 5.xx
    ->get();
```

---

## Laporan 4 — Rekap Tunggakan SPP

### Tujuan
Laporan formal yang menampilkan statistik pembayaran SPP seluruh siswa untuk bulan tertentu. Digunakan untuk rapat yayasan dan evaluasi bulanan. Berbeda dengan halaman Tunggakan operasional di Phase 1 yang hanya berupa daftar.

### Struktur Laporan

**Bagian 1 — Statistik Ringkas**

```
Bulan: Januari 2025

Total Siswa Aktif    : 270 siswa
Sudah Bayar SPP      : 215 siswa  (79,6%)
Belum Bayar SPP      : 55 siswa   (20,4%)
Total Nominal Masuk  : Rp  86.000.000
Estimasi Tunggakan   : Rp  22.000.000
```

**Bagian 2 — Per Jurusan**

| Jurusan | Total Siswa | Sudah Bayar | Belum Bayar | % Lunas |
|---------|-------------|-------------|-------------|---------|
| RPL | 95 | 80 | 15 | 84,2% |
| TBSM | 110 | 85 | 25 | 77,3% |
| Perhotelan | 65 | 50 | 15 | 76,9% |
| **Total** | **270** | **215** | **55** | **79,6%** |

**Bagian 3 — Daftar Detail Siswa Belum Bayar**

| No | NIS | Nama | Kelas | Jurusan | Nominal SPP | No HP Wali |
|----|-----|------|-------|---------|-------------|------------|
| 1 | 24010262 | Auliya Indriyani | X RPL | RPL | Rp 400.000 | 08123456789 |
| ... |

### Logika Query

```php
// Ambil semua NIS yang sudah bayar bulan ini
$sudahBayar = KartuSpp::where('bulan', $bulan)
    ->where('tahun', $tahun)
    ->pluck('nis')
    ->toArray();

// Siswa yang belum bayar
$belumBayar = Siswa::aktif()
    ->whereNotIn('nis', $sudahBayar)
    ->with(['kelas', 'jurusan'])
    ->orderBy('jurusan_id')
    ->orderBy('kelas_id')
    ->orderBy('nama')
    ->get();

// Statistik per jurusan
$statsPerJurusan = Siswa::aktif()
    ->with('jurusan')
    ->selectRaw('jurusan_id, COUNT(*) as total_siswa')
    ->groupBy('jurusan_id')
    ->get()
    ->map(function ($row) use ($sudahBayar) {
        $sudah = Siswa::where('jurusan_id', $row->jurusan_id)
            ->whereIn('nis', $sudahBayar)
            ->count();
        return [
            'jurusan'      => $row->jurusan->nama,
            'total'        => $row->total_siswa,
            'sudah_bayar'  => $sudah,
            'belum_bayar'  => $row->total_siswa - $sudah,
            'persen_lunas' => $row->total_siswa > 0
                ? round(($sudah / $row->total_siswa) * 100, 1)
                : 0,
        ];
    });
```

### Perbedaan dengan Halaman Tunggakan di Phase 1

| Aspek | Halaman Tunggakan (Phase 1) | Laporan Tunggakan (Phase 2) |
|-------|-----------------------------|-----------------------------|
| Tujuan | Operasional harian bendahara | Laporan formal untuk rapat |
| Isi | Daftar siswa belum bayar saja | Statistik + per jurusan + daftar detail |
| Aksi | Ada tombol "Kirim WA" langsung | Read-only, bisa export PDF/Excel |
| Akses | Admin, Bendahara | Admin, Bendahara, Kepala Sekolah |
| Export | Tidak | Ya — PDF dan Excel |

---

## Laporan 5 — Pivot Rekap Kas Kecil Bulanan

### Tujuan
Merangkum total pengeluaran kas kecil per kode akun untuk satu bulan. Digunakan untuk verifikasi bahwa total kas kecil di laporan Arus Kas sudah benar. Setara sheet **PIVOT 1–10** di file Kas Kecil Excel lama.

### Struktur Output

```
PIVOT KAS KECIL — JANUARI 2025

Kode Akun      | Nama Akun                              | Total
───────────────────────────────────────────────────────────────
5.02.01.00     | Bahan Bakar / Bensin                   |    53.508
5.02.05.00     | Air Minum Galon / Botol / Gelas        |    57.789
5.02.06.00     | Alat Tulis dan Keperluan Kantor        |    17.350
5.02.07.00     | Alat-Alat Listrik                      |     8.026
5.02.08.00     | Alat-Alat Elektronika                  |    26.754
5.02.13.00     | Ekspedisi / Ongkir / Materai           |   210.701
5.02.14.00     | Perlengkapan Rumah Tangga Kantor       |   227.144
5.04.01.01     | Beban Pelayanan Kebersihan             |   200.000
5.08.05.00     | Jamuan / Konsumsi                      |   360.000
5.11.01.00     | Beban Kegiatan Kebudayaan              |   137.785
───────────────────────────────────────────────────────────────
GRAND TOTAL                                             | 1.299.061
```

### Query

```php
$pivotKasKecil = KasKecil::where('bulan', $bulan)
    ->where('tahun', $tahun)
    ->selectRaw('kode_akun_id, SUM(nominal) as total')
    ->groupBy('kode_akun_id')
    ->with('kodeAkun')
    ->orderBy('kode_akun_id')
    ->get();

$grandTotal = $pivotKasKecil->sum('total');
```

### Verifikasi Silang dengan Laporan Arus Kas

Grand Total Pivot Kas Kecil **harus sama** dengan total kolom **Pengeluaran Kas Kecil** di Laporan Arus Kas bulan yang sama. Jika berbeda, ada transaksi yang input bulan/tahunnya salah.

```php
// Validasi otomatis — ditampilkan sebagai peringatan di UI
$totalArusKas    = // total C (kas kecil) dari ArusKasBulananService
$totalPivotKK    = $pivotKasKecil->sum('total');
$selisihValidasi = abs($totalArusKas - $totalPivotKK);

if ($selisihValidasi > 0) {
    // Tampilkan warning: "Ada selisih Rp X antara pivot kas kecil dan laporan arus kas"
}
```

---

## Mekanisme Saldo Kas Bulanan (Tabel `saldo_kas_bulanan`)

Tabel ini adalah titik sambung antara bulan satu dengan bulan berikutnya.

### Skenario Penggunaan

**Skenario 1 — Pertama kali pakai sistem (migrasi dari Excel)**

```
Admin buka menu "Saldo Kas" → pilih bulan Januari 2025
→ Input saldo awal:
    Saldo Awal Cash : Rp 0
    Saldo Awal Bank : Rp 0
→ Save → sistem siap digunakan mulai Januari 2025
```

**Skenario 2 — Kunci bulan normal**

```
Akhir Januari → admin review laporan arus kas Januari
→ Semua transaksi sudah benar
→ Admin klik "Kunci Bulan Januari"
→ Sistem hitung:
    Saldo Akhir Jan = 0 + 50.890.000 - 52.694.583 = -1.804.583
→ Sistem insert ke saldo_kas_bulanan:
    bulan=2, tahun=2025,
    saldo_awal_cash = -1.804.583 (atau split cash/bank sesuai data)
    saldo_awal_bank = 0
    is_locked = false (Februari belum dikunci)
→ Januari dikunci: is_locked = true
```

**Skenario 3 — Koreksi setelah bulan dikunci**

```
Admin temukan ada transaksi Januari yang salah
→ Admin klik "Buka Kunci Januari" (unlock)
→ Sistem set saldo_kas_bulanan[Jan].is_locked = false
→ Sistem hapus saldo awal Februari yang sudah otomatis terisi
   (set null, bukan hapus record)
→ Admin koreksi transaksi
→ Admin kunci ulang Januari
→ Saldo awal Februari di-recalculate otomatis
```

### Struktur Tabel

```sql
saldo_kas_bulanan
├── id
├── bulan          TINYINT   -- 1-12
├── tahun          SMALLINT  -- 2025
├── saldo_awal_cash  DECIMAL(15,2)  -- bisa negatif
├── saldo_awal_bank  DECIMAL(15,2)  -- bisa negatif
├── is_locked      BOOLEAN   -- apakah bulan ini sudah dikunci
├── created_at
└── updated_at
UNIQUE(bulan, tahun)
```

---

## Catatan Implementasi Filament 4

### Halaman Laporan sebagai Custom Page

Laporan-laporan di Phase 2 **bukan** Resource biasa karena tidak ada CRUD. Implementasikan sebagai **Filament Page** dengan Livewire:

```php
// app/Filament/Pages/LaporanArusKas.php
class LaporanArusKas extends Page
{
    protected static string $view = 'filament.pages.laporan-arus-kas';
    protected static ?string $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Arus Kas';
    protected static $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 1;

    public int $bulan;
    public int $tahun;

    public function mount(): void
    {
        $this->bulan = now()->month;
        $this->tahun = now()->year;
    }

    // Data laporan di-compute setiap kali bulan/tahun berubah
    #[Computed]
    public function dataLaporan(): array
    {
        return app(ArusKasBulananService::class)
            ->generate($this->bulan, $this->tahun);
    }
}
```

### Daftar Pages yang Perlu Dibuat

| File | Path URL | Isi |
|------|----------|-----|
| `LaporanArusKas.php` | `/admin/laporan/arus-kas` | Laporan 1 |
| `DashboardTahunan.php` | `/admin/laporan/tahunan` | Laporan 2 |
| `PivotCashBank.php` | `/admin/laporan/pivot-cash-bank` | Laporan 3 |
| `LaporanTunggakanSpp.php` | `/admin/laporan/tunggakan-spp` | Laporan 4 |
| `PivotKasKecil.php` | `/admin/laporan/pivot-kas-kecil` | Laporan 5 |

### Service Classes yang Perlu Dibuat

```
app/Services/
├── ArusKasBulananService.php    -- generate laporan arus kas per bulan
├── DashboardTahunanService.php  -- generate matriks 12 bulan
├── TunggakanSppService.php      -- hitung statistik tunggakan
└── SaldoKasService.php          -- kelola saldo awal & kunci bulan
```

---

## Ringkasan Dependency Antar Laporan

```
jurnal_kas ─────┬──► Laporan 1 (Arus Kas Bulanan)
                ├──► Laporan 2 (Dashboard Tahunan)
                └──► Laporan 3 (Pivot Cash & Bank)

kas_kecil ──────┬──► Laporan 1 (digabung dengan jurnal_kas)
                ├──► Laporan 2 (digabung dengan jurnal_kas)
                └──► Laporan 5 (Pivot Kas Kecil)

kartu_spp ──────►  Laporan 4 (Tunggakan SPP)

saldo_kas_bulanan ─► Laporan 1 (Saldo Awal)
                  ─► Laporan 2 (Baris Saldo Awal per bulan)

anggaran ───────►  Laporan 2 (kolom Anggaran & % Pencapaian)
```
