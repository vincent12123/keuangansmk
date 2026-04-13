<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoKasBulanan extends Model
{
    protected $table = 'saldo_kas_bulanan';

    protected $fillable = [
        'bulan',
        'tahun',
        'saldo_awal_cash',
        'saldo_awal_bank',
        'is_locked',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'saldo_awal_cash' => 'decimal:2',
        'saldo_awal_bank' => 'decimal:2',
        'is_locked' => 'boolean',
    ];

    public function scopeBulanTahun($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }
}
