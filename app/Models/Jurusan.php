<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ─── Jurusan ─────────────────────────────────────────────────
class Jurusan extends Model
{
    protected $table = 'jurusan';

    protected $fillable = ['kode', 'nama', 'kode_akun', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    public function kodeAkun(): BelongsTo
    {
        return $this->belongsTo(KodeAkun::class, 'kode_akun', 'kode');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
