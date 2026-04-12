<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anggaran extends Model
{
    protected $table = 'anggaran';

    protected $fillable = [
        'kode_akun_id',
        'tahun',
        'target',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tahun'  => 'integer',
        'target' => 'decimal:2',
    ];

    public function kodeAkun(): BelongsTo
    {
        return $this->belongsTo(KodeAkun::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
