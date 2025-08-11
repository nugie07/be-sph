<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DataTrxSph;
use Spatie\Permission\Models\Role;

class WorkflowRecord extends Model
{
    // jika tabel bernama 'workflow_record' (tidak plural), harus didefinisikan:
    protected $table = 'workflow_record';

    // kolom yang boleh di‐mass‐assign
    protected $fillable = [
        'trx_id',
        'tipe_trx',
        'curr_role',
        'next_role',
        'wf_status',
    ];

    /**
     * Relasi ke SPH (data_trx_sph)
     */
    public function sph()
    {
        return $this->belongsTo(DataTrxSph::class, 'trx_id');
    }

    /**
     * Relasi ke Role untuk curr_role
     */
    public function currentRole()
    {
        return $this->belongsTo(Role::class, 'curr_role');
    }

    /**
     * Relasi ke Role untuk next_role (jika perlu)
     */
    public function nextRole()
    {
        return $this->belongsTo(Role::class, 'next_role');
    }
}