<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['jurusan_id', 'tingkat', 'nama_kelas', 'aktif', 'external_source', 'external_reference', 'external_payload', 'external_synced_at'];

    protected $casts = ['aktif' => 'boolean', 'external_payload' => 'array', 'external_synced_at' => 'datetime'];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    public function jurnalKas(): HasMany
    {
        return $this->hasMany(JurnalKas::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
