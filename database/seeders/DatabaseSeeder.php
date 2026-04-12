<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Roles ───────────────────────────────────────────
        $admin        = Role::firstOrCreate(['name' => 'admin']);
        $bendahara    = Role::firstOrCreate(['name' => 'bendahara']);
        $kepalaSekolah = Role::firstOrCreate(['name' => 'kepala_sekolah']);

        // ─── Permissions ─────────────────────────────────────
        $permissions = [
            // Master Data
            'view_kode_akun', 'create_kode_akun', 'edit_kode_akun', 'delete_kode_akun',
            'view_jurusan',   'create_jurusan',   'edit_jurusan',   'delete_jurusan',
            'view_kelas',     'create_kelas',     'edit_kelas',     'delete_kelas',
            'view_siswa',     'create_siswa',     'edit_siswa',     'delete_siswa',

            // Jurnal Kas
            'view_jurnal_kas', 'create_jurnal_kas', 'edit_jurnal_kas', 'delete_jurnal_kas',

            // Kas Kecil
            'view_kas_kecil', 'create_kas_kecil', 'edit_kas_kecil', 'delete_kas_kecil',

            // Kartu SPP
            'view_kartu_spp', 'create_kartu_spp', 'edit_kartu_spp',

            // Laporan
            'view_laporan_arus_kas', 'view_laporan_kas_kecil',
            'view_laporan_spp', 'export_laporan',

            // Anggaran
            'view_anggaran', 'create_anggaran', 'edit_anggaran',

            // Dashboard
            'view_dashboard',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ─── Assign ke Role ──────────────────────────────────
        $admin->givePermissionTo(Permission::all());

        $bendahara->givePermissionTo([
            'view_kode_akun',
            'view_jurusan', 'view_kelas',
            'view_siswa', 'create_siswa', 'edit_siswa',
            'view_jurnal_kas', 'create_jurnal_kas', 'edit_jurnal_kas',
            'view_kas_kecil', 'create_kas_kecil', 'edit_kas_kecil',
            'view_kartu_spp', 'create_kartu_spp', 'edit_kartu_spp',
            'view_laporan_arus_kas', 'view_laporan_kas_kecil',
            'view_laporan_spp', 'export_laporan',
            'view_dashboard',
        ]);

        $kepalaSekolah->givePermissionTo([
            'view_jurnal_kas',
            'view_kas_kecil',
            'view_kartu_spp',
            'view_laporan_arus_kas', 'view_laporan_kas_kecil',
            'view_laporan_spp', 'export_laporan',
            'view_anggaran',
            'view_dashboard',
        ]);

        // ─── Seeder lainnya ──────────────────────────────────
        $this->call([
            KodeAkunSeeder::class,
            JurusanKelasSeeder::class,
        ]);

        // ─── User admin default ──────────────────────────────
        $user = User::firstOrCreate(
            ['email' => 'admin@karyabangsa.sch.id'],
            [
                'name'     => 'Admin Keuangan',
                'password' => bcrypt('Admin@1234'),
            ]
        );
        $user->assignRole('admin');

        // User bendahara demo
        $bendaharaUser = User::firstOrCreate(
            ['email' => 'bendahara@karyabangsa.sch.id'],
            [
                'name'     => 'Bendahara SMK',
                'password' => bcrypt('Bendahara@1234'),
            ]
        );
        $bendaharaUser->assignRole('bendahara');
    }
}
