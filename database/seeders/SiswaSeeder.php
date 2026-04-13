<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('siswa')->delete();

        $kelasMap = DB::table('kelas')
            ->join('jurusan', 'jurusan.id', '=', 'kelas.jurusan_id')
            ->select(
                'kelas.id as kelas_id',
                'kelas.nama_kelas',
                'kelas.tingkat',
                'jurusan.id as jurusan_id',
                'jurusan.kode as jurusan_kode'
            )
            ->orderBy('jurusan.kode')
            ->orderByRaw("FIELD(kelas.tingkat, 'X', 'XI', 'XII')")
            ->get()
            ->keyBy('nama_kelas');

        if ($kelasMap->isEmpty()) {
            return;
        }

        $distribution = [
            'X RPL' => 4,
            'XI RPL' => 3,
            'XII RPL' => 3,
            'X TBSM' => 4,
            'XI TBSM' => 3,
            'XII TBSM' => 3,
            'X HTL' => 4,
            'XI HTL' => 3,
            'XII HTL' => 3,
        ];

        $profiles = [
            'RPL' => [
                ['nama' => 'Ahmad Fauzan', 'wali' => 'Budi Fauzi'],
                ['nama' => 'Nabila Putri', 'wali' => 'Rina Wati'],
                ['nama' => 'Rizky Maulana', 'wali' => 'Dedi Maulana'],
                ['nama' => 'Salsa Anindya', 'wali' => 'Tri Aningsih'],
                ['nama' => 'Fajar Nugraha', 'wali' => 'Asep Nugraha'],
                ['nama' => 'Dinda Aulia', 'wali' => 'Nur Aisyah'],
                ['nama' => 'Bagas Pratama', 'wali' => 'Yanto Pratama'],
                ['nama' => 'Citra Lestari', 'wali' => 'Euis Lestari'],
                ['nama' => 'Yoga Saputra', 'wali' => 'Hendra Saputra'],
                ['nama' => 'Putri Maharani', 'wali' => 'Siti Mariam'],
            ],
            'TBSM' => [
                ['nama' => 'Siti Rahma', 'wali' => 'Nur Aini'],
                ['nama' => 'Galih Ramadhan', 'wali' => 'Ujang Rahman'],
                ['nama' => 'M. Ilham Akbar', 'wali' => 'Tatang Akbar'],
                ['nama' => 'Vina Oktaviani', 'wali' => 'Lilis Oktavia'],
                ['nama' => 'Aldi Firmansyah', 'wali' => 'Dadan Firmansyah'],
                ['nama' => 'Nurul Hidayah', 'wali' => 'Aam Hidayat'],
                ['nama' => 'Reza Prakoso', 'wali' => 'Maman Prakoso'],
                ['nama' => 'Novi Lestiana', 'wali' => 'Nining Lestari'],
                ['nama' => 'Dimas Kurniawan', 'wali' => 'Rudi Kurniawan'],
                ['nama' => 'Meysa Puspita', 'wali' => 'Yuli Puspita'],
            ],
            'HTL' => [
                ['nama' => 'Aulia Safitri', 'wali' => 'Rohayati'],
                ['nama' => 'Fikri Haikal', 'wali' => 'Dadang Haikal'],
                ['nama' => 'Tasya Maharani', 'wali' => 'Yayah Hasanah'],
                ['nama' => 'Raka Aditya', 'wali' => 'Slamet Aditya'],
                ['nama' => 'Nisa Khairunnisa', 'wali' => 'Dewi Khairani'],
                ['nama' => 'Iqbal Ramdani', 'wali' => 'Usep Ramdani'],
                ['nama' => 'Sheila Paramitha', 'wali' => 'Iis Paramita'],
                ['nama' => 'Farhan Ardiansyah', 'wali' => 'Eman Ardiansyah'],
                ['nama' => 'Keysa Amalia', 'wali' => 'Rina Marlina'],
                ['nama' => 'Abel Pranata', 'wali' => 'Yayan Pranata'],
            ],
        ];

        $nominalSpp = [
            'RPL' => 400000,
            'TBSM' => 375000,
            'HTL' => 425000,
        ];

        $angkatanByTingkat = [
            'X' => 2025,
            'XI' => 2024,
            'XII' => 2023,
        ];

        $jurusanOrder = [
            'RPL' => 1,
            'TBSM' => 2,
            'HTL' => 3,
        ];

        $profileIndex = [
            'RPL' => 0,
            'TBSM' => 0,
            'HTL' => 0,
        ];

        $sequenceByJurusan = [
            'RPL' => 1,
            'TBSM' => 1,
            'HTL' => 1,
        ];

        foreach ($distribution as $kelasName => $count) {
            $kelas = $kelasMap->get($kelasName);

            if (! $kelas) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $jurusanKode = $kelas->jurusan_kode;
                $profile = $profiles[$jurusanKode][$profileIndex[$jurusanKode]] ?? null;

                if (! $profile) {
                    continue;
                }

                $angkatan = $angkatanByTingkat[$kelas->tingkat];
                $nis = sprintf(
                    '%02d%02d%04d',
                    $angkatan % 100,
                    $jurusanOrder[$jurusanKode],
                    $sequenceByJurusan[$jurusanKode]
                );

                DB::table('siswa')->insert([
                    'nis' => $nis,
                    'nama' => $profile['nama'],
                    'kelas_id' => $kelas->kelas_id,
                    'jurusan_id' => $kelas->jurusan_id,
                    'angkatan' => $angkatan,
                    'nominal_spp' => $nominalSpp[$jurusanKode],
                    'status' => 'aktif',
                    'nama_wali' => $profile['wali'],
                    'no_hp_wali' => '08123' . str_pad((string) (100000 + $sequenceByJurusan[$jurusanKode]), 6, '0', STR_PAD_LEFT),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);

                $profileIndex[$jurusanKode]++;
                $sequenceByJurusan[$jurusanKode]++;
            }
        }
    }
}
