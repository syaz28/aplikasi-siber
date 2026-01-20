<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pangkat Kepolisian (Police Ranks)
 * 
 * Urutan: 1 = tertinggi (Jenderal) hingga 16 = terendah (Bripda)
 * 
 * @property int $id
 * @property string $kode Kode pangkat (AKBP, KOMPOL, dll)
 * @property string $nama Nama lengkap pangkat
 * @property int $urutan Urutan hierarki (1 = tertinggi)
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Anggota> $anggota
 */
class Pangkat extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'pangkat';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'kode',
        'nama',
        'urutan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Anggota yang memiliki pangkat ini
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class, 'pangkat_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Order by hierarchy (highest rank first)
     */
    public function scopeByHierarchy($query)
    {
        return $query->orderBy('urutan', 'asc');
    }
}
