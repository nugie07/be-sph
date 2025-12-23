<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_order';
    protected $guarded = [];
    public $timestamps = true; // atau true jika ada created_at/updated_at
}