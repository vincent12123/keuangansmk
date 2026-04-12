<?php

namespace App\Observers;

use App\Models\JurnalKas;
use App\Models\KartuSpp;
use App\Models\KodeAkun;
use App\Models\Siswa;

class JurnalKasObserver
{
    // Kode akun yang dianggap sebagai pembayaran SPP
    private array $kodeAkunSpp = [
        '4.01.01.00', // RPL
        '4.01.02.00', // TBSM
        '4.01.03.00', // Perhotelan
    ];

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
        // Hapus kartu SPP yang terhubung jika jurnal dihapus
        KartuSpp::where('jurnal_kas_id', $jurnal->id)->delete();
    }

    private function syncKartuSpp(JurnalKas $jurnal): void
    {
        // Hanya proses jika:
        // 1. Ada NIS (penerimaan dari siswa)
        // 2. Kode akun adalah SPP
        if (! $jurnal->nis || ! $jurnal->kode_akun_id) {
            return;
        }

        $kodeAkun = KodeAkun::find($jurnal->kode_akun_id);

        if (! $kodeAkun || ! in_array($kodeAkun->kode, $this->kodeAkunSpp, true)) {
            return;
        }

        $siswa = Siswa::where('nis', $jurnal->nis)->first();

        if (! $siswa) {
            return;
        }

        // Cek session bulan_spp dari form (diset via request)
        // Jika tidak ada, auto-detect dari uraian atau gunakan bulan transaksi
        $bulanDibayar = session('spp_bulan_' . $jurnal->id, [$jurnal->bulan]);

        foreach ($bulanDibayar as $bulan) {
            KartuSpp::updateOrCreate(
                [
                    'nis'   => $jurnal->nis,
                    'bulan' => (int) $bulan,
                    'tahun' => $jurnal->tahun,
                ],
                [
                    'nominal'       => $jurnal->cash + $jurnal->bank,
                    'tgl_bayar'     => $jurnal->tanggal,
                    'jurnal_kas_id' => $jurnal->id,
                    'keterangan'    => $jurnal->uraian,
                ]
            );
        }
    }
}
