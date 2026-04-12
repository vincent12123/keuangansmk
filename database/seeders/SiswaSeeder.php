<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $kelasRplX = DB::table('kelas')
            ->join('jurusan', 'jurusan.id', '=', 'kelas.jurusan_id')
            ->where('kelas.nama_kelas', 'X RPL')
            ->select('kelas.id as kelas_id', 'jurusan.id as jurusan_id')
            ->first();

        $kelasTbsmXi = DB::table('kelas')
            ->join('jurusan', 'jurusan.id', '=', 'kelas.jurusan_id')
            ->where('kelas.nama_kelas', 'XI TBSM')
            ->select('kelas.id as kelas_id', 'jurusan.id as jurusan_id')
            ->first();

        if (! $kelasRplX || ! $kelasTbsmXi) {
            return;
        }

        $siswa = [
            [
                'nis' => '24010001',
                'nama' => 'Ahmad Fauzan',
                'kelas_id' => $kelasRplX->kelas_id,
                'jurusan_id' => $kelasRplX->jurusan_id,
                'angkatan' => 2024,
                'nominal_spp' => 400000,
                'status' => 'aktif',
                'nama_wali' => 'Budi Fauzi',
                'no_hp_wali' => '081234567801',
            ],
            [
                'nis' => '23020001',
                'nama' => 'Siti Rahma',
                'kelas_id' => $kelasTbsmXi->kelas_id,
                'jurusan_id' => $kelasTbsmXi->jurusan_id,
                'angkatan' => 2023,
                'nominal_spp' => 375000,
                'status' => 'aktif',
                'nama_wali' => 'Nur Aini',
                'no_hp_wali' => '081234567802',
            ],
        ];

        foreach ($siswa as $row) {
            $exists = DB::table('siswa')
                ->where('nis', $row['nis'])
                ->exists();

            DB::table('siswa')->updateOrInsert(
                ['nis' => $row['nis']],
                array_merge($row, [
                    'updated_at' => now(),
                    'created_at' => $exists
                        ? DB::table('siswa')->where('nis', $row['nis'])->value('created_at')
                        : now(),
                    'deleted_at' => null,
                ]),
            );
        }
    }
}
