<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSysLog extends Model
{
    protected $table = 'user_sys_log';

    protected $fillable = [
        'id',
        'user_id',
        'user_name',
        'services',
        'activity',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
