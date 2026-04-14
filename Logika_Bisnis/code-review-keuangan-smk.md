# Code Review — Logika Bisnis Keuangan SMK Karya Bangsa

> Review dilakukan terhadap 147 file yang di-upload. Status: **3 bug kritis, 5 issue minor, mayoritas logika sudah benar.**

---

## ✅ Yang Sudah Benar

### Arus Kas & Saldo (CashFlowReportService)

Formula saldo sudah benar dan sesuai Excel asli:

```php
// ✓ Total pengeluaran = jurnal_kas keluar + kas_kecil (dua sumber digabung per kode akun)
$saldoAkhirTotal = ($opening['cash'] + $opening['bank']) + $selisih;
$saldoAkhirCash  = $opening['cash'] + masuk_cash - keluar_cash - kas_kecil;
$saldoAkhirBank  = $opening['bank'] + masuk_bank - keluar_bank;
```

Verifikasi Januari 2025: `0 + 50.890.000 − 52.694.583 = −1.804.583` ✓  
Saldo akhir bulan N otomatis menjadi saldo awal bulan N+1 saat kunci bulan ✓

### Kunci/Buka Bulan (SaldoKasService)

```php
// ✓ Lock: simpan saldo akhir ke bulan berikutnya
public function lockPeriod(int $bulan, int $tahun): void
// ✓ Unlock: reset saldo awal bulan berikutnya ke 0
public function unlockPeriod(int $bulan, int $tahun): void
// ✓ Proteksi: transaksi bulan terkunci diblokir di semua Resource
if (app(SaldoKasService::class)->isLocked(...)) throw ValidationException...
```

Implementasi tiga skenario (first-time, normal lock, koreksi setelah unlock) sudah benar ✓

### Observer SPP (JurnalKasObserver)

```php
// ✓ Delete dulu kartu_spp lama, lalu recreate — mencegah data stale
$this->deleteLinkedKartuSpp($jurnal);
foreach ($bulanDibayar as $bulan) {
    KartuSpp::updateOrCreate(['nis', 'bulan', 'tahun'], [...]);
}
```

Unique constraint `(nis, bulan, tahun)` di migration juga ada ✓

### Import Excel — Proteksi Data Jurnal

```php
// ✓ Import tidak bisa timpa record yang berasal dari jurnal kas nyata
if ($existing?->jurnal_kas_id) {
    $errors[] = 'Pembayaran ... berasal dari jurnal kas dan tidak boleh ditimpa oleh import.';
    return false;
}
```

### Export Excel — Running Balance

```php
// ✓ Saldo Cash & Bank dihitung kumulatif per baris (running balance)
if ($record->jenis === 'masuk') {
    $saldoCash += (float) $record->cash;
    $saldoBank += (float) $record->bank;
} else {
    $saldoCash -= (float) $record->cash;
    $saldoBank -= (float) $record->bank;
}
```

### Dashboard Tahunan — Query Pivot Efisien

```php
// ✓ Satu query per tabel, pivot di PHP — bukan 12 query terpisah
$jurnal   = JurnalKas::where('tahun', $tahun)->selectRaw('bulan, kode_akun_id, SUM(...) as total')->groupBy(...)->get();
$kasKecil = KasKecil::where('tahun', $tahun)->selectRaw('bulan, kode_akun_id, SUM(nominal) as total')->groupBy(...)->get();
$matrix[$row->kode_akun_id][$row->bulan] += $row->total;
```

### Validasi SPP Multi-Bulan

```php
// ✓ Total bayar harus = nominal_spp × jumlah_bulan
$totalSeharusnya = round((float) $siswa->nominal_spp * count($bulanSpp), 2);
$totalDibayar    = round($cash + $bank, 2);
if ($totalDibayar !== $totalSeharusnya) throw ValidationException...
```

### Policy Edit Bendahara

```php
// ✓ Jurnal kas: bendahara hanya bisa edit transaksi ≤ 3 hari
return ($jurnal->created_at?->gte(now()->subDays(3)) ?? false)
    && ! app(SaldoKasService::class)->isLocked($jurnal->bulan, $jurnal->tahun);
```

---

## 🔴 Bug Kritis

### Bug 1: `scopeTransaksional` Terlalu Lebar — Kode Header Muncul di Dropdown

**File:** `app/Models/KodeAkun.php` dan `database/seeders/KodeAkunSeeder.php`

**Masalah:**

```php
// KodeAkun.php
public function scopeTransaksional($query)
{
    return $query
        ->where('aktif', true)
        ->whereNotNull('sub_kategori'); // ← hanya cek sub_kategori
}
```

Di seeder, kode-kode intermediate header (bukan kode transaksi) juga punya `sub_kategori` terisi:

```php
// KodeAkunSeeder.php — SALAH: sub_kategori diisi padahal ini header
['kode' => '5.01.01.00', 'nama' => 'BEBAN GAJI',            'sub_kategori' => 'Gaji', ...],
['kode' => '5.01.02.00', 'nama' => 'BEBAN TUNJANGAN',       'sub_kategori' => 'Tunjangan', ...],
['kode' => '5.01.03.00', 'nama' => 'BEBAN PEGAWAI LAINNYA', 'sub_kategori' => 'Pegawai Lainnya', ...],
['kode' => '5.04.01.00', 'nama' => 'KONTRAK PELAYANAN GEDUNG','sub_kategori' => 'Pelayanan Gedung', ...],
['kode' => '5.04.02.00', 'nama' => 'BEBAN PERBAIKAN',       'sub_kategori' => 'Perbaikan', ...],
['kode' => '5.04.03.00', 'nama' => 'BEBAN JASA',            'sub_kategori' => 'Jasa', ...],
```

**Dampak:** Kode seperti `5.01.01.00 — BEBAN GAJI` muncul di dropdown form Jurnal Kas padahal yang seharusnya dipilih adalah `5.01.01.01 — Gaji Guru Tetap`. Kode ini juga masuk ke laporan Dashboard Tahunan sebagai baris terpisah.

**Fix — Opsi A (Recommended): Perbaiki seeder**

```php
// Ubah sub_kategori menjadi null untuk semua intermediate header
['kode' => '5.01.01.00', 'nama' => 'BEBAN GAJI',            'sub_kategori' => null, ...],
['kode' => '5.01.02.00', 'nama' => 'BEBAN TUNJANGAN',       'sub_kategori' => null, ...],
['kode' => '5.01.03.00', 'nama' => 'BEBAN PEGAWAI LAINNYA', 'sub_kategori' => null, ...],
['kode' => '5.04.01.00', 'nama' => 'KONTRAK PELAYANAN GEDUNG', 'sub_kategori' => null, ...],
['kode' => '5.04.02.00', 'nama' => 'BEBAN PERBAIKAN',       'sub_kategori' => null, ...],
['kode' => '5.04.03.00', 'nama' => 'BEBAN JASA',            'sub_kategori' => null, ...],
```

**Fix — Opsi B: Tambah filter di scope**

```php
public function scopeTransaksional($query)
{
    return $query
        ->where('aktif', true)
        ->whereNotNull('sub_kategori')
        // Exclude kode yang punya anak (ada kode lain yang dimulai dengan prefix yang sama + digit)
        // Simpler: cek apakah segment ke-3 non-zero tapi segment ke-4 adalah 00
        ->whereRaw("
            NOT (
                SUBSTRING_INDEX(kode, '.', 3) != SUBSTRING_INDEX(kode, '.', 2)
                AND RIGHT(kode, 3) = '.00'
                AND SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) != '00'
            )
        ");
}
```

Opsi A lebih bersih dan tidak memerlukan query SQL kompleks. **Gunakan Opsi A.**

---

### Bug 2: `AuthServiceProvider` Tidak Terdaftar di `bootstrap/providers.php`

**File:** `bootstrap/providers.php`

**Masalah:**

```php
// bootstrap/providers.php — AuthServiceProvider hilang!
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    // ← App\Providers\AuthServiceProvider::class TIDAK ADA
];
```

**Dampak:** Policy `$policies` array di `AuthServiceProvider` tidak terdaftar secara eksplisit. Bergantung pada auto-discovery Laravel 12 yang membutuhkan naming convention tepat. Jika ada mismatch nama, policy silently tidak jalan — transaksi bisa diakses tanpa otorisasi.

**Fix:**

```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,   // ← tambahkan ini
    App\Providers\Filament\AdminPanelProvider::class,
];
```

Pastikan juga file berada di `app/Providers/AuthServiceProvider.php`, bukan di `app/Policies/`.

---

### Bug 3: Session-based Passing `spp_bulan_pending` Rawan Race Condition

**File:** `app/Filament/Resources/JurnalKasResource.php` dan `app/Observers/JurnalKasObserver.php`

**Masalah:**

```php
// JurnalKasResource::prepareFormDataBeforeSave()
session(['spp_bulan_pending' => $bulanSpp]); // set di Resource

// JurnalKasObserver::syncKartuSpp()
$bulanDibayar = session()->pull('spp_bulan_pending'); // pull di Observer
```

Session digunakan sebagai "komunikasi" antara Resource dan Observer. Ini berisiko karena:
1. Jika dua user submit simultan, session bisa tercampur
2. Jika Observer gagal (exception) sebelum `pull()`, data tetap ada di session dan memengaruhi request berikutnya
3. Saat import massal (SiswaImport/HistoriSppImport), session mungkin tidak tersedia (CLI context)

**Fix:** Gunakan property static atau pass data langsung ke model:

```php
// Di JurnalKasResource::prepareFormDataBeforeSave()
// Simpan di property model sementara — bukan session
// Hapus: session(['spp_bulan_pending' => $bulanSpp]);
// Gantinya, model JurnalKas sudah punya field $bulan_spp yang tidak di-fillable
// Gunakan accessor non-persistent

// Di JurnalKas model, tambahkan:
public array $bulanSppPending = [];

// Di Resource, sebelum save, set ke model instance via afterCreate/afterSave hook
// Atau gunakan pendekatan yang lebih clean: simpan di field JSON temporary

// Solusi paling simpel: jadikan $bulanSpp sebagai parameter di method create/update
// dengan override mutateFormDataBeforeCreate yang pass data ke Observer via model property
```

Solusi minimal yang tidak perlu refactor besar: tambahkan guard di Observer agar fallback ke `kartu_spp` yang sudah ada jika session kosong (sudah ada), dan catat dalam log jika session miss.

---

## 🟡 Issue Minor

### Issue 4: Inkonsistensi Batas Edit Bendahara antara Jurnal Kas dan Kas Kecil

| Modul | Batas Edit Bendahara |
|-------|---------------------|
| `JurnalKasPolicy` | 3 hari ke belakang |
| `KasKecilPolicy` | Hanya bulan berjalan (current month) |

Kas kecil jauh lebih restriktif. Jika transaksi kas kecil diinput tanggal 1 dan ada kesalahan, bendahara tidak bisa koreksi di tanggal 5 bulan yang sama (sudah berbeda bulan berjalan). Pertimbangkan menyamakan ke 3 hari, atau konfirmasi ini memang disengaja.

```php
// KasKecilPolicy saat ini (hanya bulan berjalan):
return $kasKecil->bulan === now()->month && $kasKecil->tahun === now()->year && ...

// Alternatif (samakan dengan jurnal kas):
return ($kasKecil->created_at?->gte(now()->subDays(3)) ?? false) && ...
```

---

### Issue 5: Kop Surat PDF Masih Placeholder

**File:** `resources/views/pdf/partials/kop-surat.blade.php`

```html
{{-- SAAT INI — placeholder development --}}
<div style="font-size:10px; color:#444;">
    Sistem Keuangan Sekolah<br>
    Laporan internal pengembangan aplikasi   {{-- ← ini tidak boleh ada di produksi --}}
</div>
```

**Fix:**

```html
<div style="font-size:10px; color:#444;">
    Jl. [Alamat Lengkap SMK Karya Bangsa], Sintang, Kalimantan Barat 78611<br>
    Telp: [No Telp] | Email: [email@karyabangsa.sch.id]<br>
    NPSN: [NPSN] | Akreditasi: A
</div>
```

---

### Issue 6: `DummyFinanceSeeder` dan `SiswaSeeder` Ikut Berjalan di Produksi jika ENV Salah

**File:** `database/seeders/DatabaseSeeder.php`

```php
protected function shouldSeedDemoData(): bool
{
    if (app()->environment(['local', 'testing'])) {
        return true;
    }
    return filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL);
}
```

Jika server production tidak set `APP_ENV=production` dengan benar, dummy data (transaksi fiktif April 2026) akan ikut di-seed. Ini bisa mengotori database produksi.

**Fix:** Tambahkan konfirmasi eksplisit:

```php
protected function shouldSeedDemoData(): bool
{
    // TIDAK pernah seed demo di production, apapun ENV variabelnya
    if (app()->environment('production')) {
        return false;
    }
    return app()->environment(['local', 'testing'])
        || filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL);
}
```

---

### Issue 7: `SiswaImport` — Nominal SPP Hardcoded per Jurusan, Mengabaikan Data Siswa

**File:** `app/Imports/SiswaImport.php`

```php
$nominalSpp = match ($kelas->jurusan?->kode) {
    'RPL'  => 400000,
    'TBSM' => 375000,
    'HTL'  => 425000,
    default => 400000,
};
```

Ini mengabaikan kemungkinan siswa punya keringanan SPP individual. Saat import dari Excel D1/D2, semua siswa RPL akan di-set 400.000 meskipun di Excel lama ada yang berbeda.

**Fix:** Baca nominal dari kolom Excel jika ada, atau set default dari `jurusan.nominal_spp` jika jurusan punya field tersebut:

```php
// Baca dari kolom Excel jika ada, fallback ke default jurusan
$nominalSppFromExcel = $this->extractNumeric($indexed['nominal_spp'] ?? null);
$nominalSpp = $nominalSppFromExcel > 0
    ? $nominalSppFromExcel
    : match ($kelas->jurusan?->kode) {
        'RPL' => 400000, 'TBSM' => 375000, 'HTL' => 425000, default => 400000,
    };
```

---

### Issue 8: `pengisian_kas_kecil` Tidak Ada di `AppServiceProvider` (SoftDeletes)

**File:** `database/migrations/2026_04_13_000001_add_audit_columns_to_pengisian_kas_kecil_table.php`

Migration menambahkan `SoftDeletes` ke `pengisian_kas_kecil`, tetapi model `PengisianKasKecil` sudah menggunakan `use SoftDeletes` sejak awal. Ini tidak menyebabkan error karena migration hanya menambahkan kolom `deleted_at` yang memang belum ada. Namun perlu dipastikan migration ini sudah dijalankan sebelum model dipakai.

**Bukan bug**, hanya perlu dipastikan urutan migration benar di production.

---

## 📋 Ringkasan

| No | Status | Komponen | Masalah |
|----|--------|----------|---------|
| 1 | 🔴 Kritis | `KodeAkun::scopeTransaksional` + Seeder | Header intermediate muncul di dropdown transaksi |
| 2 | 🔴 Kritis | `bootstrap/providers.php` | `AuthServiceProvider` tidak terdaftar |
| 3 | 🔴 Kritis | Observer + Resource | Session-based data passing rawan race condition |
| 4 | 🟡 Minor | `KasKecilPolicy` | Batas edit bendahara inkonsisten dengan jurnal kas |
| 5 | 🟡 Minor | `kop-surat.blade.php` | Placeholder development masih ada |
| 6 | 🟡 Minor | `DatabaseSeeder` | Dummy data bisa ter-seed di production |
| 7 | 🟡 Minor | `SiswaImport` | Nominal SPP hardcoded, abaikan keringanan individual |
| 8 | ℹ️ Info | `PengisianKasKecil` migration | Pastikan urutan migration benar |

---

## Prioritas Perbaikan Sebelum Produksi

1. **Fix Bug 1 sekarang** — jalankan `php artisan db:seed --class=KodeAkunSeeder` setelah fix seeder, atau buat migration untuk update `sub_kategori = null` pada kode-kode header intermediate
2. **Fix Bug 2 sekarang** — satu baris tambahan di `bootstrap/providers.php`
3. **Fix Bug 3 sebelum go-live** — refactor session menjadi model property
4. **Fix Issue 5 sebelum cetak dokumen** — kop surat real sekolah
5. **Fix Issue 6 sebelum deploy production** — guard ENV
