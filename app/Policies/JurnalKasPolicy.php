<?php

namespace App\Policies;

use App\Models\JurnalKas;
use App\Models\User;
use Illuminate\Support\Carbon;

class JurnalKasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_jurnal_kas');
    }

    public function view(User $user, JurnalKas $jurnal): bool
    {
        return $user->hasPermissionTo('view_jurnal_kas');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_jurnal_kas');
    }

    public function update(User $user, JurnalKas $jurnal): bool
    {
        if (! $user->hasPermissionTo('edit_jurnal_kas')) {
            return false;
        }

        // Admin bisa edit kapan saja
        if ($user->isAdmin()) {
            return true;
        }

        // Bendahara hanya bisa edit transaksi bulan berjalan
        // atau maksimal 3 hari ke belakang
        return $jurnal->tanggal->gte(now()->subDays(3));
    }

    public function delete(User $user, JurnalKas $jurnal): bool
    {
        if (! $user->hasPermissionTo('delete_jurnal_kas')) {
            return false;
        }

        // Hanya admin yang bisa hapus
        if (! $user->isAdmin()) {
            return false;
        }

        // Tidak boleh hapus jika sudah ada di laporan bulan yang dikunci
        // (implementasi lanjutan: cek saldo_kas_bulanan.is_locked)
        return true;
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, JurnalKas $jurnal): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, JurnalKas $jurnal): bool
    {
        return false; // Data keuangan tidak boleh dihapus permanen
    }
}
