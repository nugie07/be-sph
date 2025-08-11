<?php
// app/Models/WorkflowRemark.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRemark extends Model
{
    // jika nama tabel bukan plural standar, uncomment baris ini:
    protected $table = 'workflow_remark';

    protected $fillable = [
        'wf_id',
        'tipe_trx',
        'wf_comment',
        'last_updateby',
    ];

    public $timestamps = true;
}