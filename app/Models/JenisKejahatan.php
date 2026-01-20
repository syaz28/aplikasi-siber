<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jenis Kejahatan Siber (Specific Cyber Crime Types)
 * 
 * 30+ jenis kejahatan spesifik yang termasuk dalam kategori
 * Contoh: Penipuan Investasi Bodong, Sextortion, Phishing, dll
 * 
 * @property int $id
 * @property int $kategori_kejahatan_id FK ke kategori
 * @property string $nama Nama jenis kejahatan
 * @property string|null $deskripsi Deskripsi
 * @property bool $is_active Status aktif
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read KategoriKejahatan $kategori
 * @property-read \Illuminate\Database\Eloquent\Collection<Laporan> $laporan
 */
class JenisKejahatan extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'jenis_kejahatan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'kategori_kejahatan_id',
        'nama',
        'deskripsi',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'kategori_kejahatan_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Kategori kejahatan parent
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriKejahatan::class, 'kategori_kejahatan_id');
    }

    /**
     * Laporan yang menggunakan jenis kejahatan ini
     */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class, 'jenis_kejahatan_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Only active crime types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by category
     */
    public function scopeByKategori($query, int $kategoriId)
    {
        return $query->where('kategori_kejahatan_id', $kategoriId);
    }
}
