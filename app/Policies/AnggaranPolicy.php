<?php

namespace App\Policies;

use App\Models\Anggaran;
use App\Models\User;

class AnggaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_anggaran');
    }

    public function view(User $user, Anggaran $anggaran): bool
    {
        return $user->hasPermissionTo('view_anggaran');
    }

    public function create(User $user): bool
    {
        // Hanya admin yang bisa set anggaran
        return $user->hasPermissionTo('create_anggaran') && $user->isAdmin();
    }

    public function update(User $user, Anggaran $anggaran): bool
    {
        return $user->hasPermissionTo('edit_anggaran') && $user->isAdmin();
    }

    public function delete(User $user, Anggaran $anggaran): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
