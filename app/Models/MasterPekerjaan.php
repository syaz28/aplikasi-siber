<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MasterPekerjaan (Occupation Master Data)
 * 
 * Standardized occupation list for analytics.
 * 
 * @property int $id
 * @property string $nama Nama pekerjaan standar
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MasterPekerjaan extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'master_pekerjaan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama',
    ];

    /**
     * Search by name
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('nama', 'like', "%{$term}%");
    }
}
