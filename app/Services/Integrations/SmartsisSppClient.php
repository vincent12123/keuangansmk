<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

class SmartsisSppClient
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    public function getMonthlySummary(int $bulan, int $tahun): array
    {
        $response = $this->request()
            ->get('/api/keuangan/spp/rekap-bulanan', [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ])
            ->throw();

        return $response->json();
    }

    public function getAllPayments(int $bulan, int $tahun, int $perPage = 200): array
    {
        $page = 1;
        $rows = [];

        do {
            $payload = $this->request()
                ->get('/api/keuangan/spp/pembayaran', [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'per_page' => $perPage,
                    'page' => $page,
                ])
                ->throw()
                ->json();

            $rows = array_merge($rows, $payload['data'] ?? []);
            $currentPage = (int) ($payload['meta']['current_page'] ?? $page);
            $lastPage = (int) ($payload['meta']['last_page'] ?? $currentPage);
            $page++;
        } while ($currentPage < $lastPage);

        return $rows;
    }

    public function getArrearsReport(int $bulan, int $tahun, ?string $jurusan = null, ?string $kelas = null): array
    {
        $params = [
            'bulan' => $bulan,
            'tahun' => $tahun,
        ];

        if ($jurusan) {
            $params['jurusan'] = $jurusan;
        }

        if ($kelas) {
            $params['kelas'] = $kelas;
        }

        return $this->request()
            ->get('/api/keuangan/spp/tunggakan', $params)
            ->throw()
            ->json();
    }

    public function getAllActiveStudents(int $perPage = 200): array
    {
        $page = 1;
        $rows = [];

        do {
            $payload = $this->request()
                ->get('/api/keuangan/master/siswa-aktif', [
                    'per_page' => $perPage,
                    'page' => $page,
                ])
                ->throw()
                ->json();

            $rows = array_merge($rows, $payload['data'] ?? []);
            $currentPage = (int) ($payload['meta']['current_page'] ?? $page);
            $lastPage = (int) ($payload['meta']['last_page'] ?? $currentPage);
            $page++;
        } while ($currentPage < $lastPage);

        return $rows;
    }

    protected function request()
    {
        return $this->http
            ->baseUrl(rtrim((string) config('spp_integration.base_url'), '/'))
            ->acceptJson()
            ->withToken((string) config('spp_integration.token'))
            ->timeout((int) config('spp_integration.timeout', 10));
    }
}
