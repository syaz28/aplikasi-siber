<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jabatan Kepolisian (Police Positions)
 * 
 * Contoh: Kanit, Penyidik, BA PIKET, dll
 * 
 * @property int $id
 * @property string $nama Nama jabatan
 * @property string|null $deskripsi Deskripsi tugas jabatan
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Anggota> $anggota
 */
class Jabatan extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'jabatan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Anggota yang memiliki jabatan ini
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(Anggota::class, 'jabatan_id');
    }
}
