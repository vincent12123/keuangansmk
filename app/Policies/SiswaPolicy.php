<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_siswa');
    }

    public function view(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('view_siswa');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_siswa');
    }

    public function update(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('edit_siswa');
    }

    public function delete(User $user, Siswa $siswa): bool
    {
        // Tidak boleh hard-delete siswa yang punya riwayat transaksi
        // Gunakan soft delete / ubah status saja
        return $user->hasPermissionTo('delete_siswa') && $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete_siswa') && $user->isAdmin();
    }

    public function restore(User $user, Siswa $siswa): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Siswa $siswa): bool
    {
        return false; // Tidak pernah boleh force delete data siswa
    }
}
