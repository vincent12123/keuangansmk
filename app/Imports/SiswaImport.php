<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\SppImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;

class SiswaImport implements ToCollection
{
    use Importable;

    protected array $errors = [];

    public function __construct(
        protected int $angkatan,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $firstRow = collect($rows->first())->map(fn ($value) => $this->normalize((string) $value))->all();
        $hasHeading = $this->containsAnyKey($firstRow, ['nis', 'nisn', 'nis_nisn'])
            && $this->containsAnyKey($firstRow, ['kelas', 'kelas_siswa']);

        if ($hasHeading) {
            $headers = collect($rows->shift())->map(fn ($value) => $this->normalize((string) $value))->values()->all();
            $monthColumns = $this->resolveHeadingMonthColumns($headers);

            foreach ($rows as $row) {
                $this->importRowWithHeaders(collect($row)->values()->all(), $headers, $monthColumns);
            }

            return;
        }

        $monthColumns = $this->resolveDefaultMonthColumns();

        foreach ($rows as $row) {
            $this->importRowByPosition(collect($row)->values()->all(), $monthColumns);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function importRowWithHeaders(array $row, array $headers, array $monthColumns): void
    {
        $indexed = [];

        foreach ($headers as $index => $header) {
            $indexed[$header] = $row[$index] ?? null;
        }

        $nis = trim((string) $this->getIndexedValue($indexed, ['nis', 'nisn', 'nis_nisn']));
        $nama = trim((string) $this->getIndexedValue($indexed, ['nama', 'nama_siswa']));
        $kelasName = trim((string) $this->getIndexedValue($indexed, ['kelas', 'kelas_siswa']));
        $nominalSpp = $this->extractNumeric($this->getIndexedValue($indexed, ['nominal_spp', 'spp']));

        if ($nis === '' || $nama === '' || $kelasName === '') {
            return;
        }

        $siswa = $this->upsertSiswa($nis, $nama, $kelasName, $nominalSpp > 0 ? $nominalSpp : null);

        if (! $siswa) {
            return;
        }

        foreach ($monthColumns as $header => $info) {
            $value = $indexed[$header] ?? null;
            $this->syncKartuSpp($siswa, $value, $info['bulan'], $info['tahun']);
        }
    }

    protected function importRowByPosition(array $row, array $monthColumns): void
    {
        $nis = trim((string) ($row[1] ?? ''));
        $nama = trim((string) ($row[2] ?? ''));
        $kelasName = trim((string) ($row[3] ?? ''));

        if ($nis === '' || $nama === '' || $kelasName === '') {
            return;
        }

        $siswa = $this->upsertSiswa($nis, $nama, $kelasName);

        if (! $siswa) {
            return;
        }

        foreach ($monthColumns as $index => $info) {
            $this->syncKartuSpp($siswa, $row[$index] ?? null, $info['bulan'], $info['tahun']);
        }
    }

    protected function upsertSiswa(string $nis, string $nama, string $kelasName, ?float $nominalSppFromExcel = null): ?Siswa
    {
        $kelas = $this->resolveKelas($kelasName);

        if (! $kelas) {
            $this->errors[] = "Kelas tidak ditemukan untuk NIS {$nis}: {$kelasName}";

            return null;
        }

        $existingSiswa = Siswa::query()->where('nis', $nis)->first();

        $nominalDefault = match ($kelas->jurusan?->kode) {
            'RPL' => 400000,
            'TBSM' => 375000,
            'HTL' => 425000,
            default => 400000,
        };

        $nominalSpp = $nominalSppFromExcel && $nominalSppFromExcel > 0
            ? $nominalSppFromExcel
            : (float) ($existingSiswa?->nominal_spp ?? $nominalDefault);

        return Siswa::updateOrCreate(
            ['nis' => $nis],
            [
                'nama' => $nama,
                'kelas_id' => $kelas->id,
                'jurusan_id' => $kelas->jurusan_id,
                'angkatan' => $this->angkatan,
                'nominal_spp' => $nominalSpp,
                'status' => 'aktif',
            ],
        );
    }

    protected function syncKartuSpp(Siswa $siswa, mixed $value, int $bulan, int $tahun): void
    {
        $nominal = $this->extractNumeric($value);

        if ($nominal <= 0) {
            return;
        }

        app(SppImportService::class)->syncPayment(
            $siswa,
            $bulan,
            $tahun,
            $nominal,
            'Import dari Excel lama',
            $this->errors,
        );
    }

    protected function resolveKelas(string $kelasName): ?Kelas
    {
        $namaKelas = strtoupper(trim($kelasName));

        $kelas = Kelas::query()
            ->with('jurusan')
            ->whereRaw('UPPER(nama_kelas) = ?', [$namaKelas])
            ->first();

        if ($kelas) {
            return $kelas;
        }

        if (preg_match('/^(XII|XI|X)\s+(.+)$/', $namaKelas, $matches) !== 1) {
            return null;
        }

        $tingkat = $matches[1];
        $jurusan = trim($matches[2]);
        $jurusan = match ($jurusan) {
            'PERHOTELAN' => 'HTL',
            default => $jurusan,
        };

        return Kelas::query()
            ->with('jurusan')
            ->where('tingkat', $tingkat)
            ->whereHas('jurusan', fn ($query) => $query->where('kode', $jurusan))
            ->first();
    }

    protected function resolveHeadingMonthColumns(array $headers): array
    {
        $startYear = $this->angkatan;
        $nextYear = $startYear + 1;
        $result = [];

        foreach ($headers as $header) {
            $result[$header] = match ($header) {
                'juli' => ['bulan' => 7, 'tahun' => $startYear],
                'agustus' => ['bulan' => 8, 'tahun' => $startYear],
                'september' => ['bulan' => 9, 'tahun' => $startYear],
                'oktober' => ['bulan' => 10, 'tahun' => $startYear],
                'november' => ['bulan' => 11, 'tahun' => $startYear],
                'desember' => ['bulan' => 12, 'tahun' => $startYear],
                'januari' => ['bulan' => 1, 'tahun' => $nextYear],
                'februari' => ['bulan' => 2, 'tahun' => $nextYear],
                'maret' => ['bulan' => 3, 'tahun' => $nextYear],
                'april' => ['bulan' => 4, 'tahun' => $nextYear],
                'mei' => ['bulan' => 5, 'tahun' => $nextYear],
                'juni' => ['bulan' => 6, 'tahun' => $nextYear],
                default => null,
            };
        }

        return array_filter($result);
    }

    protected function resolveDefaultMonthColumns(): array
    {
        $startYear = $this->angkatan;
        $nextYear = $startYear + 1;

        return [
            4 => ['bulan' => 7, 'tahun' => $startYear],
            5 => ['bulan' => 8, 'tahun' => $startYear],
            6 => ['bulan' => 9, 'tahun' => $startYear],
            7 => ['bulan' => 10, 'tahun' => $startYear],
            8 => ['bulan' => 11, 'tahun' => $startYear],
            9 => ['bulan' => 12, 'tahun' => $startYear],
            10 => ['bulan' => 1, 'tahun' => $nextYear],
            11 => ['bulan' => 2, 'tahun' => $nextYear],
            12 => ['bulan' => 3, 'tahun' => $nextYear],
            13 => ['bulan' => 4, 'tahun' => $nextYear],
            14 => ['bulan' => 5, 'tahun' => $nextYear],
            15 => ['bulan' => 6, 'tahun' => $nextYear],
        ];
    }

    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    protected function getIndexedValue(array $indexed, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $indexed)) {
                continue;
            }

            if (filled($indexed[$key])) {
                return $indexed[$key];
            }
        }

        return null;
    }

    protected function containsAnyKey(array $keys, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $keys, true)) {
                return true;
            }
        }

        return false;
    }

    protected function extractNumeric(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $numeric = preg_replace('/[^0-9]/', '', (string) $value);

        return $numeric === '' ? 0.0 : (float) $numeric;
    }
}
