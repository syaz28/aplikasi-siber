<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MasterCountry Model
 * 
 * Represents countries with phone codes for WNA dropdown
 * 
 * @property int $id
 * @property string $alpha_2
 * @property string $alpha_3
 * @property string $name
 * @property string|null $phone_code
 */
class MasterCountry extends Model
{
    use HasFactory;

    protected $table = 'master_countries';

    public $timestamps = false;

    protected $fillable = [
        'alpha_2',
        'alpha_3',
        'name',
        'phone_code',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
