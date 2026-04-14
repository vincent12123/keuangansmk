<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $kepalaSekolah = Role::firstOrCreate(['name' => 'kepala_sekolah']);

        $permissions = [
            'view_kode_akun', 'create_kode_akun', 'edit_kode_akun', 'delete_kode_akun',
            'view_jurusan', 'create_jurusan', 'edit_jurusan', 'delete_jurusan',
            'view_kelas', 'create_kelas', 'edit_kelas', 'delete_kelas',
            'view_siswa', 'create_siswa', 'edit_siswa', 'delete_siswa',
            'view_jurnal_kas', 'create_jurnal_kas', 'edit_jurnal_kas', 'delete_jurnal_kas',
            'view_kas_kecil', 'create_kas_kecil', 'edit_kas_kecil', 'delete_kas_kecil',
            'view_kartu_spp', 'create_kartu_spp', 'edit_kartu_spp',
            'view_laporan_arus_kas', 'view_laporan_kas_kecil',
            'view_laporan_spp', 'export_laporan',
            'view_audit_trail',
            'view_anggaran', 'create_anggaran', 'edit_anggaran',
            'view_dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin->syncPermissions(Permission::all());

        $bendahara->syncPermissions([
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

        $kepalaSekolah->syncPermissions([
            'view_jurnal_kas',
            'view_kas_kecil',
            'view_kartu_spp',
            'view_laporan_arus_kas', 'view_laporan_kas_kecil',
            'view_laporan_spp', 'export_laporan',
            'view_anggaran',
            'view_dashboard',
        ]);

        $this->call([
            KodeAkunSeeder::class,
            JurusanKelasSeeder::class,
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@karyabangsa.sch.id'],
            [
                'name' => 'Admin Keuangan',
                'password' => bcrypt('Admin@1234'),
            ],
        );
        $adminUser->syncRoles(['admin']);

        $bendaharaUser = User::firstOrCreate(
            ['email' => 'bendahara@karyabangsa.sch.id'],
            [
                'name' => 'Bendahara SMK',
                'password' => bcrypt('Bendahara@1234'),
            ],
        );
        $bendaharaUser->syncRoles(['bendahara']);

        $kepalaSekolahUser = User::firstOrCreate(
            ['email' => 'kepalasekolah@karyabangsa.sch.id'],
            [
                'name' => 'Kepala Sekolah',
                'password' => bcrypt('KepalaSekolah@1234'),
            ],
        );
        $kepalaSekolahUser->syncRoles(['kepala_sekolah']);

        if ($this->shouldSeedDemoData()) {
            $this->call([
                SiswaSeeder::class,
                DummyFinanceSeeder::class,
            ]);
        }
    }

    protected function shouldSeedDemoData(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL);
    }
}
