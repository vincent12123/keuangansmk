<?php

namespace App\Services\Integrations;

use App\Models\KartuSpp;
use App\Models\JurnalKas;
use App\Models\KodeAkun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SmartsisSppSyncService
{
    public const SOURCE = 'smksmartsis_spp';

    public function __construct(
        protected SmartsisSppClient $client,
    ) {
    }

    public function syncMonth(int $bulan, int $tahun, int $actorId): array
    {
        $payments = $this->client->getAllPayments($bulan, $tahun);
        $references = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $deleted = 0;
        $errors = [];

        DB::transaction(function () use ($payments, $bulan, $tahun, $actorId, &$references, &$created, &$updated, &$skipped, &$deleted, &$errors) {
            foreach ($payments as $payment) {
                $reference = (string) ($payment['id'] ?? '');

                if ($reference === '') {
                    $skipped++;
                    $errors[] = 'Pembayaran tanpa ID dilewati.';
                    continue;
                }

                $references[] = $reference;

                $kodeAkun = KodeAkun::query()
                    ->where('kode', $payment['kode_akun'] ?? null)
                    ->first();

                if (! $kodeAkun) {
                    $skipped++;
                    $errors[] = 'Kode akun ' . ($payment['kode_akun'] ?? '-') . ' tidak ditemukan untuk pembayaran #' . $reference . '.';
                    continue;
                }

                $cash = (float) ($payment['cash_total'] ?? 0);
                $bank = (float) ($payment['bank_total'] ?? 0);

                if (($cash + $bank) <= 0) {
                    $skipped++;
                    continue;
                }

                $record = JurnalKas::withTrashed()->firstOrNew([
                    'external_source' => self::SOURCE,
                    'external_reference' => $reference,
                ]);

                $exists = $record->exists;

                if ($record->trashed()) {
                    $record->restore();
                }

                $record->fill([
                    'tanggal' => $payment['tanggal_bayar'],
                    'nis' => $payment['nis'] ?: null,
                    'nama_penyetor' => $payment['nama_siswa'] ?: ('SPP ' . ($payment['jurusan'] ?? '')),
                    'kelas_id' => null,
                    'kode_akun_id' => $kodeAkun->id,
                    'uraian' => $this->buildDescription($payment),
                    'cash' => $cash,
                    'bank' => $bank,
                    'created_by' => $record->created_by ?: $actorId,
                    'updated_by' => $actorId,
                    'external_payload' => $payment,
                    'external_synced_at' => now(),
                ]);

                $record->save();

                if ($exists) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            $deleted = JurnalKas::query()
                ->where('external_source', self::SOURCE)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->when($references !== [], fn ($query) => $query->whereNotIn('external_reference', $references))
                ->delete();

            $this->syncKartuSpp($payments);
        });

        return [
            'fetched' => count($payments),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'deleted' => $deleted,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    protected function buildDescription(array $payment): string
    {
        $bulanTagihan = $payment['bulan_tagihan'] ?? null;
        $tahunTagihan = $payment['tahun_tagihan'] ?? null;
        $jurusan = $payment['jurusan'] ?? '-';
        $nis = $payment['nis'] ?? '-';

        $label = 'SYNC SPP SMARTSIS';

        if ($bulanTagihan && $tahunTagihan) {
            $label .= " {$bulanTagihan}/{$tahunTagihan}";
        }

        return trim("{$label} - {$jurusan} - NIS {$nis}");
    }

    protected function syncKartuSpp(array $payments): void
    {
        $grouped = collect($payments)
            ->filter(fn (array $payment) => filled($payment['nis'] ?? null) && filled($payment['bulan_tagihan'] ?? null) && filled($payment['tahun_tagihan'] ?? null))
            ->groupBy(fn (array $payment) => implode(':', [
                $payment['nis'],
                $payment['bulan_tagihan'],
                $payment['tahun_tagihan'],
            ]));

        $references = [];

        foreach ($grouped as $reference => $items) {
            $references[] = $reference;
            $latest = $items
                ->sortByDesc(fn (array $payment) => $payment['tanggal_bayar'] ?? '')
                ->first();

            $linkedJournal = JurnalKas::query()
                ->where('external_source', self::SOURCE)
                ->where('external_reference', (string) ($latest['id'] ?? ''))
                ->first();

            KartuSpp::updateOrCreate(
                [
                    'nis' => $latest['nis'],
                    'bulan' => (int) $latest['bulan_tagihan'],
                    'tahun' => (int) $latest['tahun_tagihan'],
                ],
                [
                    'nominal' => (float) $items->sum(fn (array $payment) => (float) ($payment['jumlah_bayar'] ?? 0)),
                    'tgl_bayar' => Carbon::parse($latest['tanggal_bayar'])->toDateString(),
                    'jurnal_kas_id' => $linkedJournal?->id,
                    'keterangan' => 'SYNC SMARTSIS - ' . ($latest['jurusan'] ?? '-'),
                    'external_source' => self::SOURCE,
                    'external_reference' => $reference,
                    'external_payload' => [
                        'payment_ids' => $items->pluck('id')->values()->all(),
                        'jurusan' => $latest['jurusan'] ?? null,
                        'kelas' => $latest['kelas'] ?? null,
                    ],
                    'external_synced_at' => now(),
                ]
            );
        }

        $monthsByYear = collect($payments)
            ->filter(fn (array $payment) => filled($payment['bulan_tagihan'] ?? null) && filled($payment['tahun_tagihan'] ?? null))
            ->groupBy(fn (array $payment) => (int) $payment['tahun_tagihan'])
            ->map(fn ($items) => collect($items)->pluck('bulan_tagihan')->map(fn ($bulan) => (int) $bulan)->unique()->values()->all());

        foreach ($monthsByYear as $year => $months) {
            KartuSpp::query()
                ->where('external_source', self::SOURCE)
                ->where('tahun', (int) $year)
                ->whereIn('bulan', $months)
                ->when($references !== [], fn ($query) => $query->whereNotIn('external_reference', $references))
                ->delete();
        }
    }
}
