<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Anggota Kepolisian (Police Members/Officers)
 * 
 * Data anggota Kepolisian Ditresiber POLDA Jateng
 * 
 * @property int $id
 * @property int $pangkat_id FK ke pangkat
 * @property int $jabatan_id FK ke jabatan
 * @property string $nrp Nomor Registrasi Pokok (unique)
 * @property string $nama Nama lengkap
 * @property bool $is_active Status aktif
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Pangkat $pangkat
 * @property-read Jabatan $jabatan
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<Laporan> $laporanDiterima
 */
class Anggota extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'anggota';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'pangkat_id',
        'jabatan_id',
        'nrp',
        'nama',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'pangkat_id' => 'integer',
            'jabatan_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Pangkat anggota
     */
    public function pangkat(): BelongsTo
    {
        return $this->belongsTo(Pangkat::class, 'pangkat_id');
    }

    /**
     * Jabatan anggota
     */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    /**
     * User account linked to this anggota
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'anggota_id');
    }

    /**
     * Laporan yang diterima oleh anggota ini
     */
    public function laporanDiterima(): HasMany
    {
        return $this->hasMany(Laporan::class, 'petugas_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Only active members
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get full display name with rank
     * Example: "KOMPOL John Doe"
     */
    public function getNamaLengkapAttribute(): string
    {
        return ($this->pangkat?->kode ?? '') . ' ' . $this->nama;
    }

    /**
     * Get formatted display: "KOMPOL John Doe - Penyidik"
     */
    public function getDisplayNameAttribute(): string
    {
        $pangkat = $this->pangkat?->kode ?? '';
        $jabatan = $this->jabatan?->nama ?? '';
        
        return trim("{$pangkat} {$this->nama}") . ($jabatan ? " - {$jabatan}" : '');
    }
}
