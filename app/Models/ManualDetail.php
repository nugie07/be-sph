<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualDetail extends Model
{
    protected $table = 'manual_details';

    protected $fillable = [
        'menu_id',
        'sequence',
        'title',
        'content',
    ];

    /**
     * Get the manual that owns the detail.
     */
    public function manual()
    {
        return $this->belongsTo(Manual::class, 'menu_id', 'id');
    }
}
