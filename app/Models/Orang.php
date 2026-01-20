<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Orang (Generic Person Entity)
 * 
 * Tabel generik untuk pelapor, korban, dan tersangka
 * NIK dan telepon di-encrypt untuk keamanan data
 * 
 * @property int $id
 * @property string $nik NIK 16 digit (encrypted)
 * @property string $nama Nama lengkap
 * @property string $tempat_lahir Tempat lahir
 * @property \Carbon\Carbon $tanggal_lahir Tanggal lahir
 * @property string $jenis_kelamin Laki-laki|Perempuan
 * @property string $pekerjaan Pekerjaan
 * @property string $telepon Nomor telepon (encrypted)
 * @property string|null $email Email opsional
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Alamat> $alamat
 * @property-read Alamat|null $alamatKtp
 * @property-read Alamat|null $alamatDomisili
 * @property-read \Illuminate\Database\Eloquent\Collection<Laporan> $laporanSebagaiPelapor
 * @property-read \Illuminate\Database\Eloquent\Collection<Korban> $sebagaiKorban
 * @property-read \Illuminate\Database\Eloquent\Collection<Tersangka> $sebagaiTersangka
 */
class Orang extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'orang';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nik',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pekerjaan',
        'telepon',
        'email',
    ];

    /**
     * The attributes that should be cast.
     * 
     * Note: NIK and telepon are stored as plain text for now
     * because encrypted values can't be searched with updateOrCreate()
     * TODO: Implement searchable encryption (hash index + encrypted value)
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            // 'nik' => 'encrypted', // Disabled: can't search encrypted values
            // 'telepon' => 'encrypted', // Disabled: can't search encrypted values
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Semua alamat orang ini
     */
    public function alamat(): HasMany
    {
        return $this->hasMany(Alamat::class, 'orang_id');
    }

    /**
     * Alamat sesuai KTP
     */
    public function alamatKtp(): HasOne
    {
        return $this->hasOne(Alamat::class, 'orang_id')->where('jenis_alamat', 'ktp');
    }

    /**
     * Alamat domisili
     */
    public function alamatDomisili(): HasOne
    {
        return $this->hasOne(Alamat::class, 'orang_id')->where('jenis_alamat', 'domisili');
    }

    /**
     * Laporan dimana orang ini sebagai pelapor
     */
    public function laporanSebagaiPelapor(): HasMany
    {
        return $this->hasMany(Laporan::class, 'pelapor_id');
    }

    /**
     * Record korban (orang ini sebagai korban)
     */
    public function sebagaiKorban(): HasMany
    {
        return $this->hasMany(Korban::class, 'orang_id');
    }

    /**
     * Record tersangka (orang ini sebagai tersangka)
     */
    public function sebagaiTersangka(): HasMany
    {
        return $this->hasMany(Tersangka::class, 'orang_id');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get formatted NIK (show only last 4 digits for security display)
     */
    public function getNikMaskedAttribute(): string
    {
        $nik = $this->nik;
        if (strlen($nik) === 16) {
            return '****-****-****-' . substr($nik, -4);
        }
        return '****';
    }

    /**
     * Get formatted phone (show only last 4 digits for security display)
     */
    public function getTeleponMaskedAttribute(): string
    {
        $tel = $this->telepon;
        if (strlen($tel) >= 4) {
            return '****-' . substr($tel, -4);
        }
        return '****';
    }

    /**
     * Get age from tanggal_lahir
     */
    public function getUsiaAttribute(): int
    {
        return $this->tanggal_lahir?->age ?? 0;
    }

    /**
     * Get formatted tempat, tanggal lahir
     */
    public function getTtlAttribute(): string
    {
        $tanggal = $this->tanggal_lahir?->translatedFormat('d F Y') ?? '-';
        return "{$this->tempat_lahir}, {$tanggal}";
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Search by name
     */
    public function scopeSearchNama($query, string $nama)
    {
        return $query->where('nama', 'like', "%{$nama}%");
    }
}
