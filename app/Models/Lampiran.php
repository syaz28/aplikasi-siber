<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Lampiran (Evidence Attachments)
 * 
 * Lampiran bukti per laporan (1:N)
 * File disimpan di storage, path disimpan di database
 * 
 * @property int $id
 * @property int $laporan_id FK ke laporan
 * @property string $nama_file Nama file asli
 * @property string $path_file Path file di storage
 * @property string $jenis_file gambar|dokumen|screenshot|video|audio|lainnya
 * @property int|null $ukuran_file Ukuran file dalam bytes
 * @property string|null $deskripsi Deskripsi lampiran
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Laporan $laporan
 */
class Lampiran extends Model
{
    /**
     * Jenis file enum values
     */
    public const JENIS_GAMBAR = 'gambar';
    public const JENIS_DOKUMEN = 'dokumen';
    public const JENIS_SCREENSHOT = 'screenshot';
    public const JENIS_VIDEO = 'video';
    public const JENIS_AUDIO = 'audio';
    public const JENIS_LAINNYA = 'lainnya';

    /**
     * The table associated with the model.
     */
    protected $table = 'lampiran';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'laporan_id',
        'nama_file',
        'path_file',
        'jenis_file',
        'ukuran_file',
        'deskripsi',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'laporan_id' => 'integer',
            'ukuran_file' => 'integer',
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

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get jenis file label
     */
    public function getJenisFileLabelAttribute(): string
    {
        return match ($this->jenis_file) {
            self::JENIS_GAMBAR => 'Gambar',
            self::JENIS_DOKUMEN => 'Dokumen',
            self::JENIS_SCREENSHOT => 'Screenshot',
            self::JENIS_VIDEO => 'Video',
            self::JENIS_AUDIO => 'Audio',
            self::JENIS_LAINNYA => 'Lainnya',
            default => $this->jenis_file,
        };
    }

    /**
     * Get formatted file size
     */
    public function getUkuranFormattedAttribute(): string
    {
        if (!$this->ukuran_file) {
            return '-';
        }

        $bytes = $this->ukuran_file;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get full URL to file
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path_file);
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->nama_file, PATHINFO_EXTENSION);
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->jenis_file, [self::JENIS_GAMBAR, self::JENIS_SCREENSHOT]);
    }

    /**
     * Check if file is a video
     */
    public function getIsVideoAttribute(): bool
    {
        return $this->jenis_file === self::JENIS_VIDEO;
    }

    /**
     * Check if file exists in storage
     */
    public function getExistsAttribute(): bool
    {
        return Storage::exists($this->path_file);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Filter by jenis file
     */
    public function scopeJenisFile($query, string $jenis)
    {
        return $query->where('jenis_file', $jenis);
    }

    /**
     * Only images
     */
    public function scopeImages($query)
    {
        return $query->whereIn('jenis_file', [self::JENIS_GAMBAR, self::JENIS_SCREENSHOT]);
    }

    /**
     * Only documents
     */
    public function scopeDocuments($query)
    {
        return $query->where('jenis_file', self::JENIS_DOKUMEN);
    }

    // ========================================
    // STATIC METHODS
    // ========================================

    /**
     * Get all jenis file options for dropdown
     */
    public static function getJenisFileOptions(): array
    {
        return [
            self::JENIS_GAMBAR => 'Gambar',
            self::JENIS_DOKUMEN => 'Dokumen',
            self::JENIS_SCREENSHOT => 'Screenshot',
            self::JENIS_VIDEO => 'Video',
            self::JENIS_AUDIO => 'Audio',
            self::JENIS_LAINNYA => 'Lainnya',
        ];
    }

    /**
     * Determine jenis file from mime type
     */
    public static function determineJenisFromMime(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => self::JENIS_GAMBAR,
            str_starts_with($mimeType, 'video/') => self::JENIS_VIDEO,
            str_starts_with($mimeType, 'audio/') => self::JENIS_AUDIO,
            in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) => self::JENIS_DOKUMEN,
            default => self::JENIS_LAINNYA,
        };
    }
}
