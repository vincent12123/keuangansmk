<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalSppMonthlyCache extends Model
{
    protected $fillable = [
        'bulan',
        'tahun',
        'total_cash',
        'total_bank',
        'total_nominal',
        'payload',
        'fetched_at',
    ];

    protected $casts = [
        'total_cash' => 'decimal:2',
        'total_bank' => 'decimal:2',
        'total_nominal' => 'decimal:2',
        'payload' => 'array',
        'fetched_at' => 'datetime',
    ];
}
