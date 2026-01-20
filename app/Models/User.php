<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User (Authentication)
 * 
 * User sistem dengan link ke anggota kepolisian
 * Role: superadmin, admin, operator
 * 
 * @property int $id
 * @property int|null $anggota_id FK ke anggota (nullable untuk superadmin)
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role superadmin|admin|operator
 * @property string|null $remember_token
 * @property bool $is_active Status aktif user
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Anggota|null $anggota
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
        'anggota_id',
        'email',
        'password',
        'role',
        'is_active',
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
            'anggota_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Anggota yang terkait dengan user ini
     */
    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
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
     * Check if user is superadmin
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is operator
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Check if user has admin level access (superadmin or admin)
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get display name (from anggota or email)
     */
    public function getNameAttribute(): string
    {
        return $this->anggota?->nama ?? $this->email;
    }

    /**
     * Get full display name with rank if available
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->anggota?->display_name ?? $this->email;
    }
}
