<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['jurusan_id', 'tingkat', 'nama_kelas', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

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
