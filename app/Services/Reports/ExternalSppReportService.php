<?php

namespace App\Services\Reports;

use App\Models\ExternalSppMonthlyCache;
use App\Models\JurnalKas;
use App\Models\KodeAkun;
use App\Services\Integrations\SmartsisSppClient;
use App\Services\Integrations\SmartsisSppSyncService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ExternalSppReportService
{
    public function __construct(
        protected SmartsisSppClient $client,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) config('spp_integration.enabled')
            && filled(config('spp_integration.base_url'))
            && filled(config('spp_integration.token'));
    }

    public function getMonthlySummary(int $bulan, int $tahun): array
    {
        $syncedLocal = $this->getSyncedLocalSummary($bulan, $tahun);

        if ($syncedLocal !== null) {
            return $syncedLocal;
        }

        if (! $this->isEnabled()) {
            return $this->emptySummary($bulan, $tahun, false, 'disabled');
        }

        try {
            $payload = $this->client->getMonthlySummary($bulan, $tahun);
            $summary = $this->normalizePayload($payload, $bulan, $tahun, 'remote');
            $this->storeCache($summary);

            return $summary;
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengambil rekap SPP eksternal.', [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'message' => $exception->getMessage(),
            ]);

            if ((bool) config('spp_integration.use_cache_fallback', true)) {
                $cached = ExternalSppMonthlyCache::query()
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if ($cached) {
                    return $this->normalizeCachedRecord($cached);
                }
            }

            return $this->emptySummary($bulan, $tahun, true, 'unavailable');
        }
    }

    protected function normalizePayload(array $payload, int $bulan, int $tahun, string $source): array
    {
        $totals = $payload['totals'] ?? [];
        $rows = collect($payload['accounts'] ?? [])
            ->map(function (array $row): array {
                $kode = (string) ($row['kode_akun'] ?? '-');
                $nama = KodeAkun::query()->where('kode', $kode)->value('nama') ?? ('SPP ' . ($row['jurusan'] ?? ''));

                return [
                    'kode' => $kode,
                    'nama' => $nama,
                    'jurusan' => $row['jurusan'] ?? '-',
                    'kategori' => 'PENERIMAAN PENDIDIKAN',
                    'cash' => (float) ($row['cash_total'] ?? 0),
                    'bank' => (float) ($row['bank_total'] ?? 0),
                    'total' => (float) ($row['total'] ?? 0),
                    'payment_count' => (int) ($row['payment_count'] ?? 0),
                ];
            })
            ->sortBy('kode')
            ->values()
            ->all();

        return [
            'enabled' => true,
            'source' => $source,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'fetched_at' => now(),
            'total_cash' => (float) ($totals['cash_total'] ?? 0),
            'total_bank' => (float) ($totals['bank_total'] ?? 0),
            'total' => (float) ($totals['total'] ?? 0),
            'payment_count' => (int) ($totals['payment_count'] ?? 0),
            'rows' => $rows,
        ];
    }

    protected function normalizeCachedRecord(ExternalSppMonthlyCache $cached): array
    {
        $payload = $cached->payload ?? [];

        return [
            'enabled' => true,
            'source' => 'cache',
            'bulan' => $cached->bulan,
            'tahun' => $cached->tahun,
            'fetched_at' => $cached->fetched_at,
            'total_cash' => (float) $cached->total_cash,
            'total_bank' => (float) $cached->total_bank,
            'total' => (float) $cached->total_nominal,
            'payment_count' => (int) ($payload['payment_count'] ?? 0),
            'rows' => $payload['rows'] ?? [],
        ];
    }

    protected function storeCache(array $summary): void
    {
        ExternalSppMonthlyCache::query()->updateOrCreate(
            [
                'bulan' => $summary['bulan'],
                'tahun' => $summary['tahun'],
            ],
            [
                'total_cash' => $summary['total_cash'],
                'total_bank' => $summary['total_bank'],
                'total_nominal' => $summary['total'],
                'payload' => [
                    'rows' => $summary['rows'],
                    'payment_count' => $summary['payment_count'],
                ],
                'fetched_at' => $summary['fetched_at'] instanceof Carbon
                    ? $summary['fetched_at']
                    : now(),
            ],
        );
    }

    protected function emptySummary(int $bulan, int $tahun, bool $enabled, string $source): array
    {
        return [
            'enabled' => $enabled,
            'source' => $source,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'fetched_at' => null,
            'total_cash' => 0.0,
            'total_bank' => 0.0,
            'total' => 0.0,
            'payment_count' => 0,
            'rows' => [],
        ];
    }

    protected function getSyncedLocalSummary(int $bulan, int $tahun): ?array
    {
        $rows = JurnalKas::query()
            ->with('kodeAkun')
            ->where('external_source', SmartsisSppSyncService::SOURCE)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $accounts = $rows
            ->groupBy(fn (JurnalKas $jurnal) => $jurnal->kodeAkun?->kode ?? '-')
            ->map(function ($group, string $kode): array {
                /** @var JurnalKas $sample */
                $sample = $group->first();

                return [
                    'kode' => $kode,
                    'nama' => $sample->kodeAkun?->nama ?? '-',
                    'jurusan' => $sample->external_payload['jurusan'] ?? '-',
                    'kategori' => 'PENERIMAAN PENDIDIKAN',
                    'cash' => (float) $group->sum('cash'),
                    'bank' => (float) $group->sum('bank'),
                    'total' => (float) $group->sum(fn (JurnalKas $jurnal) => $jurnal->cash + $jurnal->bank),
                    'payment_count' => $group->count(),
                ];
            })
            ->sortBy('kode')
            ->values()
            ->all();

        return [
            'enabled' => true,
            'source' => 'database_sync',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'fetched_at' => $rows->max('external_synced_at'),
            'total_cash' => (float) $rows->sum('cash'),
            'total_bank' => (float) $rows->sum('bank'),
            'total' => (float) $rows->sum(fn (JurnalKas $jurnal) => $jurnal->cash + $jurnal->bank),
            'payment_count' => $rows->count(),
            'rows' => $accounts,
        ];
    }
}
