<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataProduct extends Model
{
    use HasFactory;

    protected $table = 'data_product';

    protected $fillable = [
        'product_name',
        'price',
        'status'
    ];
}