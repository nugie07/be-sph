<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryRequest extends Model
{
    protected $table = 'delivery_request';

    protected $fillable = [
        'drs_no',
        'drs_unique',
        'customer_name',
        'po_number',
        'po_date',
        'source',
        'volume',
        'truck_capacity',
        'request_date',
        'transporter_name',
        'wilayah',
        'dn_no',
        'site_location',
        'delivery_note',
        'pic_site',
        'pic_site_telp',
        'requested_by',
        'additional_note',
        'file_drs',
        'created_by',
    ];
}