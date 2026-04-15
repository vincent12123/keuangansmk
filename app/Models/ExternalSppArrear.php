<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalSppArrear extends Model
{
    protected $table = 'external_spp_arrears';

    protected $fillable = [
        'bulan',
        'tahun',
        'nis',
        'nama',
        'jurusan_id',
        'kelas_id',
        'jurusan',
        'kelas',
        'nominal_spp',
        'no_hp_wali',
        'nama_wali',
        'external_source',
        'external_reference',
        'external_payload',
        'external_synced_at',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'jurusan_id' => 'integer',
        'kelas_id' => 'integer',
        'nominal_spp' => 'decimal:2',
        'external_payload' => 'array',
        'external_synced_at' => 'datetime',
    ];
}
