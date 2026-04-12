<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Filament Auth ───────────────────────────────────────
    public function canAccessPanel(Panel $panel): bool
    {
        // Semua user yang punya role bisa akses panel
        return $this->hasAnyRole(['admin', 'bendahara', 'kepala_sekolah']);
    }

    // ─── Helpers ─────────────────────────────────────────────
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isBendahara(): bool
    {
        return $this->hasRole('bendahara');
    }

    public function isKepalaSekolah(): bool
    {
        return $this->hasRole('kepala_sekolah');
    }

    public function getRoleDisplayAttribute(): string
    {
        if ($this->isAdmin()) return 'Administrator';
        if ($this->isBendahara()) return 'Bendahara';
        if ($this->isKepalaSekolah()) return 'Kepala Sekolah';
        return 'User';
    }
}
