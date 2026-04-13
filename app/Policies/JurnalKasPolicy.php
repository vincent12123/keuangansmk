<?php

namespace App\Policies;

use App\Models\JurnalKas;
use App\Models\User;
use App\Services\Reports\SaldoKasService;
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
            return ! app(SaldoKasService::class)->isLocked($jurnal->bulan, $jurnal->tahun);
        }

        return ($jurnal->created_at?->gte(now()->subDays(3)) ?? false)
            && ! app(SaldoKasService::class)->isLocked($jurnal->bulan, $jurnal->tahun);
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

        return ! app(SaldoKasService::class)->isLocked($jurnal->bulan, $jurnal->tahun);
    }

    public function deleteAny(User $user): bool
    {
        return false;
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
