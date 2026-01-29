<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laporan Kejahatan Siber (Cyber Crime Reports)
 * 
 * Tabel utama laporan dengan pelapor, korban (1:N), tersangka (1:N)
 * Lokasi kejadian dengan wilayah denormalized untuk analytics
 * 
 * @property int $id
 * @property string|null $nomor_stpa Nomor STPA (opsional)
 * @property \Carbon\Carbon $tanggal_laporan Tanggal laporan
 * @property int $pelapor_id FK ke orang (pelapor)
 * @property string $hubungan_pelapor Hubungan dengan korban
 * @property int $petugas_id FK ke anggota (petugas penerima)
 * @property int $kategori_kejahatan_id FK ke kategori_kejahatan
 * @property string|null $kode_provinsi_kejadian Lokasi kejadian - provinsi
 * @property string|null $kode_kabupaten_kejadian Lokasi kejadian - kabupaten
 * @property string|null $kode_kecamatan_kejadian Lokasi kejadian - kecamatan
 * @property string|null $kode_kelurahan_kejadian Lokasi kejadian - kelurahan
 * @property string|null $alamat_kejadian Detail alamat kejadian
 * @property \Carbon\Carbon $waktu_kejadian Waktu kejadian
 * @property string $modus Modus operandi
 * @property string $status Status laporan
 * @property string|null $catatan Catatan tambahan
 * @property int|null $created_by FK ke users
 * @property int|null $updated_by FK ke users
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Orang $pelapor
 * @property-read Anggota $petugas
 * @property-read KategoriKejahatan $kategoriKejahatan
 * @property-read User|null $createdBy
 * @property-read User|null $updatedBy
 * @property-read Wilayah|null $provinsiKejadian
 * @property-read Wilayah|null $kabupatenKejadian
 * @property-read Wilayah|null $kecamatanKejadian
 * @property-read Wilayah|null $kelurahanKejadian
 * @property-read \Illuminate\Database\Eloquent\Collection<Korban> $korban
 * @property-read \Illuminate\Database\Eloquent\Collection<Tersangka> $tersangka
 * @property-read \Illuminate\Database\Eloquent\Collection<Lampiran> $lampiran
 */
class Laporan extends Model
{
    /**
     * Status enum values (new workflow)
     */
    public const STATUS_PENYELIDIKAN = 'Penyelidikan';
    public const STATUS_PENYIDIKAN = 'Penyidikan';
    public const STATUS_TAHAP_I = 'Tahap I';
    public const STATUS_TAHAP_II = 'Tahap II';
    public const STATUS_SP3 = 'SP3';
    public const STATUS_RJ = 'RJ';
    public const STATUS_DIVERSI = 'Diversi';

    /**
     * Hubungan pelapor enum values
     */
    public const HUBUNGAN_DIRI_SENDIRI = 'diri_sendiri';
    public const HUBUNGAN_KELUARGA = 'keluarga';
    public const HUBUNGAN_KUASA_HUKUM = 'kuasa_hukum';
    public const HUBUNGAN_TEMAN = 'teman';
    public const HUBUNGAN_REKAN_KERJA = 'rekan_kerja';
    public const HUBUNGAN_LAINNYA = 'lainnya';

    /**
     * The table associated with the model.
     */
    protected $table = 'laporan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nomor_stpa',
        'tanggal_laporan',
        'pelapor_id',
        'hubungan_pelapor',
        'petugas_id',
        'kategori_kejahatan_id',
        'kode_provinsi_kejadian',
        'kode_kabupaten_kejadian',
        'kode_kecamatan_kejadian',
        'kode_kelurahan_kejadian',
        'alamat_kejadian',
        'waktu_kejadian',
        'modus',
        'status',
        'catatan',
        'assigned_subdit',
        'assigned_by',
        'assigned_at',
        'disposisi_subdit',
        'disposisi_unit',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'tanggal_laporan' => 'datetime',
            'waktu_kejadian' => 'datetime',
            'pelapor_id' => 'integer',
            'petugas_id' => 'integer',
            'kategori_kejahatan_id' => 'integer',
            'assigned_subdit' => 'integer',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Pelapor (orang yang melapor)
     */
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Orang::class, 'pelapor_id');
    }

    /**
     * Petugas penerima laporan
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * Kategori kejahatan
     */
    public function kategoriKejahatan(): BelongsTo
    {
        return $this->belongsTo(KategoriKejahatan::class, 'kategori_kejahatan_id');
    }

    /**
     * User pembuat laporan
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User terakhir update
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * User yang melakukan assignment subdit
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Wilayah kejadian relationships
    public function provinsiKejadian(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_provinsi_kejadian', 'kode');
    }

    public function kabupatenKejadian(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_kabupaten_kejadian', 'kode');
    }

    public function kecamatanKejadian(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_kecamatan_kejadian', 'kode');
    }

    public function kelurahanKejadian(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'kode_kelurahan_kejadian', 'kode');
    }

    /**
     * Korban dalam laporan ini (1:N)
     */
    public function korban(): HasMany
    {
        return $this->hasMany(Korban::class, 'laporan_id');
    }

    /**
     * Tersangka dalam laporan ini (1:N)
     */
    public function tersangka(): HasMany
    {
        return $this->hasMany(Tersangka::class, 'laporan_id');
    }

    /**
     * Lampiran bukti (1:N)
     */
    public function lampiran(): HasMany
    {
        return $this->hasMany(Lampiran::class, 'laporan_id');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get status label in Indonesian
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENYELIDIKAN => 'Penyelidikan',
            self::STATUS_PENYIDIKAN => 'Penyidikan',
            self::STATUS_TAHAP_I => 'Tahap I',
            self::STATUS_TAHAP_II => 'Tahap II',
            self::STATUS_SP3 => 'SP3',
            self::STATUS_RJ => 'Restorative Justice',
            self::STATUS_DIVERSI => 'Diversi',
            default => $this->status,
        };
    }

    /**
     * Get hubungan pelapor label
     */
    public function getHubunganPelaporLabelAttribute(): string
    {
        return match ($this->hubungan_pelapor) {
            self::HUBUNGAN_DIRI_SENDIRI => 'Diri Sendiri',
            self::HUBUNGAN_KELUARGA => 'Keluarga / Kerabat',
            self::HUBUNGAN_KUASA_HUKUM => 'Kuasa Hukum / Pengacara',
            self::HUBUNGAN_REKAN_KERJA => 'Rekan Kerja / Perusahaan',
            self::HUBUNGAN_LAINNYA => 'Lainnya',
            default => $this->hubungan_pelapor,
        };
    }

    /**
     * Get lokasi kejadian lengkap
     */
    public function getLokasiKejadianLengkapAttribute(): string
    {
        $parts = [];
        
        if ($this->alamat_kejadian) {
            $parts[] = $this->alamat_kejadian;
        }
        if ($this->kelurahanKejadian) {
            $parts[] = "Kel. {$this->kelurahanKejadian->nama}";
        }
        if ($this->kecamatanKejadian) {
            $parts[] = "Kec. {$this->kecamatanKejadian->nama}";
        }
        if ($this->kabupatenKejadian) {
            $parts[] = $this->kabupatenKejadian->nama;
        }
        if ($this->provinsiKejadian) {
            $parts[] = $this->provinsiKejadian->nama;
        }

        return implode(', ', $parts) ?: '-';
    }

    /**
     * Get total kerugian from all korban
     */
    public function getTotalKerugianAttribute(): float
    {
        return $this->korban->sum('kerugian_nominal');
    }

    /**
     * Get jumlah korban
     */
    public function getJumlahKorbanAttribute(): int
    {
        return $this->korban->count();
    }

    /**
     * Get jumlah tersangka
     */
    public function getJumlahTersangkaAttribute(): int
    {
        return $this->tersangka->count();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by kategori kejahatan
     */
    public function scopeKategoriKejahatan($query, int $kategoriId)
    {
        return $query->where('kategori_kejahatan_id', $kategoriId);
    }

    /**
     * Filter by provinsi kejadian
     */
    public function scopeProvinsiKejadian($query, string $kode)
    {
        return $query->where('kode_provinsi_kejadian', $kode);
    }

    /**
     * Filter by date range
     */
    public function scopeTanggalLaporanBetween($query, $start, $end)
    {
        return $query->whereBetween('tanggal_laporan', [$start, $end]);
    }

    /**
     * Get all status options for dropdown
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENYELIDIKAN => 'Penyelidikan',
            self::STATUS_PENYIDIKAN => 'Penyidikan',
            self::STATUS_TAHAP_I => 'Tahap I',
            self::STATUS_TAHAP_II => 'Tahap II',
            self::STATUS_SP3 => 'SP3',
            self::STATUS_RJ => 'Restorative Justice',
            self::STATUS_DIVERSI => 'Diversi',
        ];
    }

    /**
     * Get all hubungan pelapor options for dropdown
     */
    public static function getHubunganPelaporOptions(): array
    {
        return [
            self::HUBUNGAN_DIRI_SENDIRI => 'Diri Sendiri',
            self::HUBUNGAN_KELUARGA => 'Keluarga / Kerabat',
            self::HUBUNGAN_KUASA_HUKUM => 'Kuasa Hukum / Pengacara',
            self::HUBUNGAN_REKAN_KERJA => 'Rekan Kerja / Perusahaan',
            self::HUBUNGAN_LAINNYA => 'Lainnya',
        ];
    }
}
