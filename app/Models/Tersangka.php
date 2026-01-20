<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tersangka (Suspects per Report)
 * 
 * Tersangka kejahatan siber per laporan (1:N)
 * orang_id NULLABLE: tersangka bisa belum teridentifikasi
 * Identitas digital disimpan di identitas_tersangka (1:N)
 * 
 * @property int $id
 * @property int $laporan_id FK ke laporan
 * @property int|null $orang_id FK ke orang (nullable jika belum teridentifikasi)
 * @property string|null $catatan Catatan tentang tersangka
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Laporan $laporan
 * @property-read Orang|null $orang
 * @property-read \Illuminate\Database\Eloquent\Collection<IdentitasTersangka> $identitas
 */
class Tersangka extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'tersangka';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'laporan_id',
        'orang_id',
        'catatan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'laporan_id' => 'integer',
            'orang_id' => 'integer',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Laporan terkait
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    /**
     * Data orang (tersangka) - nullable
     */
    public function orang(): BelongsTo
    {
        return $this->belongsTo(Orang::class, 'orang_id');
    }

    /**
     * Identitas digital tersangka (1:N)
     */
    public function identitas(): HasMany
    {
        return $this->hasMany(IdentitasTersangka::class, 'tersangka_id');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Check if tersangka is identified (has orang data)
     */
    public function getIsIdentifiedAttribute(): bool
    {
        return $this->orang_id !== null;
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->orang) {
            return $this->orang->nama;
        }
        
        // If not identified, show first identity if available
        $firstIdentity = $this->identitas->first();
        if ($firstIdentity) {
            return "Tersangka ({$firstIdentity->jenis}: {$firstIdentity->nilai})";
        }
        
        return 'Tersangka Belum Teridentifikasi';
    }

    /**
     * Get jumlah identitas
     */
    public function getJumlahIdentitasAttribute(): int
    {
        return $this->identitas->count();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Only identified suspects
     */
    public function scopeIdentified($query)
    {
        return $query->whereNotNull('orang_id');
    }

    /**
     * Only unidentified suspects
     */
    public function scopeUnidentified($query)
    {
        return $query->whereNull('orang_id');
    }
}
