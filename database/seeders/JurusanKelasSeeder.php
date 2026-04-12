<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JurusanKelasSeeder extends Seeder
{
    public function run(): void
    {
        $jurusan = [
            ['kode' => 'RPL',  'nama' => 'Rekayasa Perangkat Lunak',   'kode_akun' => '4.01.01.00'],
            ['kode' => 'TBSM', 'nama' => 'Teknik Bisnis Sepeda Motor', 'kode_akun' => '4.01.02.00'],
            ['kode' => 'PHT',  'nama' => 'Perhotelan',                 'kode_akun' => '4.01.03.00'],
        ];

        foreach ($jurusan as $j) {
            $id = DB::table('jurusan')->insertGetId(array_merge($j, [
                'aktif'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Buat kelas X, XI, XII untuk tiap jurusan
            foreach (['X', 'XI', 'XII'] as $tingkat) {
                DB::table('kelas')->insertOrIgnore([
                    'jurusan_id' => $id,
                    'tingkat'    => $tingkat,
                    'nama_kelas' => $tingkat . ' ' . $j['kode'],
                    'aktif'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
