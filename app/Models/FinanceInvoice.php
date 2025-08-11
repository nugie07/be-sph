<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceInvoice extends Model
{
    use HasFactory;

    protected $table = 'finance_invoice';

    protected $fillable = [
        'drs_no',
        'drs_unique',
        'bast_id',
        'invoice_no',
        'invoice_date',
        'terms',
        'po_no',
        'bill_to',
        'ship_to',
        'fob',
        'sent_date',
        'sent_via',
        'sub_total',
        'ppn',
        'pbbkb',
        'pph',
        'total',
        'terbilang',
        'status',
        'created_by',
    ];
    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id', 'id');
    }
}