<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterLov extends Model
{
    use HasFactory;

    protected $table = 'master_lov';

    protected $fillable = [
        'code', 'value', 'parent_id'
    ];
    
    public $timestamps = false; // Biasanya setting tidak pakai timestamps

    // Optional: helper method untuk get value by key
    public static function getValue($key, $default = null)
    {
        return MasterLov::where('code', $key)->value('value') ?? $default;
    }
}