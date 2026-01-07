<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manual extends Model
{
    protected $table = 'manual';

    protected $fillable = [
        'title',
        'sequence',
        'status',
    ];

    /**
     * Get the manual details for the manual.
     */
    public function details()
    {
        return $this->hasMany(ManualDetail::class, 'menu_id', 'id')->orderBy('sequence', 'asc');
    }
}
