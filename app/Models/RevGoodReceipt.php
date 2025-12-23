<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevGoodReceipt extends Model
{
    protected $table = 'rev_good_receipt';
    protected $fillable = [
        'gr_id', 'old_po', 'created_at', 'updated_at'
    ];

    // Relasi ke GoodReceipt
    public function goodReceipt()
    {
        return $this->belongsTo(GoodReceipt::class, 'gr_id', 'id');
    }
}
