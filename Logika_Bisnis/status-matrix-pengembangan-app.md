# Status Matrix Pengembangan App Keuangan SMK

Update terakhir: 14 April 2026

Dokumen ini merangkum posisi pengembangan aplikasi berdasarkan fase implementasi yang sudah disepakati, kondisi codebase saat ini, dan fitur yang sudah hidup di panel admin.

## Ringkasan Status

| Phase | Fokus | Status | Estimasi Progres |
|---|---|---|---:|
| Phase 0 | Fondasi aplikasi dan master data | Selesai | 100% |
| Phase 1 | Operasional transaksi harian | Selesai secara fungsional | 90% |
| Phase 2 | Laporan otomatis | Berjalan, masuk tahap uji dan penyempurnaan | 75% |
| Phase 3 | Export dan finalisasi laporan | Belum dimulai penuh | 15% |

## Phase 0 — Fondasi Aplikasi dan Master Data

### Tujuan
- Menyediakan struktur dasar aplikasi.
- Menyediakan autentikasi, role, permission, dan master data inti.
- Menyiapkan data dasar untuk transaksi dan laporan.

### Status
Selesai.

### Yang Sudah Ada
- Login admin Filament aktif.
- Root `/` sudah diarahkan ke `/admin`.
- Role default sudah tersedia:
  - `admin`
  - `bendahara`
  - `kepala_sekolah`
- Permission dasar sudah disiapkan melalui seeder.
- Master data inti sudah ada:
  - `Kode Akun`
  - `Jurusan`
  - `Kelas`
  - `Siswa`
- Relasi `Jurusan -> Kelas -> Siswa` sudah berjalan.
- Seeder dasar dan akun login test sudah tersedia.
- Demo siswa untuk pengujian sudah tersedia.

### Catatan
- Phase ini secara praktis sudah aman dipakai sebagai fondasi.

## Phase 1 — Operasional Transaksi Harian

### Tujuan
- Menangani input transaksi keuangan harian.
- Menghubungkan transaksi dengan logika bisnis sekolah.
- Menyediakan operasional pembayaran SPP dan kas kecil.

### Status
Selesai secara fungsional.

### Yang Sudah Ada
- `Jurnal Cash & Bank`
- `Kas Kecil`
- `Kartu SPP`
- `Tunggakan SPP` operasional
- Ringkasan bulanan/dashboard dasar
- Validasi `cash + bank > 0`
- Akun SPP khusus:
  - `4.01.01.00`
  - `4.01.02.00`
  - `4.01.03.00`
- Sinkronisasi pembayaran SPP dari `JurnalKas` ke `KartuSPP`
- Dukungan pembayaran multi-bulan SPP
- Auto nomor kwitansi penerimaan
- Filter akun kas kecil vs akun jurnal besar
- `NIS` dikunci saat edit siswa
- Aturan akun terpakai diarahkan ke nonaktif, bukan hapus

### Yang Perlu Dijaga Saat UAT
- Uji alur SPP 1 bulan dan multi-bulan
- Uji jurnal `masuk` dan `keluar`
- Uji saldo kas kecil vs pengisian
- Uji hak akses bendahara vs admin
- Uji halaman tunggakan operasional

### Catatan
- Secara bisnis inti sudah bisa dipakai.
- Status belum saya beri 100% karena tetap perlu UAT intensif dari sisi angka dan kenyamanan operator.

## Phase 2 — Laporan Otomatis

### Tujuan
- Menghasilkan laporan dari data transaksi Phase 1 tanpa input manual ulang.
- Menyediakan laporan formal bulanan dan tahunan untuk operasional serta rapat yayasan.

### Status
Berjalan dan sudah masuk tahap uji/stabilisasi.

### Yang Sudah Ada
- Halaman laporan otomatis sudah tersedia:
  - `/admin/laporan/arus-kas`
  - `/admin/laporan/tahunan`
  - `/admin/laporan/pivot-cash-bank`
  - `/admin/laporan/tunggakan-spp`
  - `/admin/laporan/pivot-kas-kecil`
- Laporan dibangun sebagai `Filament Page`, bukan CRUD resource.
- Service laporan sudah dipisah agar perhitungan tidak tercecer:
  - `CashFlowReportService`
  - `SaldoKasService`
  - `DashboardTahunanReportService`
  - `PivotCashBankReportService`
  - `PettyCashReportService`
  - `SppArrearsReportService`
- `Arus Kas Bulanan` sudah mengikuti struktur laporan phase 2:
  - saldo awal
  - penerimaan
  - pengeluaran
  - saldo akhir
- Pengeluaran arus kas sudah menggabungkan `jurnal_kas` dan `kas_kecil`.
- Lock bulan sudah tersedia.
- Unlock bulan sudah tersedia.
- Transaksi bulan yang dikunci sudah dibatasi dari edit/hapus pada area utama.
- Rekap tunggakan sudah punya statistik formal, bukan hanya daftar operasional.
- Pivot kas kecil sudah menampilkan validasi terhadap total arus kas.

### Yang Masih Perlu Distabilkan
- Validasi angka laporan terhadap sampel data real user
- Uji lock/unlock lintas bulan
- Uji carry-over saldo awal ke bulan berikutnya
- Uji perbedaan saldo kas besar dan kas kecil
- Penyelarasan tampilan laporan dengan format final yang diinginkan pihak sekolah

### Risiko / Catatan
- Unlock bulan saat ini masih mereset saldo awal bulan berikutnya ke nilai default aman, belum ke `null` murni di level skema.
- Laporan sudah hidup, tetapi masih perlu UAT intensif sebelum dianggap final 100%.

## Phase 3 — Export dan Finalisasi Distribusi Laporan

### Tujuan
- Menyediakan export PDF/Excel dari laporan phase 2.
- Menyediakan format cetak/final yang siap dibagikan ke yayasan atau kepala sekolah.

### Status
Belum dimulai penuh.

### Indikasi Kesiapan
- Dependency export sudah tersedia di project:
  - `maatwebsite/excel`
  - `barryvdh/laravel-dompdf`
- Permission `export_laporan` sudah ada di seeder.

### Yang Belum Terlihat Sebagai Fitur Utama
- Tombol export per laporan
- Template PDF final
- Export Excel final
- Pengujian hasil export terhadap format kebutuhan user

### Fokus Saat Mulai Phase 3
- Tambahkan tombol export di setiap halaman laporan utama
- Siapkan format Excel yang mendekati dokumen kerja sekolah
- Siapkan template PDF untuk cetak formal
- Uji hasil export dengan data riil

## Penilaian Posisi Saat Ini

Secara umum, aplikasi sudah melewati tahap fondasi dan transaksi inti. Posisi sekarang paling tepat disebut:

**Sudah masuk Phase 2, dengan kondisi Phase 1 sudah cukup matang dan Phase 2 sedang dalam tahap uji serta penyempurnaan.**

## Prioritas Berikutnya

1. UAT penuh seluruh laporan phase 2 dengan data realistis.
2. Perbaiki detail angka atau format yang belum cocok dengan kebutuhan sekolah.
3. Mulai implementasi tombol export PDF/Excel sebagai awal phase 3.
4. Siapkan dokumen checklist UAT per menu dan per laporan.

## Checklist Cepat Penentu Kesiapan Naik ke Phase 3

- Semua laporan phase 2 bisa dibuka tanpa error
- Angka laporan cocok dengan transaksi sumber
- Lock/unlock bulan bekerja konsisten
- Saldo awal dan saldo akhir antar bulan konsisten
- Tunggakan SPP cocok dengan data `kartu_spp`
- Operator bendahara bisa memakai flow harian tanpa bug besar

Jika seluruh checklist di atas sudah aman, maka aplikasi bisa dianggap siap masuk eksekusi penuh Phase 3.
