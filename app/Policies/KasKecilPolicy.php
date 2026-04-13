<?php

namespace App\Policies;

use App\Models\KasKecil;
use App\Models\User;
use App\Services\Reports\SaldoKasService;

class KasKecilPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_kas_kecil');
    }

    public function view(User $user, KasKecil $kasKecil): bool
    {
        return $user->hasPermissionTo('view_kas_kecil');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_kas_kecil');
    }

    public function update(User $user, KasKecil $kasKecil): bool
    {
        if (! $user->hasPermissionTo('edit_kas_kecil')) {
            return false;
        }

        if ($user->isAdmin()) {
            return ! app(SaldoKasService::class)->isLocked($kasKecil->bulan, $kasKecil->tahun);
        }

        // Bendahara hanya bisa edit kas kecil bulan berjalan
        return $kasKecil->bulan === now()->month
            && $kasKecil->tahun === now()->year
            && ! app(SaldoKasService::class)->isLocked($kasKecil->bulan, $kasKecil->tahun);
    }

    public function delete(User $user, KasKecil $kasKecil): bool
    {
        if (! $user->hasPermissionTo('delete_kas_kecil')) {
            return false;
        }

        // Hanya admin yang bisa hapus
        return $user->isAdmin()
            && ! app(SaldoKasService::class)->isLocked($kasKecil->bulan, $kasKecil->tahun);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, KasKecil $kasKecil): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, KasKecil $kasKecil): bool
    {
        return false;
    }
}
