<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DummyFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetTransactionalData();

        $adminId = (int) DB::table('users')
            ->where('email', 'admin@karyabangsa.sch.id')
            ->value('id');

        if (! $adminId) {
            return;
        }

        $bulan = 4;
        $tahun = 2026;
        $now = now();

        $kodeAkunIds = DB::table('kode_akun')
            ->whereIn('kode', [
                '4.01.01.00', '4.01.02.00', '4.01.03.00',
                '4.01.05.00', '4.01.06.00', '4.02.02.00', '4.01.04.00',
                '5.01.01.01', '5.01.01.04', '5.02.02.00', '5.02.03.00', '5.03.01.00', '5.04.02.07',
                '5.02.01.00', '5.02.05.00', '5.02.06.00', '5.02.13.00',
                '5.08.05.00', '5.04.01.01', '5.04.02.13', '5.11.01.00',
            ])
            ->pluck('id', 'kode');

        $students = DB::table('siswa')
            ->join('jurusan', 'jurusan.id', '=', 'siswa.jurusan_id')
            ->select(
                'siswa.nis',
                'siswa.nama',
                'siswa.kelas_id',
                'siswa.nominal_spp',
                'jurusan.kode as jurusan_kode',
                'jurusan.kode_akun as spp_kode'
            )
            ->where('siswa.status', 'aktif')
            ->orderBy('jurusan.kode')
            ->orderBy('siswa.nis')
            ->get();

        $paidStudents = collect(['RPL', 'TBSM', 'HTL'])
            ->flatMap(fn (string $jurusanKode) => $students->where('jurusan_kode', $jurusanKode)->take(6))
            ->values();

        $kwitansiCounter = 1;

        foreach ($paidStudents as $index => $student) {
            $tanggal = Carbon::create($tahun, $bulan, 2 + $index);
            $cash = $index % 3 === 0 ? (float) $student->nominal_spp : 0.0;
            $bank = $cash > 0 ? 0.0 : (float) $student->nominal_spp;

            $jurnalId = DB::table('jurnal_kas')->insertGetId([
                'no_kwitansi' => str_pad((string) $kwitansiCounter, 6, '0', STR_PAD_LEFT),
                'tanggal' => $tanggal->toDateString(),
                'nis' => $student->nis,
                'nama_penyetor' => $student->nama,
                'kelas_id' => $student->kelas_id,
                'kode_akun_id' => $kodeAkunIds[$student->spp_kode] ?? null,
                'uraian' => "Pembayaran SPP April 2026 - {$student->nama}",
                'cash' => $cash,
                'bank' => $bank,
                'jenis' => 'masuk',
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_by' => $adminId,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);

            DB::table('kartu_spp')->updateOrInsert(
                [
                    'nis' => $student->nis,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                [
                    'nominal' => $student->nominal_spp,
                    'tgl_bayar' => $tanggal->toDateString(),
                    'jurnal_kas_id' => $jurnalId,
                    'keterangan' => 'SPP April 2026',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $kwitansiCounter++;
        }

        $additionalIncomes = [
            ['kode' => '4.01.05.00', 'tanggal' => 8, 'penyetor' => 'Penjualan Seragam', 'uraian' => 'Penerimaan penjualan seragam gelombang 1', 'cash' => 1250000, 'bank' => 0],
            ['kode' => '4.02.02.00', 'tanggal' => 10, 'penyetor' => 'Donatur Alumni', 'uraian' => 'Sumbangan pengembangan laboratorium', 'cash' => 0, 'bank' => 2000000],
            ['kode' => '4.01.06.00', 'tanggal' => 12, 'penyetor' => 'Kegiatan Sekolah', 'uraian' => 'Penerimaan kegiatan bina kepribadian', 'cash' => 750000, 'bank' => 750000],
            ['kode' => '4.01.04.00', 'tanggal' => 15, 'penyetor' => 'Kursus Tambahan', 'uraian' => 'Pendapatan kursus tambahan siswa', 'cash' => 900000, 'bank' => 0],
        ];

        foreach ($additionalIncomes as $row) {
            DB::table('jurnal_kas')->insert([
                'no_kwitansi' => str_pad((string) $kwitansiCounter, 6, '0', STR_PAD_LEFT),
                'tanggal' => Carbon::create($tahun, $bulan, $row['tanggal'])->toDateString(),
                'nis' => null,
                'nama_penyetor' => $row['penyetor'],
                'kelas_id' => null,
                'kode_akun_id' => $kodeAkunIds[$row['kode']] ?? null,
                'uraian' => $row['uraian'],
                'cash' => $row['cash'],
                'bank' => $row['bank'],
                'jenis' => 'masuk',
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_by' => $adminId,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);

            $kwitansiCounter++;
        }

        $expenses = [
            ['kode' => '5.01.01.01', 'tanggal' => 25, 'nama' => 'Payroll Guru Tetap', 'uraian' => 'Pembayaran gaji guru tetap April 2026', 'cash' => 0, 'bank' => 8500000],
            ['kode' => '5.01.01.04', 'tanggal' => 25, 'nama' => 'Payroll Tendik', 'uraian' => 'Pembayaran gaji tenaga kependidikan April 2026', 'cash' => 0, 'bank' => 4250000],
            ['kode' => '5.02.02.00', 'tanggal' => 20, 'nama' => 'PLN', 'uraian' => 'Pembayaran rekening listrik April 2026', 'cash' => 0, 'bank' => 1350000],
            ['kode' => '5.02.03.00', 'tanggal' => 20, 'nama' => 'Provider Internet', 'uraian' => 'Pembayaran internet dan telepon April 2026', 'cash' => 0, 'bank' => 850000],
            ['kode' => '5.03.01.00', 'tanggal' => 18, 'nama' => 'Vendor Promosi', 'uraian' => 'Biaya promosi penerimaan siswa baru', 'cash' => 1200000, 'bank' => 0],
            ['kode' => '5.04.02.07', 'tanggal' => 22, 'nama' => 'Teknisi Printer', 'uraian' => 'Perbaikan printer ruang TU', 'cash' => 650000, 'bank' => 0],
        ];

        foreach ($expenses as $row) {
            DB::table('jurnal_kas')->insert([
                'no_kwitansi' => null,
                'tanggal' => Carbon::create($tahun, $bulan, $row['tanggal'])->toDateString(),
                'nis' => null,
                'nama_penyetor' => $row['nama'],
                'kelas_id' => null,
                'kode_akun_id' => $kodeAkunIds[$row['kode']] ?? null,
                'uraian' => $row['uraian'],
                'cash' => $row['cash'],
                'bank' => $row['bank'],
                'jenis' => 'keluar',
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_by' => $adminId,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        DB::table('pengisian_kas_kecil')->insert([
            [
                'tanggal' => Carbon::create($tahun, $bulan, 5)->toDateString(),
                'nominal' => 2000000,
                'keterangan' => 'Pengisian awal kas kecil April 2026',
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_by' => $adminId,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'tanggal' => Carbon::create($tahun, $bulan, 18)->toDateString(),
                'nominal' => 1000000,
                'keterangan' => 'Top up kas kecil pertengahan bulan',
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_by' => $adminId,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);

        $pettyCashRows = [
            ['kode' => '5.02.01.00', 'tanggal' => 6, 'uraian' => 'Bensin antar dokumen dan parkir', 'nominal' => 180000],
            ['kode' => '5.02.05.00', 'tanggal' => 7, 'uraian' => 'Pembelian air galon kantor', 'nominal' => 120000],
            ['kode' => '5.02.06.00', 'tanggal' => 9, 'uraian' => 'Pembelian ATK ruang administrasi', 'nominal' => 350000],
            ['kode' => '5.02.13.00', 'tanggal' => 11, 'uraian' => 'Materai dan ongkos kirim dokumen', 'nominal' => 95000],
            ['kode' => '5.08.05.00', 'tanggal' => 14, 'uraian' => 'Konsumsi rapat bulanan yayasan', 'nominal' => 275000],
            ['kode' => '5.04.01.01', 'tanggal' => 16, 'uraian' => 'Biaya jasa kebersihan tambahan', 'nominal' => 300000],
            ['kode' => '5.04.02.13', 'tanggal' => 19, 'uraian' => 'Perbaikan kunci dan aksesoris kelas', 'nominal' => 210000],
            ['kode' => '5.11.01.00', 'tanggal' => 24, 'uraian' => 'Dekorasi kegiatan sekolah', 'nominal' => 160000],
        ];

        foreach ($pettyCashRows as $index => $row) {
            DB::table('kas_kecil')->insert([
                'no_ref' => 'K26-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'tanggal' => Carbon::create($tahun, $bulan, $row['tanggal'])->toDateString(),
                'kode_akun_id' => $kodeAkunIds[$row['kode']] ?? null,
                'uraian' => $row['uraian'],
                'nominal' => $row['nominal'],
                'bulan' => $bulan,
                'tahun' => $tahun,
                'created_by' => $adminId,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        DB::table('saldo_kas_bulanan')->updateOrInsert(
            ['bulan' => $bulan, 'tahun' => $tahun],
            [
                'saldo_awal_cash' => 5000000,
                'saldo_awal_bank' => 12000000,
                'is_locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $budgets = [
            '4.01.01.00' => 48000000,
            '4.01.02.00' => 45000000,
            '4.01.03.00' => 51000000,
            '4.01.04.00' => 12000000,
            '4.01.05.00' => 18000000,
            '4.01.06.00' => 15000000,
            '4.02.02.00' => 10000000,
            '5.01.01.01' => 102000000,
            '5.01.01.04' => 51000000,
            '5.02.02.00' => 16200000,
            '5.02.03.00' => 10200000,
            '5.03.01.00' => 15000000,
            '5.04.02.07' => 8000000,
            '5.02.01.00' => 4000000,
            '5.02.05.00' => 3600000,
            '5.02.06.00' => 5400000,
            '5.02.13.00' => 1800000,
            '5.08.05.00' => 4800000,
            '5.04.01.01' => 6000000,
            '5.04.02.13' => 3600000,
            '5.11.01.00' => 3000000,
        ];

        foreach ($budgets as $kode => $target) {
            DB::table('anggaran')->updateOrInsert(
                [
                    'kode_akun_id' => $kodeAkunIds[$kode] ?? null,
                    'tahun' => $tahun,
                ],
                [
                    'target' => $target,
                    'keterangan' => 'Anggaran dummy pengembangan 2026',
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    protected function resetTransactionalData(): void
    {
        DB::table('kartu_spp')->delete();
        DB::table('kas_kecil')->delete();
        DB::table('pengisian_kas_kecil')->delete();
        DB::table('jurnal_kas')->delete();
        DB::table('anggaran')->delete();
        DB::table('saldo_kas_bulanan')->delete();
    }
}
