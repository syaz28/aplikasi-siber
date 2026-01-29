<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User (Authentication & Profile)
 * 
 * User sistem dengan semua data profil langsung (konsolidasi dari anggota)
 * Role: admin, petugas, admin_subdit, pimpinan
 * 
 * @property int $id
 * @property string $name Nama lengkap
 * @property string|null $nrp NRP Anggota
 * @property string|null $pangkat Pangkat (AKBP, Kompol, AKP, dll)
 * @property string|null $telepon Nomor telepon
 * @property string|null $jabatan Jabatan fungsional
 * @property string|null $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role admin|petugas|admin_subdit|pimpinan
 * @property int|null $subdit Subdit 1-3 (untuk admin_subdit/petugas)
 * @property int|null $unit Unit 1-5 (untuk petugas)
 * @property bool $is_active Status aktif user
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Laporan> $laporanDiterima
 * @property-read \Illuminate\Database\Eloquent\Collection<Laporan> $laporanCreated
 * @property-read \Illuminate\Database\Eloquent\Collection<Laporan> $laporanUpdated
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nrp',
        'email',
        'password',
        'role',
        'subdit',
        'unit',
        'is_active',
        'pangkat',
        'telepon',
        'jabatan',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'subdit' => 'integer',
            'unit' => 'integer',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Laporan yang diterima/ditangani petugas ini
     */
    public function laporanDiterima(): HasMany
    {
        return $this->hasMany(Laporan::class, 'petugas_id');
    }

    /**
     * Laporan yang dibuat oleh user ini
     */
    public function laporanCreated(): HasMany
    {
        return $this->hasMany(Laporan::class, 'created_by');
    }

    /**
     * Laporan yang terakhir diupdate oleh user ini
     */
    public function laporanUpdated(): HasMany
    {
        return $this->hasMany(Laporan::class, 'updated_by');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // ========================================
    // ROLE HELPERS
    // ========================================

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is petugas
     */
    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    /**
     * Check if user is admin subdit
     */
    public function isAdminSubdit(): bool
    {
        return $this->role === 'admin_subdit';
    }

    /**
     * Check if user is pimpinan
     */
    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan';
    }

    /**
     * Check if user has admin level access (admin or admin_subdit)
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, ['admin', 'admin_subdit']);
    }

    /**
     * Get role label for display
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Admin',
            'petugas' => 'Petugas',
            'admin_subdit' => 'Admin Subdit',
            'pimpinan' => 'Pimpinan',
            default => ucfirst($this->role ?? 'Unknown'),
        };
    }

    /**
     * Get subdit label for display
     */
    public function getSubditLabelAttribute(): ?string
    {
        if ($this->subdit === null) {
            return null;
        }
        return "Subdit {$this->subdit}";
    }

    /**
     * Get unit label for display
     */
    public function getUnitLabelAttribute(): ?string
    {
        if ($this->unit === null) {
            return null;
        }
        return "Unit {$this->unit}";
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get display name (from name field or email)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Get full display name with rank if available
     */
    public function getFullDisplayNameAttribute(): string
    {
        $parts = [];
        if ($this->name) {
            $parts[] = $this->name;
        }
        if ($this->nrp) {
            $parts[] = "({$this->nrp})";
        }
        return implode(' ', $parts) ?: $this->email;
    }
}
