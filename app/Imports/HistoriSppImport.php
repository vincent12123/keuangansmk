<?php

namespace App\Imports;

use App\Models\KartuSpp;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;

class HistoriSppImport implements ToCollection
{
    use Importable;

    protected array $errors = [];

    public function __construct(
        protected int $tahunAjaran,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $firstRow = collect($rows->first())->map(fn ($value) => strtolower(trim((string) $value)))->all();
        $hasHeader = in_array('nis', $firstRow, true);

        if ($hasHeader) {
            $headers = collect($rows->shift())->map(fn ($value) => strtolower(trim((string) $value)))->values()->all();
            $monthColumns = $this->resolveHeadingMonthColumns($headers);

            foreach ($rows as $row) {
                $indexed = [];

                foreach ($headers as $index => $header) {
                    $indexed[$header] = $row[$index] ?? null;
                }

                $this->syncHistory((string) ($indexed['nis'] ?? ''), $indexed, $monthColumns);
            }

            return;
        }

        $monthColumns = $this->resolveDefaultMonthColumns();

        foreach ($rows as $row) {
            $indexed = collect($row)->values()->all();
            $nis = (string) ($indexed[1] ?? '');
            $this->syncHistory($nis, $indexed, $monthColumns);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function syncHistory(string $nis, array $row, array $monthColumns): void
    {
        $nis = trim($nis);

        if ($nis === '') {
            return;
        }

        $siswa = Siswa::query()->where('nis', $nis)->first();

        if (! $siswa) {
            $this->errors[] = "NIS tidak ditemukan: {$nis}";

            return;
        }

        foreach ($monthColumns as $column => $info) {
            $value = $row[$column] ?? null;
            $nominal = $this->extractNumeric($value);

            if ($nominal <= 0) {
                continue;
            }

            KartuSpp::updateOrCreate(
                [
                    'nis' => $siswa->nis,
                    'bulan' => $info['bulan'],
                    'tahun' => $info['tahun'],
                ],
                [
                    'nominal' => $nominal,
                    'tgl_bayar' => now()->toDateString(),
                    'jurnal_kas_id' => null,
                    'keterangan' => 'Import histori SPP dari Excel lama',
                ],
            );
        }
    }

    protected function resolveHeadingMonthColumns(array $headers): array
    {
        $startYear = $this->tahunAjaran;
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
        $startYear = $this->tahunAjaran;
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

    protected function extractNumeric(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $numeric = preg_replace('/[^0-9]/', '', (string) $value);

        return $numeric === '' ? 0.0 : (float) $numeric;
    }
}
