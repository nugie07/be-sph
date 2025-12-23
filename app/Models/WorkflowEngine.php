<?php
// app/Models/WorkflowEngine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class WorkflowEngine extends Model
{
    protected $table = 'workflow_engine';

    protected $fillable = [
        'tipe_trx',
        'first_appr',
        'second_appr',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    // Relationship dengan roles untuk first_appr
    public function firstApprover()
    {
        return $this->belongsTo(Role::class, 'first_appr', 'id');
    }

    // Relationship dengan roles untuk second_appr
    public function secondApprover()
    {
        return $this->belongsTo(Role::class, 'second_appr', 'id');
    }
}
