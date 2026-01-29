<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alamat (Addresses with Denormalized Wilayah)
 * 
 * Alamat orang dengan 4 level kode wilayah untuk fast analytics
 * Setiap orang bisa punya 2 alamat: KTP dan Domisili
 * 
 * @property int $id
 * @property int $orang_id FK ke orang
 * @property string $jenis_alamat ktp|domisili
 * @property string $kode_provinsi Contoh: 33
 * @property string $kode_kabupaten Contoh: 33.74
 * @property string $kode_kecamatan Contoh: 33.74.01
 * @property string $kode_kelurahan Contoh: 33.74.01.1001
 * @property string $detail_alamat Jalan, RT/RW, No. Rumah
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Orang $orang
 * @property-read Wilayah $provinsi
 * @property-read Wilayah $kabupaten
 * @property-read Wilayah $kecamatan
 * @property-read Wilayah $kelurahan
 */
class Alamat extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'alamat';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'orang_id',
        'jenis_alamat',
        'negara',
        'kode_provinsi',
        'kode_kabupaten',
        'kode_kecamatan',
        'kode_kelurahan',
        'detail_alamat',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'orang_id' => 'integer',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Pemilik alamat
     */
    public function orang(): BelongsTo
    {
        return $this->belongsTo(Orang::class, 'orang_id');
    }

    /**
     * Wilayah provinsi
     */
    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_provinsi', 'kode');
    }

    /**
     * Wilayah kabupaten/kota
     */
    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_kabupaten', 'kode');
    }

    /**
     * Wilayah kecamatan
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_kecamatan', 'kode');
    }

    /**
     * Wilayah kelurahan/desa
     */
    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_kelurahan', 'kode');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get full address string
     * Format: Detail Alamat, Kel. X, Kec. Y, Kab/Kota Z, Prov W
     */
    public function getAlamatLengkapAttribute(): string
    {
        $parts = [];
        $parts[] = $this->detail_alamat;
        
        if ($this->kelurahan) {
            $parts[] = "Kel. {$this->kelurahan->nama}";
        }
        if ($this->kecamatan) {
            $parts[] = "Kec. {$this->kecamatan->nama}";
        }
        if ($this->kabupaten) {
            $parts[] = $this->kabupaten->nama;
        }
        if ($this->provinsi) {
            $parts[] = $this->provinsi->nama;
        }

        return implode(', ', $parts);
    }

    /**
     * Get short address (detail + kelurahan + kecamatan)
     */
    public function getAlamatSingkatAttribute(): string
    {
        $parts = [];
        $parts[] = $this->detail_alamat;
        
        if ($this->kelurahan) {
            $parts[] = $this->kelurahan->nama;
        }
        if ($this->kecamatan) {
            $parts[] = $this->kecamatan->nama;
        }

        return implode(', ', $parts);
    }

    /**
     * Get jenis alamat label
     */
    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis_alamat) {
            'ktp' => 'Alamat KTP',
            'domisili' => 'Alamat Domisili',
            default => $this->jenis_alamat,
        };
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by jenis alamat
     */
    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis_alamat', $jenis);
    }

    /**
     * Filter by provinsi
     */
    public function scopeProvinsi($query, string $kode)
    {
        return $query->where('kode_provinsi', $kode);
    }

    /**
     * Filter by kabupaten
     */
    public function scopeKabupaten($query, string $kode)
    {
        return $query->where('kode_kabupaten', $kode);
    }
}
