<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori Kejahatan Siber (Cyber Crime Categories)
 * 
 * 8 kategori utama: Penipuan Online, Pemerasan Digital, Pencurian Data, dll
 * 
 * @property int $id
 * @property string $nama Nama kategori
 * @property string|null $deskripsi Deskripsi kategori
 * @property bool $is_active Status aktif
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<JenisKejahatan> $jenisKejahatan
 */
class KategoriKejahatan extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'kategori_kejahatan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
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
            'is_active' => 'boolean',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Jenis kejahatan dalam kategori ini
     */
    public function jenisKejahatan(): HasMany
    {
        return $this->hasMany(JenisKejahatan::class, 'kategori_kejahatan_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
