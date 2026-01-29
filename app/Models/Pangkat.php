<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pangkat Model
 * 
 * Master data untuk pangkat anggota Kepolisian Republik Indonesia
 *
 * @property int $id
 * @property string $kode Kode pangkat: AKBP, KOMPOL, AKP, dll
 * @property int $urutan Urutan hierarki: 1=tertinggi
 */
class Pangkat extends Model
{
    use HasFactory;

    protected $table = 'pangkat';

    protected $fillable = [
        'kode',
        'urutan',
    ];

    /**
     * Get all pangkat as dropdown options.
     * 
     * @return array
     */
    public static function getDropdownOptions(): array
    {
        return self::orderBy('urutan', 'asc')
            ->get()
            ->map(fn ($p) => [
                'value' => $p->kode,
                'label' => $p->kode,
            ])
            ->toArray();
    }
}
