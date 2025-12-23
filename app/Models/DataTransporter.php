<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTransporter extends Model
{
    protected $table = 'data_supplier_transporter';

    protected $fillable = [
        'nama',
        'pic',
        'contact_no',
        'email',
        'address',
        'status',
    ];
}