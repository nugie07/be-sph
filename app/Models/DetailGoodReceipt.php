<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailGoodReceipt extends Model
{
    protected $table = 'detail_good_receipt';
    protected $fillable = [
        'gr_id', 'nama_item', 'qty', 'per_item', 'total_harga', 'created_at', 'updated_at'
    ];

    // Relasi ke master
    public function goodReceipt()
    {
        return $this->belongsTo(GoodReceipt::class, 'gr_id', 'id');
    }
}