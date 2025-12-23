<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterWilayah extends Model
{
    protected $table = 'master_wilayah';

    protected $fillable = [
        'nama',
        'value',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
