<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTrxSph extends Model
{
    protected $table = 'data_trx_sph';

    protected $fillable = [
        'tipe_sph','kode_sph', 'comp_name', 'pic', 'contact_no',
        'product', 'price_liter', 'biaya_lokasi',
        'ppn', 'pbbkb', 'total_price', 'pay_method',
        'susut', 'note_berlaku', 'status','file_sph'
    ];
}