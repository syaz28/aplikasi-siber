<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MasterPendidikan (Education Level Master Data)
 * 
 * Standardized education level list for analytics.
 * 
 * @property int $id
 * @property string $nama Nama tingkat pendidikan
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MasterPendidikan extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'master_pendidikan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama',
    ];
}
