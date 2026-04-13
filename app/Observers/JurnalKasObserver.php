<?php

namespace App\Observers;

use App\Models\JurnalKas;
use App\Models\KartuSpp;
use App\Models\KodeAkun;
use App\Models\Siswa;

class JurnalKasObserver
{
    public function created(JurnalKas $jurnal): void
    {
        $this->syncKartuSpp($jurnal);
    }

    public function updated(JurnalKas $jurnal): void
    {
        $this->syncKartuSpp($jurnal);
    }

    public function deleted(JurnalKas $jurnal): void
    {
        $this->deleteLinkedKartuSpp($jurnal);
    }

    private function syncKartuSpp(JurnalKas $jurnal): void
    {
        $kodeAkun = $jurnal->relationLoaded('kodeAkun')
            ? $jurnal->kodeAkun
            : KodeAkun::find($jurnal->kode_akun_id);

        $bulanDibayar = session()->pull('spp_bulan_pending');

        if (blank($bulanDibayar) && $jurnal->exists) {
            $bulanDibayar = KartuSpp::where('jurnal_kas_id', $jurnal->id)
                ->pluck('bulan')
                ->all();
        }

        $bulanDibayar = collect($bulanDibayar ?: [$jurnal->bulan])
            ->map(fn ($bulan) => (int) $bulan)
            ->filter(fn (int $bulan) => $bulan >= 1 && $bulan <= 12)
            ->unique()
            ->values()
            ->all();

        if (! $jurnal->nis || ! $kodeAkun) {
            $this->deleteLinkedKartuSpp($jurnal);
            return;
        }

        if (! in_array($kodeAkun->kode, JurnalKas::SPP_ACCOUNT_CODES, true)) {
            $this->deleteLinkedKartuSpp($jurnal);
            return;
        }

        $siswa = Siswa::where('nis', $jurnal->nis)->first();

        if (! $siswa) {
            $this->deleteLinkedKartuSpp($jurnal);
            return;
        }

        $this->deleteLinkedKartuSpp($jurnal);

        foreach ($bulanDibayar as $bulan) {
            KartuSpp::updateOrCreate(
                [
                    'nis'   => $jurnal->nis,
                    'bulan' => (int) $bulan,
                    'tahun' => $jurnal->tahun,
                ],
                [
                    'nominal'       => $siswa->nominal_spp,
                    'tgl_bayar'     => $jurnal->tanggal,
                    'jurnal_kas_id' => $jurnal->id,
                    'keterangan'    => $jurnal->uraian,
                ]
            );
        }
    }

    private function deleteLinkedKartuSpp(JurnalKas $jurnal): void
    {
        if (! $jurnal->id) {
            return;
        }

        KartuSpp::where('jurnal_kas_id', $jurnal->id)->delete();
    }
}
