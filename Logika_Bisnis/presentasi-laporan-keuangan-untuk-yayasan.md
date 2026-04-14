# Presentasi Laporan Keuangan Aplikasi Keuangan SMK

Dokumen ini disusun sebagai naskah presentasi untuk menjelaskan kepada yayasan bagaimana laporan pada aplikasi bekerja, dari sisi sumber data, logika perhitungan, dan manfaat kontrolnya.

---

## Slide 1 - Judul Presentasi

### Judul
**Sistem Laporan Keuangan Sekolah Berbasis Aplikasi**

### Subjudul
Penjelasan logika perhitungan:
- Arus Kas Bulanan
- Dashboard Tahunan Monitoring Anggaran
- Pivot Cash & Bank
- Pivot Kas Kecil

### Narasi Presentasi
Pada sesi ini saya akan menjelaskan bagaimana aplikasi menghitung laporan keuangan sekolah secara otomatis. Fokus penjelasan bukan hanya pada tampilan laporan, tetapi pada logika perhitungan di baliknya, supaya yayasan bisa menilai bahwa angka yang muncul benar-benar berasal dari transaksi yang tercatat dan dapat dipertanggungjawabkan.

---

## Slide 2 - Tujuan Sistem Laporan

### Isi Slide
- Mencatat transaksi keuangan harian secara terstruktur
- Mengubah transaksi menjadi laporan otomatis
- Mengurangi ketergantungan pada rekap manual
- Menjaga konsistensi antara transaksi, saldo, dan laporan
- Menyediakan bahan kontrol untuk bendahara, kepala sekolah, dan yayasan

### Narasi Presentasi
Sistem ini dibangun bukan hanya untuk input transaksi, tetapi untuk memastikan setiap transaksi yang sudah masuk bisa langsung membentuk laporan yang konsisten. Jadi operator tidak perlu menghitung ulang secara manual. Dengan begitu, risiko salah jumlah, salah pindah angka, atau rekap ganda bisa ditekan.

---

## Slide 3 - Sumber Data Laporan

### Isi Slide
Laporan mengambil data dari tabel utama berikut:
- `jurnal_kas`
  Untuk transaksi masuk dan keluar utama
- `kas_kecil`
  Untuk pengeluaran operasional kecil
- `pengisian_kas_kecil`
  Untuk pengisian dana ke kas kecil
- `saldo_kas_bulanan`
  Untuk saldo awal dan status kunci per bulan
- `anggaran`
  Untuk target anggaran tahunan per akun

### Narasi Presentasi
Semua laporan di sistem berasal dari sumber data yang jelas. Jadi bukan hasil input manual ulang. `Jurnal kas` menjadi sumber utama penerimaan dan pengeluaran besar. `Kas kecil` mencatat pengeluaran operasional harian dalam nominal kecil. `Saldo kas bulanan` dipakai untuk menjaga kesinambungan saldo antar bulan. Sementara `anggaran` dipakai untuk membandingkan realisasi dengan target tahunan.

---

## Slide 4 - Gambaran Umum Arus Kas Bulanan

### Isi Slide
Rumus utama arus kas bulanan:

`Saldo Akhir = Saldo Awal + Total Penerimaan - Total Pengeluaran`

Komponen utama:
- Saldo awal bulan
- Penerimaan bulan berjalan
- Pengeluaran bulan berjalan
- Saldo akhir bulan

### Narasi Presentasi
Secara konsep, arus kas bulanan adalah laporan posisi uang sekolah pada satu bulan tertentu. Laporan ini menjawab empat pertanyaan: berapa saldo awal bulan, berapa uang masuk, berapa uang keluar, dan berapa sisa saldo akhir bulan. Semua logika perhitungannya mengikuti rumus kas yang sederhana dan mudah diaudit.

---

## Slide 5 - Cara Kerja Saldo Awal Bulanan

### Isi Slide
Urutan penentuan saldo awal:
1. Sistem cek apakah bulan tersebut sudah punya saldo awal tersimpan
2. Jika ada, saldo itu dipakai
3. Jika tidak ada, sistem melihat bulan sebelumnya
4. Jika bulan sebelumnya sudah dikunci, saldo akhir bulan sebelumnya menjadi saldo awal bulan ini
5. Jika belum ada acuan, saldo awal dianggap `0`

### Narasi Presentasi
Saldo awal tidak selalu diisi manual terus-menerus. Pada awal penggunaan, bendahara bisa menyimpan saldo awal bulan tertentu. Setelah itu, ketika bulan sudah selesai dan dikunci, saldo akhir bulan tersebut otomatis menjadi saldo awal bulan berikutnya. Mekanisme ini menjaga kesinambungan kas dari bulan ke bulan dan mencegah angka meloncat tanpa dasar.

---

## Slide 6 - Cara Hitung Penerimaan

### Isi Slide
Penerimaan diambil dari `jurnal_kas` dengan `jenis = masuk`

Yang dihitung:
- `cash`
- `bank`
- `cash + bank`

Penerimaan dikelompokkan menjadi:
- `B1` Penerimaan Pendidikan
- `B2` Penerimaan Non Pendidikan
- `B3` Pinjaman

### Narasi Presentasi
Semua transaksi yang dikategorikan sebagai pemasukan akan masuk ke bagian penerimaan. Sistem tidak hanya menjumlahkan totalnya, tetapi juga memisahkan apakah uang diterima secara tunai atau lewat bank. Selain itu, penerimaan juga dikelompokkan berdasarkan kategori akun agar laporan lebih mudah dibaca dan sesuai struktur keuangan sekolah.

---

## Slide 7 - Cara Hitung Pengeluaran

### Isi Slide
Pengeluaran diambil dari dua sumber:
- `jurnal_kas` dengan `jenis = keluar`
- `kas_kecil`

Keduanya digabung menjadi pengeluaran bulan berjalan

Lalu dipetakan ke kelompok:
- `C1` Gaji dan Tunjangan
- `C2` Beban Pegawai Lainnya
- `C3` Beban Operasional Kantor
- ...
- `C12` Biaya Lain-lain

### Narasi Presentasi
Pada sistem ini, pengeluaran tidak hanya dilihat dari jurnal utama. Pengeluaran yang dicatat di kas kecil juga ikut dihitung. Tujuannya supaya total pengeluaran bulanan benar-benar lengkap. Setelah digabung, pengeluaran dipetakan berdasarkan struktur kode akun, sehingga laporan tidak hanya menunjukkan angka total, tetapi juga komposisi beban per kelompok.

---

## Slide 8 - Saldo Akhir, Kas Besar, dan Kas Kecil

### Isi Slide
Rumus:
- `Selisih = Total Penerimaan - Total Pengeluaran`
- `Saldo Akhir Total = Saldo Awal Total + Selisih`

Pemisahan saldo:
- `Saldo Kas Kecil = Pengisian Kas Kecil - Pengeluaran Kas Kecil`
- `Saldo Kas Besar = Saldo Akhir Total - Saldo Kas Kecil`

Catatan penting:
- Pengisian kas kecil **bukan** pendapatan baru
- Pengisian kas kecil hanya perpindahan internal dari kas besar ke kas kecil

### Narasi Presentasi
Ini poin penting yang biasanya perlu ditekankan ke yayasan. Saat kas kecil diisi, total uang sekolah tidak bertambah. Uang hanya dipindahkan dari kas besar ke kas kecil. Karena itu, pengisian kas kecil tidak dihitung sebagai penerimaan. Yang berubah hanyalah komposisi tempat penyimpanan uang, bukan jumlah total uang sekolah.

---

## Slide 9 - Mekanisme Lock Bulan

### Isi Slide
Saat bulan dikunci:
1. Saldo awal bulan disimpan
2. Bulan ditandai final
3. Saldo akhir bulan dihitung
4. Saldo akhir itu dibawa menjadi saldo awal bulan berikutnya

Manfaat lock:
- Mencegah perubahan sembarangan pada bulan yang sudah final
- Menjaga konsistensi antar bulan
- Memudahkan rekonsiliasi dan pelaporan ke yayasan

### Narasi Presentasi
Fitur lock bulan adalah kontrol akuntabilitas. Begitu laporan bulan tertentu sudah dicek dan dianggap final, bulan itu bisa dikunci. Setelah terkunci, sistem menganggap angka bulan tersebut sebagai dasar resmi untuk bulan berikutnya. Dengan demikian, perubahan data lama tidak akan merusak kesinambungan laporan.

---

## Slide 10 - Dashboard Tahunan Monitoring Anggaran

### Isi Slide
Fungsi dashboard tahunan:
- Membandingkan realisasi dengan target anggaran
- Melihat akumulasi pengeluaran dan pendapatan per akun
- Menilai kesehatan pelaksanaan anggaran
- Memberi sinyal akun yang aman, perlu perhatian, atau kritis

Sumber data:
- `jurnal_kas`
- `kas_kecil`
- `anggaran`
- `saldo_kas_bulanan`

### Narasi Presentasi
Kalau arus kas bulanan menjawab pertanyaan “uang kita bulan ini bagaimana?”, maka dashboard tahunan menjawab pertanyaan “pelaksanaan anggaran kita sehat atau tidak?”. Dashboard ini mengumpulkan seluruh realisasi per akun selama satu tahun dan membandingkannya dengan target anggaran yang sudah ditetapkan.

---

## Slide 11 - Cara Hitung Dashboard Tahunan

### Isi Slide
Per akun, sistem menghitung:
- Realisasi per bulan
- Akumulasi satu tahun
- Anggaran tahunan
- Persentase realisasi terhadap anggaran
- Selisih realisasi dan anggaran

Rumus:
- `Akumulasi = total Januari sampai Desember`
- `Persentase = Akumulasi / Anggaran x 100%`
- `Selisih = Akumulasi - Anggaran`

### Narasi Presentasi
Setiap akun diperlakukan seperti unit kontrol tersendiri. Sistem menghitung berapa realisasi Januari, Februari, dan seterusnya, lalu menjumlahkannya menjadi akumulasi tahunan. Setelah itu, angka itu dibandingkan dengan anggaran. Dari perbandingan itu, sistem bisa memberi gambaran apakah akun tersebut masih aman, mendekati batas, atau sudah melewati target.

---

## Slide 12 - Logika Status pada Dashboard Tahunan

### Isi Slide
Untuk akun pendapatan:
- `>= 100%` = baik
- `>= 80%` = perhatian
- `< 80%` = belum tercapai

Untuk akun beban:
- `< 80%` = aman
- `>= 80%` = perhatian
- `>= 100%` = kritis

### Narasi Presentasi
Logika status dibuat berbeda antara pendapatan dan beban. Untuk pendapatan, makin tinggi realisasi justru makin baik. Tetapi untuk beban, jika realisasi sudah mendekati atau melewati anggaran, itu menjadi sinyal perhatian. Dengan cara ini, yayasan bisa membaca dashboard tidak hanya sebagai kumpulan angka, tetapi sebagai alat kontrol kebijakan.

---

## Slide 13 - Pivot Cash & Bank

### Isi Slide
Tujuan Pivot Cash & Bank:
- Memisahkan transaksi berdasarkan bentuk uang
- Menunjukkan per akun berapa yang masuk/keluar lewat `cash`
- Menunjukkan per akun berapa yang masuk/keluar lewat `bank`
- Menampilkan grand total gabungan

Rumus per akun:
- `Total Cash = SUM(cash)`
- `Total Bank = SUM(bank)`
- `Total = SUM(cash + bank)`

### Narasi Presentasi
Laporan ini berguna untuk melihat struktur transaksi per akun. Yayasan dapat melihat apakah suatu akun lebih banyak bergerak lewat tunai atau lewat bank. Ini penting untuk rekonsiliasi, pengawasan internal, dan juga untuk menilai apakah pola transaksi sudah sehat dari sisi administrasi.

---

## Slide 14 - Pivot Kas Kecil

### Isi Slide
Fungsi laporan:
- Menunjukkan total pengisian kas kecil
- Menunjukkan total pengeluaran kas kecil
- Menunjukkan saldo kas kecil
- Menampilkan penggunaan kas kecil per akun
- Menunjukkan saldo berjalan setelah tiap transaksi

Rumus utama:
- `Saldo Kas Kecil = Total Pengisian - Total Pengeluaran`

### Narasi Presentasi
Kas kecil biasanya menjadi titik rawan karena nominalnya kecil tetapi frekuensinya tinggi. Karena itu, laporan pivot kas kecil dibuat lebih rinci. Bukan hanya total, tetapi juga pola pengeluaran per akun dan saldo berjalan. Dengan saldo berjalan, bendahara bisa menelusuri pada titik mana kas kecil menipis dan apakah pengisian dilakukan secara wajar.

---

## Slide 15 - Saldo Berjalan Kas Kecil

### Isi Slide
Urutan perhitungan saldo berjalan:
1. Semua pengisian kas kecil disusun berdasarkan tanggal
2. Semua pengeluaran kas kecil juga disusun berdasarkan tanggal
3. Pengisian menambah saldo
4. Pengeluaran mengurangi saldo
5. Saldo setelah pengeluaran dicatat per transaksi

### Narasi Presentasi
Saldo berjalan pada kas kecil penting karena menunjukkan perjalanan saldo dari waktu ke waktu, bukan hanya angka akhir. Jadi kalau ada pertanyaan “setelah transaksi ini, kas kecil sisa berapa?”, sistem bisa menjawabnya. Ini membantu audit operasional dan memudahkan pembuktian urutan penggunaan dana.

---

## Slide 16 - Hubungan Antar Laporan

### Isi Slide
- `Arus Kas Bulanan`
  Menjawab posisi kas sekolah per bulan
- `Dashboard Tahunan`
  Menjawab posisi realisasi vs anggaran per tahun
- `Pivot Cash & Bank`
  Menjawab komposisi transaksi tunai vs bank
- `Pivot Kas Kecil`
  Menjawab kontrol rinci operasional kas kecil

### Narasi Presentasi
Empat laporan ini saling melengkapi. Arus kas bulanan menunjukkan posisi uang. Dashboard tahunan menunjukkan disiplin anggaran. Pivot cash dan bank menunjukkan bentuk transaksi. Pivot kas kecil menunjukkan kontrol operasional harian. Jadi yayasan tidak hanya melihat hasil akhir, tetapi juga cara uang bergerak dan cara anggaran dijalankan.

---

## Slide 17 - Nilai Tambah untuk Yayasan

### Isi Slide
- Angka laporan berasal langsung dari transaksi
- Ada kesinambungan saldo antar bulan
- Ada pembatasan bulan final melalui lock
- Ada pemisahan kas besar, bank, dan kas kecil
- Ada kontrol realisasi terhadap anggaran
- Ada laporan analitik untuk audit internal

### Narasi Presentasi
Nilai tambah utamanya adalah transparansi dan kontrol. Yayasan tidak harus menunggu rekap manual dari awal. Sistem sudah menyusun transaksi menjadi laporan yang saling terhubung. Ini mempercepat pelaporan, mengurangi kesalahan, dan mempermudah evaluasi keuangan sekolah secara periodik.

---

## Slide 18 - Penutup

### Isi Slide
Kesimpulan:
- Sistem tidak hanya mencatat transaksi
- Sistem membentuk laporan otomatis yang saling terhubung
- Sistem mendukung kontrol operasional dan evaluasi anggaran
- Sistem membantu akuntabilitas sekolah kepada yayasan

### Narasi Presentasi
Kesimpulannya, aplikasi ini bekerja sebagai sistem pencatatan sekaligus sistem kontrol. Setiap transaksi yang dicatat akan otomatis membentuk laporan bulanan, tahunan, dan analitik. Dengan begitu, sekolah memiliki alat yang lebih kuat untuk mempertanggungjawabkan keuangan kepada yayasan secara tertib, konsisten, dan mudah dijelaskan.

---

## Catatan Tambahan untuk Presenter

### Istilah yang sebaiknya dipakai saat presentasi
- Gunakan istilah `saldo awal`, `uang masuk`, `uang keluar`, `sisa saldo`
- Untuk yayasan, hindari istilah teknis berlebihan seperti `query`, `groupBy`, atau `computed`
- Fokuskan bahasa ke:
  - sumber angka
  - cara hitung
  - manfaat kontrol

### Kalimat singkat yang aman dipakai
- "Angka pada laporan berasal langsung dari transaksi yang diinput."
- "Saldo awal bulan berikutnya berasal dari saldo akhir bulan sebelumnya yang sudah difinalkan."
- "Kas kecil tetap dihitung, tetapi pengisian kas kecil bukan pendapatan baru."
- "Dashboard tahunan berfungsi untuk melihat apakah realisasi masih sesuai dengan anggaran."

