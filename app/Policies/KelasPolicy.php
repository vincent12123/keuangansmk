<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_kelas');
    }

    public function view(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('view_kelas');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_kelas');
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('edit_kelas');
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        // Tidak boleh hapus kelas yang masih punya siswa
        if ($kelas->siswa()->exists()) {
            return false;
        }
        return $user->hasPermissionTo('delete_kelas');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete_kelas');
    }
}
