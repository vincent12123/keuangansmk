<?php

namespace App\Policies;

use App\Models\KartuSpp;
use App\Models\User;
use App\Services\Reports\SaldoKasService;

class KartuSppPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_kartu_spp');
    }

    public function view(User $user, KartuSpp $kartuSpp): bool
    {
        return $user->hasPermissionTo('view_kartu_spp');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_kartu_spp');
    }

    public function update(User $user, KartuSpp $kartuSpp): bool
    {
        return $user->hasPermissionTo('edit_kartu_spp')
            && ! app(SaldoKasService::class)->isLocked($kartuSpp->bulan, $kartuSpp->tahun);
    }

    public function delete(User $user, KartuSpp $kartuSpp): bool
    {
        // Hanya admin yang boleh hapus record pembayaran SPP
        return $user->isAdmin()
            && ! app(SaldoKasService::class)->isLocked($kartuSpp->bulan, $kartuSpp->tahun);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
