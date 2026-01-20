<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Wilayah Indonesia (Hierarchical Single Table)
 * 
 * Sumber: github.com/cahyadsn/wilayah
 * Format: Provinsi (2) → Kab/Kota (5) → Kecamatan (8) → Kelurahan (13)
 * 
 * @property string $kode Primary key (max 13 chars)
 * @property string $nama Nama wilayah
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Alamat> $alamatProvinsi
 * @property-read \Illuminate\Database\Eloquent\Collection<Alamat> $alamatKabupaten
 * @property-read \Illuminate\Database\Eloquent\Collection<Alamat> $alamatKecamatan
 * @property-read \Illuminate\Database\Eloquent\Collection<Alamat> $alamatKelurahan
 */
class Wilayah extends Model
{
    /**
     * Primary key is string (kode), not auto-increment
     */
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * No timestamps on this table
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     */
    protected $table = 'wilayah';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'kode',
        'nama',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Alamat yang menggunakan wilayah ini sebagai provinsi
     */
    public function alamatProvinsi(): HasMany
    {
        return $this->hasMany(Alamat::class, 'kode_provinsi', 'kode');
    }

    /**
     * Alamat yang menggunakan wilayah ini sebagai kabupaten
     */
    public function alamatKabupaten(): HasMany
    {
        return $this->hasMany(Alamat::class, 'kode_kabupaten', 'kode');
    }

    /**
     * Alamat yang menggunakan wilayah ini sebagai kecamatan
     */
    public function alamatKecamatan(): HasMany
    {
        return $this->hasMany(Alamat::class, 'kode_kecamatan', 'kode');
    }

    /**
     * Alamat yang menggunakan wilayah ini sebagai kelurahan
     */
    public function alamatKelurahan(): HasMany
    {
        return $this->hasMany(Alamat::class, 'kode_kelurahan', 'kode');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get hierarchy level based on kode length
     * 
     * @return string provinsi|kabupaten|kecamatan|kelurahan
     */
    public function getLevel(): string
    {
        return match (strlen($this->kode)) {
            2 => 'provinsi',
            5 => 'kabupaten',
            8 => 'kecamatan',
            13 => 'kelurahan',
            default => 'unknown',
        };
    }

    /**
     * Check if this is a province
     */
    public function isProvinsi(): bool
    {
        return strlen($this->kode) === 2;
    }

    /**
     * Check if this is a kabupaten/kota
     */
    public function isKabupaten(): bool
    {
        return strlen($this->kode) === 5;
    }

    /**
     * Check if this is a kecamatan
     */
    public function isKecamatan(): bool
    {
        return strlen($this->kode) === 8;
    }

    /**
     * Check if this is a kelurahan/desa
     */
    public function isKelurahan(): bool
    {
        return strlen($this->kode) === 13;
    }

    /**
     * Get parent wilayah
     */
    public function getParent(): ?Wilayah
    {
        $parentKode = match (strlen($this->kode)) {
            5 => substr($this->kode, 0, 2),      // Kabupaten → Provinsi
            8 => substr($this->kode, 0, 5),      // Kecamatan → Kabupaten
            13 => substr($this->kode, 0, 8),     // Kelurahan → Kecamatan
            default => null,
        };

        return $parentKode ? static::find($parentKode) : null;
    }

    /**
     * Get children wilayah
     */
    public function getChildren()
    {
        $childLength = match (strlen($this->kode)) {
            2 => 5,   // Provinsi → Kabupaten
            5 => 8,   // Kabupaten → Kecamatan
            8 => 13,  // Kecamatan → Kelurahan
            default => 0,
        };

        if ($childLength === 0) {
            return collect();
        }

        return static::where('kode', 'like', $this->kode . '%')
            ->whereRaw('LENGTH(kode) = ?', [$childLength])
            ->get();
    }
}
