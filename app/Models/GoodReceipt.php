<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodReceipt extends Model
{
    protected $table = 'good_receipt';
    protected $fillable = [
        'kode_sph', 'nama_customer', 'po_no', 'po_file', 'sub_total',
        'ppn', 'pbbkb', 'pph', 'total', 'terbilang', 'status', 'revisi_count', 'created_at', 'updated_at'
    ];

    // Relasi ke detail
    public function detail()
    {
        return $this->hasMany(DetailGoodReceipt::class, 'gr_id', 'id');
    }
}
