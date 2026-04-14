<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KodeAkunSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // =============================================
            // 4.xx — PENERIMAAN
            // =============================================
            // 4.01 - PENERIMAAN PENDIDIKAN
            ['kode' => '4.01.00.00', 'nama' => 'PENERIMAAN PENDIDIKAN', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '4.01.01.00', 'nama' => 'Jurusan RPL', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'SPP Jurusan', 'kas_kecil' => false],
            ['kode' => '4.01.02.00', 'nama' => 'Jurusan TBSM', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'SPP Jurusan', 'kas_kecil' => false],
            ['kode' => '4.01.03.00', 'nama' => 'Jurusan Perhotelan', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'SPP Jurusan', 'kas_kecil' => false],
            ['kode' => '4.01.04.00', 'nama' => 'Kursus', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'SPP Jurusan', 'kas_kecil' => false],
            ['kode' => '4.01.05.00', 'nama' => 'Seragam', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Penjualan', 'kas_kecil' => false],
            ['kode' => '4.01.06.00', 'nama' => 'Kegiatan / Bina Kepribadian', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Kegiatan', 'kas_kecil' => false],
            ['kode' => '4.01.07.00', 'nama' => 'Pendapatan Bunga atau Bagi Hasil Bank', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Pendapatan Lain', 'kas_kecil' => false],
            ['kode' => '4.01.08.00', 'nama' => 'Penjualan Buku Tulis', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Penjualan', 'kas_kecil' => false],
            ['kode' => '4.01.09.00', 'nama' => 'Penjualan Logo Sekolah', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Penjualan', 'kas_kecil' => false],
            ['kode' => '4.01.10.00', 'nama' => 'Penjualan Buku Paket', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Penjualan', 'kas_kecil' => false],
            ['kode' => '4.01.11.00', 'nama' => 'Pendapatan Sewa Ruangan', 'tipe' => 'pendapatan', 'kategori' => 'PENERIMAAN PENDIDIKAN', 'sub_kategori' => 'Pendapatan Lain', 'kas_kecil' => false],

            // 4.02 - PENDAPATAN NON PENDIDIKAN
            ['kode' => '4.02.00.00', 'nama' => 'PENDAPATAN NON PENDIDIKAN', 'tipe' => 'pendapatan', 'kategori' => 'PENDAPATAN NON PENDIDIKAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '4.02.01.00', 'nama' => 'Pendaftaran / Biaya Gedung', 'tipe' => 'pendapatan', 'kategori' => 'PENDAPATAN NON PENDIDIKAN', 'sub_kategori' => 'Non Pendidikan', 'kas_kecil' => false],
            ['kode' => '4.02.02.00', 'nama' => 'Sumbangan', 'tipe' => 'pendapatan', 'kategori' => 'PENDAPATAN NON PENDIDIKAN', 'sub_kategori' => 'Non Pendidikan', 'kas_kecil' => false],
            ['kode' => '4.02.03.00', 'nama' => 'Pendapatan Lain-Lain', 'tipe' => 'pendapatan', 'kategori' => 'PENDAPATAN NON PENDIDIKAN', 'sub_kategori' => 'Non Pendidikan', 'kas_kecil' => false],

            // 4.03 - PINJAMAN
            ['kode' => '4.03.00.00', 'nama' => 'PINJAMAN', 'tipe' => 'pendapatan', 'kategori' => 'PINJAMAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '4.03.01.00', 'nama' => 'Pinjaman Bank', 'tipe' => 'pendapatan', 'kategori' => 'PINJAMAN', 'sub_kategori' => 'Pinjaman', 'kas_kecil' => false],
            ['kode' => '4.03.02.00', 'nama' => 'Pinjaman Pihak Ketiga', 'tipe' => 'pendapatan', 'kategori' => 'PINJAMAN', 'sub_kategori' => 'Pinjaman', 'kas_kecil' => false],

            // =============================================
            // 5.xx — PENGELUARAN
            // =============================================
            // 5.01 - BEBAN PEGAWAI
            ['kode' => '5.01.00.00', 'nama' => 'BEBAN PEGAWAI', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.01.01.00', 'nama' => 'BEBAN GAJI', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Gaji', 'kas_kecil' => false],
            ['kode' => '5.01.01.01', 'nama' => 'Gaji Guru Tetap', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Gaji', 'kas_kecil' => false],
            ['kode' => '5.01.01.02', 'nama' => 'Gaji Guru Honorer', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Gaji', 'kas_kecil' => false],
            ['kode' => '5.01.01.03', 'nama' => 'Gaji Guru Kursus', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Gaji', 'kas_kecil' => false],
            ['kode' => '5.01.01.04', 'nama' => 'Gaji Tenaga Kependidikan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Gaji', 'kas_kecil' => false],
            ['kode' => '5.01.02.00', 'nama' => 'BEBAN TUNJANGAN', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Tunjangan', 'kas_kecil' => false],
            ['kode' => '5.01.02.01', 'nama' => 'Tunjangan Guru Tetap', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Tunjangan', 'kas_kecil' => false],
            ['kode' => '5.01.02.02', 'nama' => 'Tunjangan Guru Honorer', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Tunjangan', 'kas_kecil' => false],
            ['kode' => '5.01.03.00', 'nama' => 'BEBAN PEGAWAI LAINNYA', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],
            ['kode' => '5.01.03.01', 'nama' => 'BPJS Ketenagakerjaan dan Kesehatan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],
            ['kode' => '5.01.03.02', 'nama' => 'Honorarium / Insentif Lainnya', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],
            ['kode' => '5.01.03.03', 'nama' => 'Uang Makan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => true],
            ['kode' => '5.01.03.04', 'nama' => 'Uang Jasa & Pesangon', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],
            ['kode' => '5.01.03.05', 'nama' => 'Pakaian Seragam Pegawai', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],
            ['kode' => '5.01.03.10', 'nama' => 'Pinjaman Karyawan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],
            ['kode' => '5.01.03.11', 'nama' => 'Biaya Pegawai Lainnya', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEGAWAI', 'sub_kategori' => 'Pegawai Lainnya', 'kas_kecil' => false],

            // 5.02 - BEBAN OPERASIONAL KANTOR
            ['kode' => '5.02.00.00', 'nama' => 'BEBAN OPERASIONAL KANTOR', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.02.01.00', 'nama' => 'Bahan Bakar / Bensin / Parkir', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.02.00', 'nama' => 'Rekening Listrik', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Utilitas', 'kas_kecil' => false],
            ['kode' => '5.02.03.00', 'nama' => 'Rekening Telepon dan Internet', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Utilitas', 'kas_kecil' => false],
            ['kode' => '5.02.04.00', 'nama' => 'Rekening Air PDAM', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Utilitas', 'kas_kecil' => false],
            ['kode' => '5.02.05.00', 'nama' => 'Air Minum Galon / Botol / Gelas', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.06.00', 'nama' => 'Alat Tulis dan Keperluan Kantor (ATK)', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.07.00', 'nama' => 'Alat-Alat Listrik', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.08.00', 'nama' => 'Alat-Alat Elektronika', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.09.00', 'nama' => 'Alat-Alat Pertukangan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.10.00', 'nama' => 'Barang Cetakan & Kertas', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.11.00', 'nama' => 'Buku Referensi / Perpustakaan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => false],
            ['kode' => '5.02.12.00', 'nama' => 'Koran & Majalah', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => false],
            ['kode' => '5.02.13.00', 'nama' => 'Ekspedisi / Ongkir / Materai', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.14.00', 'nama' => 'Perlengkapan Rumah Tangga Kantor', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Operasional Harian', 'kas_kecil' => true],
            ['kode' => '5.02.15.00', 'nama' => 'Pembelian Buku Materi Pelajaran', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Pendidikan', 'kas_kecil' => false],
            ['kode' => '5.02.16.00', 'nama' => 'Pembelian Seragam', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN OPERASIONAL', 'sub_kategori' => 'Pendidikan', 'kas_kecil' => false],

            // 5.03 - BEBAN PEMASARAN
            ['kode' => '5.03.00.00', 'nama' => 'BEBAN PEMASARAN', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEMASARAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.03.01.00', 'nama' => 'Beban Iklan & Promosi', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEMASARAN', 'sub_kategori' => 'Pemasaran', 'kas_kecil' => false],
            ['kode' => '5.03.02.00', 'nama' => 'Beban Marketing Fee', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN PEMASARAN', 'sub_kategori' => 'Pemasaran', 'kas_kecil' => false],

            // 5.04 - BEBAN KONTRAK PELAYANAN
            ['kode' => '5.04.00.00', 'nama' => 'BEBAN KONTRAK PELAYANAN', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.04.01.00', 'nama' => 'BEBAN KONTRAK PELAYANAN GEDUNG', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Pelayanan Gedung', 'kas_kecil' => false],
            ['kode' => '5.04.01.01', 'nama' => 'Beban Pelayanan Kebersihan / Jasa Sampah / Laundry', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Pelayanan Gedung', 'kas_kecil' => true],
            ['kode' => '5.04.01.02', 'nama' => 'Beban Perbaikan dan Pemeliharaan Gedung', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Pelayanan Gedung', 'kas_kecil' => false],
            ['kode' => '5.04.01.03', 'nama' => 'Pekerjaan Pipa dan Saluran', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Pelayanan Gedung', 'kas_kecil' => false],
            ['kode' => '5.04.02.00', 'nama' => 'BEBAN PERBAIKAN', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Perbaikan', 'kas_kecil' => false],
            ['kode' => '5.04.02.04', 'nama' => 'Perbaikan Generator', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Perbaikan', 'kas_kecil' => false],
            ['kode' => '5.04.02.07', 'nama' => 'Perbaikan Komputer / Notebook / Printer', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Perbaikan', 'kas_kecil' => false],
            ['kode' => '5.04.02.12', 'nama' => 'Perbaikan Pompa Air / Sanitasi', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Perbaikan', 'kas_kecil' => true],
            ['kode' => '5.04.02.13', 'nama' => 'Perbaikan Lain-lain / Kunci / Aksesoris', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Perbaikan', 'kas_kecil' => true],
            ['kode' => '5.04.03.00', 'nama' => 'BEBAN JASA', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Jasa', 'kas_kecil' => false],
            ['kode' => '5.04.03.03', 'nama' => 'Beban Retribusi / Pajak', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Jasa', 'kas_kecil' => false],
            ['kode' => '5.04.03.04', 'nama' => 'Beban Administrasi Bank', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Jasa', 'kas_kecil' => false],
            ['kode' => '5.04.03.08', 'nama' => 'Beban Pelayanan Lain-lain', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KONTRAK PELAYANAN', 'sub_kategori' => 'Jasa', 'kas_kecil' => true],

            // 5.06 - PENGADAAN FASILITAS
            ['kode' => '5.06.00.00', 'nama' => 'PENGADAAN FASILITAS', 'tipe' => 'pengeluaran', 'kategori' => 'PENGADAAN FASILITAS', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.06.03.00', 'nama' => 'Fasilitas Perlengkapan Kelas', 'tipe' => 'pengeluaran', 'kategori' => 'PENGADAAN FASILITAS', 'sub_kategori' => 'Fasilitas', 'kas_kecil' => false],
            ['kode' => '5.06.04.00', 'nama' => 'Fasilitas Lain-lain / Sanitasi', 'tipe' => 'pengeluaran', 'kategori' => 'PENGADAAN FASILITAS', 'sub_kategori' => 'Fasilitas', 'kas_kecil' => true],

            // 5.07 - BEBAN KEGIATAN SISWA
            ['kode' => '5.07.00.00', 'nama' => 'BEBAN KEGIATAN SISWA', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KEGIATAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.07.01.00', 'nama' => 'Kegiatan Khusus Kesiswaan / Lomba / Praktek', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KEGIATAN', 'sub_kategori' => 'Kegiatan Siswa', 'kas_kecil' => true],

            // 5.08 - BEBAN KEGIATAN SOSIAL
            ['kode' => '5.08.00.00', 'nama' => 'BEBAN KEGIATAN SOSIAL', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KEGIATAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.08.05.00', 'nama' => 'Jamuan / Konsumsi Rapat & Kegiatan', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KEGIATAN', 'sub_kategori' => 'Sosial', 'kas_kecil' => true],
            ['kode' => '5.08.06.00', 'nama' => 'Hubungan Masyarakat / Humas', 'tipe' => 'pengeluaran', 'kategori' => 'BEBAN KEGIATAN', 'sub_kategori' => 'Sosial', 'kas_kecil' => false],

            // 5.09 - PERJALANAN DINAS
            ['kode' => '5.09.00.00', 'nama' => 'BEBAN PERJALANAN DINAS', 'tipe' => 'pengeluaran', 'kategori' => 'PERJALANAN DINAS', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.09.01.00', 'nama' => 'Perjalanan Dinas Lokal / Dalam Negeri', 'tipe' => 'pengeluaran', 'kategori' => 'PERJALANAN DINAS', 'sub_kategori' => 'Perjalanan Dinas', 'kas_kecil' => false],

            // 5.10 - PENDIDIKAN DAN LATIHAN
            ['kode' => '5.10.00.00', 'nama' => 'PENDIDIKAN DAN LATIHAN', 'tipe' => 'pengeluaran', 'kategori' => 'PENDIDIKAN DAN LATIHAN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.10.05.00', 'nama' => 'Beban Pendidikan, Penelitian dan Pengembangan', 'tipe' => 'pengeluaran', 'kategori' => 'PENDIDIKAN DAN LATIHAN', 'sub_kategori' => 'Diklat', 'kas_kecil' => false],

            // 5.11 - BIAYA LAIN-LAIN
            ['kode' => '5.11.00.00', 'nama' => 'BIAYA LAIN-LAIN', 'tipe' => 'pengeluaran', 'kategori' => 'BIAYA LAIN-LAIN', 'sub_kategori' => null, 'kas_kecil' => false],
            ['kode' => '5.11.01.00', 'nama' => 'Beban Kegiatan Kebudayaan / Dekorasi', 'tipe' => 'pengeluaran', 'kategori' => 'BIAYA LAIN-LAIN', 'sub_kategori' => 'Lain-lain', 'kas_kecil' => true],
            ['kode' => '5.11.05.00', 'nama' => 'Beban Sewa Gedung', 'tipe' => 'pengeluaran', 'kategori' => 'BIAYA LAIN-LAIN', 'sub_kategori' => 'Lain-lain', 'kas_kecil' => false],
            ['kode' => '5.11.06.00', 'nama' => 'Beban Lain-lain', 'tipe' => 'pengeluaran', 'kategori' => 'BIAYA LAIN-LAIN', 'sub_kategori' => 'Lain-lain', 'kas_kecil' => false],
        ];

        foreach ($data as $row) {
            DB::table('kode_akun')->insertOrIgnore(array_merge($row, [
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $codes = DB::table('kode_akun')->pluck('kode');

        $parentCodes = $codes
            ->filter(function (string $kode) use ($codes): bool {
                $prefix = substr($kode, 0, -2);

                return Str::endsWith($kode, '.00')
                    && $codes->contains(fn (string $candidate) => $candidate !== $kode && Str::startsWith($candidate, $prefix));
            })
            ->values()
            ->all();

        if ($parentCodes !== []) {
            DB::table('kode_akun')
                ->whereIn('kode', $parentCodes)
                ->update([
                    'sub_kategori' => null,
                    'updated_at' => now(),
                ]);
        }
    }
}
