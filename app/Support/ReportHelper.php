<?php

namespace App\Support;

class ReportHelper
{
    public static function monthName(int $bulan): string
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

    public static function monthNameUpper(int $bulan): string
    {
        return strtoupper(static::monthName($bulan));
    }
}
