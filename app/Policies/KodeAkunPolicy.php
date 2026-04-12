<?php

namespace App\Policies;

use App\Models\Jurusan;
use App\Models\KodeAkun;
use App\Models\User;

class KodeAkunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_kode_akun');
    }

    public function view(User $user, KodeAkun $kodeAkun): bool
    {
        return $user->hasPermissionTo('view_kode_akun');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_kode_akun');
    }

    public function update(User $user, KodeAkun $kodeAkun): bool
    {
        return $user->hasPermissionTo('edit_kode_akun');
    }

    public function delete(User $user, KodeAkun $kodeAkun): bool
    {
        if (! $user->hasPermissionTo('delete_kode_akun')) {
            return false;
        }

        if ($kodeAkun->jurnalKas()->withTrashed()->exists()) {
            return false;
        }

        if ($kodeAkun->kasKecil()->withTrashed()->exists()) {
            return false;
        }

        if ($kodeAkun->anggaran()->exists()) {
            return false;
        }

        if (Jurusan::where('kode_akun', $kodeAkun->kode)->exists()) {
            return false;
        }

        return true;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, KodeAkun $kodeAkun): bool
    {
        return false;
    }
}
