<?php

namespace App\Providers;

use App\Models\Anggaran;
use App\Models\JurnalKas;
use App\Models\KartuSpp;
use App\Models\KasKecil;
use App\Models\Kelas;
use App\Models\KodeAkun;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Policies\AnggaranPolicy;
use App\Policies\JurnalKasPolicy;
use App\Policies\KartuSppPolicy;
use App\Policies\KasKecilPolicy;
use App\Policies\KelasPolicy;
use App\Policies\KodeAkunPolicy;
use App\Policies\JurusanPolicy;
use App\Policies\SiswaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        KodeAkun::class => KodeAkunPolicy::class,
        Jurusan::class  => JurusanPolicy::class,
        Kelas::class    => KelasPolicy::class,
        Siswa::class    => SiswaPolicy::class,
        JurnalKas::class => JurnalKasPolicy::class,
        KasKecil::class  => KasKecilPolicy::class,
        KartuSpp::class  => KartuSppPolicy::class,
        Anggaran::class  => AnggaranPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
