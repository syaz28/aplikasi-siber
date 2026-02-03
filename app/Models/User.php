<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User (Shared Account Authentication)
 * 
 * Simplified user model for shared account login.
 * No longer stores personal data - only authentication credentials and role.
 * 
 * Role-based access:
 * - admin: User management, full system access
 * - petugas: Case entry with Pawas selection
 * - admin_subdit: Subdit-specific case management
 * - pimpinan: Executive dashboard only
 * 
 * @property int $id
 * @property string $username Unique username for login
 * @property string $password Hashed password
 * @property string $role admin|petugas|admin_subdit|pimpinan
 * @property int|null $subdit_access Subdit filter for admin_subdit (1=Ekonomi, 2=Sosial, 3=Khusus)
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
        'username',
        'password',
        'role',
        'subdit_access',
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
            'password' => 'hashed',
            'subdit_access' => 'integer',
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
