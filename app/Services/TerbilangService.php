<?php

namespace App\Services;

class TerbilangService
{
    protected array $satuan = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan',
        'sepuluh',
        'sebelas',
    ];

    public function convert(float|int $angka): string
    {
        $angka = (int) round(abs($angka));

        if ($angka === 0) {
            return 'Nol Rupiah';
        }

        return ucfirst(trim($this->terbilang($angka))) . ' Rupiah';
    }

    protected function terbilang(int $angka): string
    {
        if ($angka < 12) {
            return ' ' . $this->satuan[$angka];
        }

        if ($angka < 20) {
            return $this->terbilang($angka - 10) . ' belas';
        }

        if ($angka < 100) {
            return $this->terbilang((int) floor($angka / 10)) . ' puluh' . $this->terbilang($angka % 10);
        }

        if ($angka < 200) {
            return ' seratus' . $this->terbilang($angka - 100);
        }

        if ($angka < 1000) {
            return $this->terbilang((int) floor($angka / 100)) . ' ratus' . $this->terbilang($angka % 100);
        }

        if ($angka < 2000) {
            return ' seribu' . $this->terbilang($angka - 1000);
        }

        if ($angka < 1000000) {
            return $this->terbilang((int) floor($angka / 1000)) . ' ribu' . $this->terbilang($angka % 1000);
        }

        if ($angka < 1000000000) {
            return $this->terbilang((int) floor($angka / 1000000)) . ' juta' . $this->terbilang($angka % 1000000);
        }

        if ($angka < 1000000000000) {
            return $this->terbilang((int) floor($angka / 1000000000)) . ' miliar' . $this->terbilang($angka % 1000000000);
        }

        return $this->terbilang((int) floor($angka / 1000000000000)) . ' triliun' . $this->terbilang($angka % 1000000000000);
    }
}
