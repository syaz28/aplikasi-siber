<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identitas Tersangka (Digital Identities of Suspects)
 * 
 * Identitas digital tersangka (1:N dengan tersangka)
 * Digunakan untuk tracking tersangka berulang across reports
 * Query by jenis+nilai untuk menemukan tersangka yang sama
 * 
 * @property int $id
 * @property int $tersangka_id FK ke tersangka
 * @property string $jenis telepon|rekening|sosmed|email|ewallet|kripto|marketplace|website|lainnya
 * @property string $nilai Nilai identitas (nomor HP, rekening, username, dll)
 * @property string|null $platform Platform (BRI, Instagram, Tokopedia, dll)
 * @property string|null $nama_akun Nama akun/pemilik
 * @property string|null $catatan Catatan tambahan
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Tersangka $tersangka
 */
class IdentitasTersangka extends Model
{
    /**
     * Jenis identitas enum values
     */
    public const JENIS_TELEPON = 'telepon';
    public const JENIS_REKENING = 'rekening';
    public const JENIS_SOSMED = 'sosmed';
    public const JENIS_EMAIL = 'email';
    public const JENIS_EWALLET = 'ewallet';
    public const JENIS_KRIPTO = 'kripto';
    public const JENIS_MARKETPLACE = 'marketplace';
    public const JENIS_WEBSITE = 'website';
    public const JENIS_LAINNYA = 'lainnya';

    /**
     * The table associated with the model.
     */
    protected $table = 'identitas_tersangka';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tersangka_id',
        'jenis',
        'nilai',
        'platform',
        'nama_akun',
        'catatan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'tersangka_id' => 'integer',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Tersangka pemilik identitas ini
     */
    public function tersangka(): BelongsTo
    {
        return $this->belongsTo(Tersangka::class, 'tersangka_id');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get jenis label in Indonesian
     */
    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            self::JENIS_TELEPON => 'Nomor Telepon',
            self::JENIS_REKENING => 'Rekening Bank',
            self::JENIS_SOSMED => 'Media Sosial',
            self::JENIS_EMAIL => 'Email',
            self::JENIS_EWALLET => 'E-Wallet',
            self::JENIS_KRIPTO => 'Kripto/Wallet',
            self::JENIS_MARKETPLACE => 'Marketplace',
            self::JENIS_WEBSITE => 'Website',
            self::JENIS_LAINNYA => 'Lainnya',
            default => $this->jenis,
        };
    }

    /**
     * Get display string
     * Example: "Rekening Bank: BRI - 1234567890 (John Doe)"
     */
    public function getDisplayAttribute(): string
    {
        $parts = [$this->jenis_label . ':'];
        
        if ($this->platform) {
            $parts[] = $this->platform . ' -';
        }
        
        $parts[] = $this->nilai;
        
        if ($this->nama_akun) {
            $parts[] = "({$this->nama_akun})";
        }

        return implode(' ', $parts);
    }

    /**
     * Get short display
     * Example: "BRI 1234567890"
     */
    public function getShortDisplayAttribute(): string
    {
        if ($this->platform) {
            return "{$this->platform} {$this->nilai}";
        }
        return $this->nilai;
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by jenis
     */
    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    /**
     * Search by nilai
     */
    public function scopeNilai($query, string $nilai)
    {
        return $query->where('nilai', $nilai);
    }

    /**
     * Find matching identities across all reports
     * Useful for suspect linkage
     */
    public function scopeFindMatching($query, string $jenis, string $nilai)
    {
        return $query->where('jenis', $jenis)->where('nilai', $nilai);
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * Get all jenis options for dropdown
     */
    public static function getJenisOptions(): array
    {
        return [
            self::JENIS_TELEPON => 'Nomor Telepon',
            self::JENIS_REKENING => 'Rekening Bank',
            self::JENIS_SOSMED => 'Media Sosial',
            self::JENIS_EMAIL => 'Email',
            self::JENIS_EWALLET => 'E-Wallet',
            self::JENIS_KRIPTO => 'Kripto/Wallet',
            self::JENIS_MARKETPLACE => 'Marketplace',
            self::JENIS_WEBSITE => 'Website',
            self::JENIS_LAINNYA => 'Lainnya',
        ];
    }

    /**
     * Find all reports linked to this identity
     * Useful for suspect linkage analysis
     */
    public static function findLinkedReports(string $jenis, string $nilai): array
    {
        return static::where('jenis', $jenis)
            ->where('nilai', $nilai)
            ->with('tersangka.laporan')
            ->get()
            ->pluck('tersangka.laporan')
            ->unique('id')
            ->values()
            ->toArray();
    }
}
