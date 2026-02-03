<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Personel (Police Personnel Data)
 * 
 * Stores actual police officer data separated from authentication.
 * Used for Pawas selection, Penyidik assignment, and personnel management.
 * 
 * @property int $id
 * @property string $nrp Nomor Registrasi Pokok (unique identifier)
 * @property string $nama_lengkap Full name
 * @property string|null $pangkat Rank (AKBP, Kompol, AKP, etc.)
 * @property string|null $jabatan Position/function
 * @property int|null $subdit_id Subdit assignment (1=Ekonomi, 2=Sosial, 3=Khusus)
 * @property string|null $unit_id Unit/work unit
 * @property string|null $telepon Phone number
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Personel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nrp',
        'nama_lengkap',
        'pangkat',
        'subdit_id',
        'unit_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subdit_id' => 'integer',
            'unit_id' => 'integer',
        ];
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get formatted name with rank
     */
    public function getFormattedNameAttribute(): string
    {
        return $this->pangkat 
            ? "{$this->pangkat} {$this->nama_lengkap}" 
            : $this->nama_lengkap;
    }

    /**
     * Get subdit name
     */
    public function getSubditNameAttribute(): ?string
    {
        return match($this->subdit_id) {
            1 => 'Siber Ekonomi',
            2 => 'Siber Sosial',
            3 => 'Siber Khusus',
            default => null,
        };
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope query to specific subdit
     */
    public function scopeSubdit($query, int $subditId)
    {
        return $query->where('subdit_id', $subditId);
    }

    /**
     * Scope query to search by name or NRP
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('nama_lengkap', 'like', "%{$term}%")
              ->orWhere('nrp', 'like', "%{$term}%");
        });
    }
}
