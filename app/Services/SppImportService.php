<?php

namespace App\Services;

use App\Models\KartuSpp;
use App\Models\Siswa;
use App\Services\Reports\SaldoKasService;
use Illuminate\Support\Carbon;

class SppImportService
{
    public function syncPayment(
        Siswa $siswa,
        int $bulan,
        int $tahun,
        float $nominal,
        string $defaultKeterangan,
        array &$errors,
    ): bool {
        if ($nominal <= 0) {
            return false;
        }

        if (app(SaldoKasService::class)->isLocked($bulan, $tahun)) {
            $errors[] = sprintf(
                'Periode %s %d untuk NIS %s sedang dikunci, sehingga histori SPP tidak diimpor.',
                $this->monthName($bulan),
                $tahun,
                $siswa->nis,
            );

            return false;
        }

        $existing = KartuSpp::query()
            ->where('nis', $siswa->nis)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($existing?->jurnal_kas_id) {
            $errors[] = sprintf(
                'Pembayaran %s %d untuk NIS %s berasal dari jurnal kas dan tidak boleh ditimpa oleh import.',
                $this->monthName($bulan),
                $tahun,
                $siswa->nis,
            );

            return false;
        }

        $payload = [
            'nominal' => $nominal,
            'tgl_bayar' => $existing?->tgl_bayar?->toDateString() ?? $this->defaultPaymentDate($bulan, $tahun),
            'jurnal_kas_id' => null,
            'keterangan' => filled($existing?->keterangan) ? $existing->keterangan : $defaultKeterangan,
        ];

        if ($existing) {
            $existing->fill($payload);

            if ($existing->isDirty()) {
                $existing->save();
            }

            return true;
        }

        KartuSpp::query()->create([
            'nis' => $siswa->nis,
            'bulan' => $bulan,
            'tahun' => $tahun,
            ...$payload,
        ]);

        return true;
    }

    protected function defaultPaymentDate(int $bulan, int $tahun): string
    {
        return Carbon::create($tahun, $bulan, 1)->toDateString();
    }

    protected function monthName(int $bulan): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$bulan] ?? (string) $bulan;
    }
}
