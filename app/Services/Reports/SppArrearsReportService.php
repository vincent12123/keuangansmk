<?php

namespace App\Services\Reports;

use App\Models\ExternalSppArrear;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KartuSpp;
use App\Models\Siswa;
use App\Services\Integrations\SmartsisReferenceSyncService;
use App\Services\Integrations\SmartsisSppClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class SppArrearsReportService
{
    public function build(int $bulan, int $tahun, int|string|null $jurusanId = null, int|string|null $kelasId = null): array
    {
        if ((bool) config('spp_integration.enabled')) {
            $local = $this->buildFromLocalSynced($bulan, $tahun, $jurusanId, $kelasId);

            if ($local !== null) {
                return $local;
            }

            return $this->buildFromSmartsis($bulan, $tahun, $jurusanId, $kelasId);
        }

        $baseQuery = Siswa::query()
            ->aktif()
            ->with(['kelas', 'jurusan'])
            ->when($jurusanId, fn (Builder $query) => $query->where('jurusan_id', $jurusanId))
            ->when($kelasId, fn (Builder $query) => $query->where('kelas_id', $kelasId));

        $allStudents = $baseQuery->get();
        $paidNis = KartuSpp::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('nis')
            ->all();

        $arrears = $allStudents
            ->reject(fn (Siswa $siswa) => in_array($siswa->nis, $paidNis, true))
            ->values();

        $totalAktif = $allStudents->count();
        $totalBelumBayar = $arrears->count();
        $totalSudahBayar = $totalAktif - $totalBelumBayar;
        $totalNominal = (float) $arrears->sum('nominal_spp');
        $totalNominalMasuk = (float) KartuSpp::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($jurusanId || $kelasId, function ($query) use ($jurusanId, $kelasId) {
                $query->whereIn('nis', Siswa::query()
                    ->aktif()
                    ->when($jurusanId, fn (Builder $builder) => $builder->where('jurusan_id', $jurusanId))
                    ->when($kelasId, fn (Builder $builder) => $builder->where('kelas_id', $kelasId))
                    ->pluck('nis'));
            })
            ->sum('nominal');

        $statsPerJurusan = $allStudents
            ->groupBy('jurusan_id')
            ->map(function ($students) use ($paidNis): array {
                $total = $students->count();
                $sudah = $students->filter(fn (Siswa $siswa) => in_array($siswa->nis, $paidNis, true))->count();
                $belum = $total - $sudah;
                $sample = $students->first();

                return [
                    'jurusan' => $sample?->jurusan?->nama ?? '-',
                    'kode' => $sample?->jurusan?->kode ?? '-',
                    'total' => $total,
                    'sudah_bayar' => $sudah,
                    'belum_bayar' => $belum,
                    'persen_lunas' => $total > 0 ? round(($sudah / $total) * 100, 1) : 0,
                ];
            })
            ->sortBy('kode')
            ->values();

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_siswa_aktif' => $totalAktif,
            'total_sudah_bayar' => $totalSudahBayar,
            'total_belum_bayar' => $totalBelumBayar,
            'persen_sudah_bayar' => $totalAktif > 0 ? round(($totalSudahBayar / $totalAktif) * 100, 2) : 0,
            'persen_belum_bayar' => $totalAktif > 0 ? round(($totalBelumBayar / $totalAktif) * 100, 2) : 0,
            'total_nominal_masuk' => $totalNominalMasuk,
            'total_nominal_tunggakan' => $totalNominal,
            'stats_per_jurusan' => $statsPerJurusan,
            'rows' => $arrears->map(function (Siswa $siswa): array {
                return [
                    'nis' => $siswa->nis,
                    'nama' => $siswa->nama,
                    'kelas' => $siswa->kelas?->nama_kelas ?? '-',
                    'jurusan' => $siswa->jurusan?->kode ?? '-',
                    'nama_jurusan' => $siswa->jurusan?->nama ?? '-',
                    'nominal_spp' => (float) $siswa->nominal_spp,
                    'no_hp_wali' => $siswa->no_hp_wali,
                    'nama_wali' => $siswa->nama_wali,
                ];
            })->sortBy([
                ['jurusan', 'asc'],
                ['kelas', 'asc'],
                ['nama', 'asc'],
            ])->values(),
        ];
    }

    public function getIntegrationFilterOptions(): array
    {
        if (! (bool) config('spp_integration.enabled')) {
            return [
                'jurusan' => [],
                'kelas' => [],
            ];
        }

        $jurusan = Jurusan::query()
            ->where('external_source', SmartsisReferenceSyncService::SOURCE)
            ->where('aktif', true)
            ->orderBy('nama')
            ->pluck('nama', 'nama')
            ->all();

        $kelas = Kelas::query()
            ->where('external_source', SmartsisReferenceSyncService::SOURCE)
            ->where('aktif', true)
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas', 'nama_kelas')
            ->all();

        if ($jurusan !== [] || $kelas !== []) {
            return [
                'jurusan' => $jurusan,
                'kelas' => $kelas,
            ];
        }

        try {
            $students = app(SmartsisSppClient::class)->getAllActiveStudents();

            $jurusan = collect($students)
                ->pluck('jurusan')
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn (string $name) => [$name => $name])
                ->all();

            $kelas = collect($students)
                ->pluck('kelas')
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(fn (string $name) => [$name => $name])
                ->all();

            return [
                'jurusan' => $jurusan,
                'kelas' => $kelas,
            ];
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengambil opsi filter tunggakan SmartSIS.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'jurusan' => [],
                'kelas' => [],
            ];
        }
    }

    protected function buildFromLocalSynced(int $bulan, int $tahun, int|string|null $jurusanId = null, int|string|null $kelasId = null): ?array
    {
        $arrearsExists = ExternalSppArrear::query()
            ->where('external_source', SmartsisReferenceSyncService::SOURCE)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->exists();

        $studentsExists = Siswa::query()
            ->where('external_source', SmartsisReferenceSyncService::SOURCE)
            ->where('status', 'aktif')
            ->exists();

        if (! $arrearsExists && ! $studentsExists) {
            return null;
        }

        $jurusanName = is_numeric((string) $jurusanId) && $jurusanId !== null
            ? Jurusan::query()->whereKey((int) $jurusanId)->value('nama')
            : ($jurusanId ?: null);
        $kelasName = is_numeric((string) $kelasId) && $kelasId !== null
            ? Kelas::query()->whereKey((int) $kelasId)->value('nama_kelas')
            : ($kelasId ?: null);

        $studentQuery = Siswa::query()
            ->aktif()
            ->with(['kelas', 'jurusan'])
            ->where('external_source', SmartsisReferenceSyncService::SOURCE)
            ->when($jurusanName, fn (Builder $query) => $query->whereHas('jurusan', fn (Builder $builder) => $builder->where('nama', $jurusanName)))
            ->when($kelasName, fn (Builder $query) => $query->whereHas('kelas', fn (Builder $builder) => $builder->where('nama_kelas', $kelasName)));

        $students = $studentQuery->get();
        $studentNis = $students->pluck('nis')->filter()->all();

        $arrearsQuery = ExternalSppArrear::query()
            ->where('external_source', SmartsisReferenceSyncService::SOURCE)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($jurusanName, fn ($query) => $query->where('jurusan', $jurusanName))
            ->when($kelasName, fn ($query) => $query->where('kelas', $kelasName));

        if ($studentNis !== []) {
            $arrearsQuery->whereIn('nis', $studentNis);
        }

        $arrears = $arrearsQuery->get();
        $paidNis = KartuSpp::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($studentNis !== [], fn ($query) => $query->whereIn('nis', $studentNis))
            ->pluck('nis')
            ->unique()
            ->all();

        $arrearsNis = $arrears->pluck('nis')->filter()->unique()->all();
        $totalAktif = $students->count();
        $totalBelumBayar = count($arrearsNis);
        $totalSudahBayar = max($totalAktif - $totalBelumBayar, 0);
        $totalNominalTunggakan = (float) $arrears->sum('nominal_spp');
        $totalNominalMasuk = (float) KartuSpp::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($studentNis !== [], fn ($query) => $query->whereIn('nis', $studentNis))
            ->sum('nominal');

        $statsPerJurusan = $students
            ->groupBy(fn (Siswa $siswa) => $siswa->jurusan?->nama ?? '-')
            ->map(function ($group, string $jurusan) use ($arrearsNis): array {
                $total = $group->count();
                $belum = $group->filter(fn (Siswa $siswa) => in_array($siswa->nis, $arrearsNis, true))->count();
                $sudah = max($total - $belum, 0);
                $kode = $group->first()?->jurusan?->kode ?? $jurusan;

                return [
                    'jurusan' => $jurusan,
                    'kode' => $kode,
                    'total' => $total,
                    'sudah_bayar' => $sudah,
                    'belum_bayar' => $belum,
                    'persen_lunas' => $total > 0 ? round(($sudah / $total) * 100, 1) : 0,
                ];
            })
            ->sortBy('jurusan')
            ->values()
            ->all();

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_siswa_aktif' => $totalAktif,
            'total_sudah_bayar' => $totalSudahBayar,
            'total_belum_bayar' => $totalBelumBayar,
            'persen_sudah_bayar' => $totalAktif > 0 ? round(($totalSudahBayar / $totalAktif) * 100, 2) : 0,
            'persen_belum_bayar' => $totalAktif > 0 ? round(($totalBelumBayar / $totalAktif) * 100, 2) : 0,
            'total_nominal_masuk' => $totalNominalMasuk,
            'total_nominal_tunggakan' => $totalNominalTunggakan,
            'stats_per_jurusan' => $statsPerJurusan,
            'rows' => $arrears->map(function (ExternalSppArrear $row): array {
                return [
                    'nis' => $row->nis ?: '-',
                    'nama' => $row->nama ?: '-',
                    'kelas' => $row->kelas ?: '-',
                    'jurusan' => $row->jurusan ?: '-',
                    'nama_jurusan' => $row->jurusan ?: '-',
                    'nominal_spp' => (float) $row->nominal_spp,
                    'no_hp_wali' => $row->no_hp_wali,
                    'nama_wali' => $row->nama_wali,
                ];
            })->sortBy([
                ['jurusan', 'asc'],
                ['kelas', 'asc'],
                ['nama', 'asc'],
            ])->values()->all(),
        ];
    }

    protected function buildFromSmartsis(int $bulan, int $tahun, int|string|null $jurusanId = null, int|string|null $kelasId = null): array
    {
        $jurusanName = is_numeric((string) $jurusanId) && $jurusanId !== null
            ? Jurusan::query()->whereKey((int) $jurusanId)->value('nama')
            : ($jurusanId ?: null);
        $kelasName = is_numeric((string) $kelasId) && $kelasId !== null
            ? Kelas::query()->whereKey((int) $kelasId)->value('nama_kelas')
            : ($kelasId ?: null);

        try {
            return app(SmartsisSppClient::class)->getArrearsReport($bulan, $tahun, $jurusanName, $kelasName);
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengambil laporan tunggakan SPP dari SmartSIS.', [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'message' => $exception->getMessage(),
            ]);

            return [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_siswa_aktif' => 0,
                'total_sudah_bayar' => 0,
                'total_belum_bayar' => 0,
                'persen_sudah_bayar' => 0,
                'persen_belum_bayar' => 0,
                'total_nominal_masuk' => 0,
                'total_nominal_tunggakan' => 0,
                'stats_per_jurusan' => [],
                'rows' => [],
            ];
        }
    }
}
