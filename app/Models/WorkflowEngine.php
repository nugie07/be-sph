<?php
// app/Models/WorkflowEngine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowEngine extends Model
{
    // jika nama tabel bukan plural standar, uncomment baris ini:
    protected $table = 'workflow_engine';

    // mass‐assignable fields
    protected $fillable = [
        'tipe_trx',
        'first_appr',
        'second_appr',
    ];

    // jika kamu pakai timestamps() di migrasi:
    public $timestamps = true;
}