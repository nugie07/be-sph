<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCustomer extends Model
{
    use HasFactory;

    protected $table = 'master_customer';

    protected $fillable = [
        'cust_code',
        'alias',
        'type',
        'name',
        'address',
        'pic_name',
        'pic_contact',
        'email',
        'pay_terms',
        'fob',
        'delivery_method',
        'bill_to',
        'ship_to',
        'status'
    ];
}