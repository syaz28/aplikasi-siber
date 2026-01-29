<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPlatform extends Model
{
    use HasFactory;

    protected $table = 'master_platforms';

    protected $fillable = [
        'kategori',
        'nama_platform',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * Scope untuk filter by kategori
     */
    public function scopeByKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Scope untuk filter active only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get platforms grouped by kategori
     */
    public static function getGrouped(): array
    {
        return self::active()
            ->orderBy('kategori')
            ->orderBy('urutan')
            ->get()
            ->groupBy('kategori')
            ->toArray();
    }

    /**
     * Kategori constants for validation
     */
    const KATEGORI_MEDIA_SOSIAL = 'Media Sosial';
    const KATEGORI_NOMOR_TELEPON = 'Nomor Telepon';
    const KATEGORI_REKENING_BANK = 'Rekening Bank';
    const KATEGORI_E_WALLET = 'E-Wallet';
    const KATEGORI_EMAIL = 'Email';
    const KATEGORI_MARKETPLACE = 'Marketplace';
    const KATEGORI_LAINNYA = 'Lainnya';

    /**
     * Get all valid kategori
     */
    public static function getValidKategori(): array
    {
        return [
            self::KATEGORI_MEDIA_SOSIAL,
            self::KATEGORI_NOMOR_TELEPON,
            self::KATEGORI_REKENING_BANK,
            self::KATEGORI_E_WALLET,
            self::KATEGORI_EMAIL,
            self::KATEGORI_MARKETPLACE,
            self::KATEGORI_LAINNYA,
        ];
    }
}
