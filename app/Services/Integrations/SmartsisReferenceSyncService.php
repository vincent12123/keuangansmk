<?php

namespace App\Services\Integrations;

use App\Models\ExternalSppArrear;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmartsisReferenceSyncService
{
    public const SOURCE = 'smksmartsis_spp';

    public function __construct(
        protected SmartsisSppClient $client,
    ) {
    }

    public function syncMasterData(): array
    {
        return $this->syncMasterDataFromRows($this->client->getAllActiveStudents());
    }

    public function syncMasterDataFromRows(array $students): array
    {
        $students = collect($students);

        $createdJurusan = 0;
        $updatedJurusan = 0;
        $createdKelas = 0;
        $updatedKelas = 0;
        $createdSiswa = 0;
        $updatedSiswa = 0;
        $deactivatedSiswa = 0;

        DB::transaction(function () use (
            $students,
            &$createdJurusan,
            &$updatedJurusan,
            &$createdKelas,
            &$updatedKelas,
            &$createdSiswa,
            &$updatedSiswa,
            &$deactivatedSiswa
        ) {
            $syncedNis = [];

            foreach ($students as $student) {
                $jurusan = $this->syncJurusan((string) ($student['jurusan'] ?? ''), $student, $createdJurusan, $updatedJurusan);
                $kelas = $this->syncKelas($jurusan, $student, $createdKelas, $updatedKelas);
                $this->syncSiswa($jurusan, $kelas, $student, $createdSiswa, $updatedSiswa);

                if (filled($student['nis'] ?? null)) {
                    $syncedNis[] = (string) $student['nis'];
                }
            }

            $deactivatedSiswa = Siswa::query()
                ->where('external_source', self::SOURCE)
                ->where('status', 'aktif')
                ->whereNotIn('nis', $syncedNis)
                ->update([
                    'status' => 'keluar',
                    'external_synced_at' => now(),
                ]);
        });

        return [
            'students_fetched' => $students->count(),
            'jurusan_created' => $createdJurusan,
            'jurusan_updated' => $updatedJurusan,
            'kelas_created' => $createdKelas,
            'kelas_updated' => $updatedKelas,
            'siswa_created' => $createdSiswa,
            'siswa_updated' => $updatedSiswa,
            'siswa_deactivated' => $deactivatedSiswa,
        ];
    }

    public function syncArrears(int $bulan, int $tahun): array
    {
        return $this->syncArrearsFromReport(
            $this->client->getArrearsReport($bulan, $tahun),
            $bulan,
            $tahun,
        );
    }

    public function syncArrearsFromReport(array $report, int $bulan, int $tahun): array
    {
        $rows = collect($report['rows'] ?? []);
        $references = [];
        $created = 0;
        $updated = 0;
        $deleted = 0;

        DB::transaction(function () use ($bulan, $tahun, $rows, &$references, &$created, &$updated, &$deleted) {
            foreach ($rows as $row) {
                $nis = (string) ($row['nis'] ?? '');
                $reference = "{$tahun}:{$bulan}:{$nis}";
                $references[] = $reference;

                $jurusan = $this->findJurusanByValue($row['nama_jurusan'] ?? $row['jurusan'] ?? null);
                $kelas = $this->findKelasByName($row['kelas'] ?? null);
                $siswa = filled($nis) ? Siswa::query()->where('nis', $nis)->first() : null;

                $payload = [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'nis' => $nis ?: null,
                    'nama' => $row['nama'] ?? null,
                    'jurusan_id' => $jurusan?->id ?? $siswa?->jurusan_id,
                    'kelas_id' => $kelas?->id ?? $siswa?->kelas_id,
                    'jurusan' => $jurusan?->nama ?? ($row['nama_jurusan'] ?? $row['jurusan'] ?? null),
                    'kelas' => $kelas?->nama_kelas ?? ($row['kelas'] ?? null),
                    'nominal_spp' => (float) ($row['nominal_spp'] ?? 0),
                    'no_hp_wali' => $row['no_hp_wali'] ?? $siswa?->no_hp_wali,
                    'nama_wali' => $row['nama_wali'] ?? $siswa?->nama_wali,
                    'external_source' => self::SOURCE,
                    'external_reference' => $reference,
                    'external_payload' => $row,
                    'external_synced_at' => now(),
                ];

                $existing = ExternalSppArrear::query()
                    ->where('external_source', self::SOURCE)
                    ->where('external_reference', $reference)
                    ->first();

                if ($existing) {
                    $existing->fill($payload)->save();
                    $updated++;
                } else {
                    ExternalSppArrear::query()->create($payload);
                    $created++;
                }

                if ($siswa && (float) ($row['nominal_spp'] ?? 0) > 0) {
                    $siswa->forceFill([
                        'nominal_spp' => (float) ($row['nominal_spp'] ?? 0),
                        'external_synced_at' => now(),
                    ])->save();
                }
            }

            $stale = ExternalSppArrear::query()
                ->where('external_source', self::SOURCE)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->when($references !== [], fn ($query) => $query->whereNotIn('external_reference', $references))
                ->get();

            $deleted = $stale->count();
            foreach ($stale as $row) {
                $row->delete();
            }
        });

        return [
            'rows_fetched' => $rows->count(),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'total_siswa_aktif' => (int) ($report['total_siswa_aktif'] ?? 0),
            'total_belum_bayar' => (int) ($report['total_belum_bayar'] ?? 0),
        ];
    }

    public function syncForMonth(int $bulan, int $tahun): array
    {
        $master = $this->syncMasterData();
        $arrears = $this->syncArrears($bulan, $tahun);

        return [
            'master' => $master,
            'arrears' => $arrears,
        ];
    }

    protected function syncJurusan(string $jurusanName, array $payload, int &$created, int &$updated): Jurusan
    {
        $mapping = $this->resolveJurusanMapping($jurusanName);
        $code = $mapping['kode'] ?? Str::upper(Str::limit(preg_replace('/[^A-Z]/', '', $this->normalize($jurusanName)), 10, ''));
        $name = $mapping['nama'] ?? trim($jurusanName);
        $accountCode = $mapping['kode_akun'] ?? null;
        $reference = $this->normalize($jurusanName);

        $model = Jurusan::query()->firstOrNew(['kode' => $code]);
        $exists = $model->exists;

        $model->fill([
            'nama' => $name,
            'kode_akun' => $accountCode ?: $model->kode_akun,
            'aktif' => true,
            'external_source' => self::SOURCE,
            'external_reference' => $reference,
            'external_payload' => $payload,
            'external_synced_at' => now(),
        ])->save();

        $exists ? $updated++ : $created++;

        return $model->fresh();
    }

    protected function syncKelas(Jurusan $jurusan, array $payload, int &$created, int &$updated): Kelas
    {
        $kelasName = trim((string) ($payload['kelas'] ?? ''));
        $tingkat = $this->normalizeTingkat($payload['tingkat'] ?? Str::before($kelasName, ' '));
        $reference = $this->normalize($kelasName);

        $model = Kelas::query()
            ->where('nama_kelas', $kelasName)
            ->first();

        if (! $model) {
            $model = Kelas::query()->firstOrNew([
                'jurusan_id' => $jurusan->id,
                'tingkat' => $tingkat,
            ]);
        }

        $exists = $model->exists;

        $model->fill([
            'jurusan_id' => $jurusan->id,
            'tingkat' => $tingkat,
            'nama_kelas' => $kelasName,
            'aktif' => true,
            'external_source' => self::SOURCE,
            'external_reference' => $reference,
            'external_payload' => $payload,
            'external_synced_at' => now(),
        ])->save();

        $exists ? $updated++ : $created++;

        return $model->fresh();
    }

    protected function syncSiswa(Jurusan $jurusan, Kelas $kelas, array $payload, int &$created, int &$updated): void
    {
        $nis = (string) ($payload['nis'] ?? '');
        if ($nis === '') {
            return;
        }

        $tingkat = strtoupper((string) ($payload['tingkat'] ?? $kelas->tingkat));
        $tingkat = $this->normalizeTingkat($tingkat);
        $angkatan = match ($tingkat) {
            'X' => now()->year,
            'XI' => now()->year - 1,
            'XII' => now()->year - 2,
            default => now()->year,
        };

        $model = Siswa::query()->firstOrNew(['nis' => $nis]);
        $exists = $model->exists;

        $model->fill([
            'nama' => $payload['nama'] ?? $model->nama,
            'kelas_id' => $kelas->id,
            'jurusan_id' => $jurusan->id,
            'angkatan' => $model->angkatan ?: $angkatan,
            'nominal_spp' => $model->nominal_spp ?: (float) config('spp_integration.master_default_nominal', 400000),
            'status' => 'aktif',
            'no_hp_wali' => $payload['whatsapp_orang_tua'] ?? $model->no_hp_wali,
            'nama_wali' => $model->nama_wali,
            'external_source' => self::SOURCE,
            'external_reference' => (string) ($payload['id'] ?? $nis),
            'external_payload' => $payload,
            'external_synced_at' => now(),
        ])->save();

        $exists ? $updated++ : $created++;
    }

    protected function resolveJurusanMapping(?string $jurusanName): ?array
    {
        $normalized = $this->normalize($jurusanName);

        foreach (config('spp_integration.jurusan_map', []) as $mapping) {
            $aliases = collect($mapping['aliases'] ?? [])
                ->map(fn ($alias) => $this->normalize($alias))
                ->all();

            if (in_array($normalized, $aliases, true)) {
                return $mapping;
            }
        }

        return null;
    }

    protected function findJurusanByValue(int|string|null $jurusan): ?Jurusan
    {
        if (blank($jurusan)) {
            return null;
        }

        if (is_numeric((string) $jurusan)) {
            return Jurusan::query()->find((int) $jurusan);
        }

        $mapping = $this->resolveJurusanMapping((string) $jurusan);
        if ($mapping) {
            return Jurusan::query()->where('kode', $mapping['kode'])->first();
        }

        return Jurusan::query()
            ->whereRaw('REPLACE(UPPER(nama), " ", "") = ?', [$this->normalize((string) $jurusan)])
            ->orWhereRaw('REPLACE(UPPER(kode), " ", "") = ?', [$this->normalize((string) $jurusan)])
            ->first();
    }

    protected function findKelasByName(?string $kelas): ?Kelas
    {
        if (blank($kelas)) {
            return null;
        }

        return Kelas::query()
            ->where('nama_kelas', $kelas)
            ->first();
    }

    protected function normalize(?string $value): string
    {
        return Str::upper(str_replace(' ', '', trim((string) $value)));
    }

    protected function normalizeTingkat(mixed $value): string
    {
        $raw = strtoupper(trim((string) $value));

        return match ($raw) {
            '10', 'X' => 'X',
            '11', 'XI' => 'XI',
            '12', 'XII' => 'XII',
            default => Str::before($raw, ' ') ?: 'X',
        };
    }
}
