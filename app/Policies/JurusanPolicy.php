<?php

namespace App\Policies;

use App\Models\Jurusan;
use App\Models\User;

class JurusanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_jurusan');
    }

    public function view(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('view_jurusan');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_jurusan');
    }

    public function update(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('edit_jurusan');
    }

    public function delete(User $user, Jurusan $jurusan): bool
    {
        // Tidak boleh hapus jurusan yang masih punya siswa aktif
        if ($jurusan->siswa()->where('status', 'aktif')->exists()) {
            return false;
        }
        return $user->hasPermissionTo('delete_jurusan');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete_jurusan');
    }
}
