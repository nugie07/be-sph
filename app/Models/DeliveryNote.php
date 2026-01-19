<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $table = 'delivery_note'; // Jika nama tabel bukan jamak (delivery_notes)

        protected $fillable = [
        'dn_no',
        'drs_no',
        'drs_unique',
        'customer_po',
        'customer_name',
        'po_date',
        'arrival_date',
        'consignee',
        'delivery_to',
        'address',
        'qty',
        'unit',
        'description',
        'segel_atas',
        'segel_bawah',
        'nopol',
        'driver_name',
        'transportir',
        'so',
        'terra',
        'berat_jenis',
        'temperature',
        'tgl_bongkar',
        'jam_mulai',
        'jam_akhir',
        'meter_awal',
        'meter_akhir',
        'tinggi_sounding',
        'jenis_suhu',
        'volume_diterima',
        'dn_file',
        'created_by',
        'status',
        'file',
    ];
}