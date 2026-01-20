<?php

namespace App\Models;

use App\Services\TerbilangService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Korban (Victims per Report)
 * 
 * Korban kejahatan siber per laporan (1:N)
 * Kerugian disimpan per korban, bukan per laporan
 * 
 * @property int $id
 * @property int $laporan_id FK ke laporan
 * @property int $orang_id FK ke orang (korban)
 * @property float $kerugian_nominal Kerugian dalam rupiah
 * @property string|null $kerugian_terbilang Kerugian dalam kata
 * @property string|null $keterangan Keterangan tambahan
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Laporan $laporan
 * @property-read Orang $orang
 */
class Korban extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'korban';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'laporan_id',
        'orang_id',
        'kerugian_nominal',
        'kerugian_terbilang',
        'keterangan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'laporan_id' => 'integer',
            'orang_id' => 'integer',
            'kerugian_nominal' => 'decimal:2',
        ];
    }

    // ========================================
    // BOOT
    // ========================================

    /**
     * Boot the model - auto-generate terbilang
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate kerugian_terbilang when kerugian_nominal changes
        static::saving(function (Korban $korban) {
            if ($korban->isDirty('kerugian_nominal') && $korban->kerugian_nominal > 0) {
                $korban->kerugian_terbilang = TerbilangService::convert($korban->kerugian_nominal);
            }
        });
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
     * Data orang (korban)
     */
    public function orang(): BelongsTo
    {
        return $this->belongsTo(Orang::class, 'orang_id');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get formatted kerugian in Rupiah
     */
    public function getKerugianFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->kerugian_nominal, 0, ',', '.');
    }

    /**
     * Get kerugian display (nominal + terbilang)
     */
    public function getKerugianDisplayAttribute(): string
    {
        $formatted = $this->kerugian_formatted;
        $terbilang = $this->kerugian_terbilang;
        
        return $terbilang ? "{$formatted} ({$terbilang})" : $formatted;
    }

    // ========================================
    // MUTATORS
    // ========================================

    /**
     * Set kerugian_nominal and auto-generate terbilang
     */
    public function setKerugianNominalAttribute($value): void
    {
        $this->attributes['kerugian_nominal'] = $value;
        
        // Generate terbilang if value > 0
        if ($value > 0) {
            $this->attributes['kerugian_terbilang'] = TerbilangService::convert($value);
        } else {
            $this->attributes['kerugian_terbilang'] = null;
        }
    }
}
