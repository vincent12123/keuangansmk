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
            ['kode' => 'HTL',  'nama' => 'Perhotelan',                 'kode_akun' => '4.01.03.00'],
        ];

        foreach ($jurusan as $j) {
            DB::table('jurusan')->updateOrInsert([
                'kode' => $j['kode'],
            ], array_merge($j, [
                'aktif'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $id = DB::table('jurusan')
                ->where('kode', $j['kode'])
                ->value('id');

            // Buat kelas X, XI, XII untuk tiap jurusan
            foreach (['X', 'XI', 'XII'] as $tingkat) {
                DB::table('kelas')->updateOrInsert([
                    'jurusan_id' => $id,
                    'tingkat'    => $tingkat,
                ], [
                    'nama_kelas' => $tingkat . ' ' . $j['kode'],
                    'aktif'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
