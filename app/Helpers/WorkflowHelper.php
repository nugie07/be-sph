<?php

namespace App\Helpers;

use App\Models\WorkflowEngine;
use App\Models\WorkflowRecord;
use App\Models\WorkflowRemark;
use Carbon\Carbon;

class WorkflowHelper
{
    /**
     * Buat workflow record + remark sekaligus.
     *
     * @param  int    $trxId      ID record transaksi (misal SPH, DRS, PO, dst)
     * @param  string $tipeTrx    Tipe transaksi, sesuai kolom workflow_engine.tipe_trx
     * @param  string $comment    Pesan remark (boleh dengan placeholder User / Waktu)
     * @param  string $userName   Nama user atau identifier yang men-trigger
     * @return \App\Models\WorkflowRecord|null
     */
public static function createWorkflowWithRemark(int $trxId, string $tipeTrx, string $comment, string $userName)
    {
        // 1) Ambil config engine
        $engine = WorkflowEngine::where('tipe_trx', $tipeTrx)->first();
        if (! $engine) {
            return null;
        }

        // 2) Simpan workflow_record
        $workflowRecord = WorkflowRecord::create([
            'trx_id'    => $trxId,
            'tipe_trx'  => $engine->tipe_trx,
            'curr_role' => $engine->first_appr,
            'next_role' => $engine->second_appr,
            'wf_status' => 1,
        ]);

        // 3) Simpan workflow_remark
        $now       = Carbon::now()->format('Y-m-d H:i:s');
        $fullComment = str_replace(
            ['{user}', '{time}'],
            [$userName, $now],
            $comment
        );

        WorkflowRemark::create([
            'wf_id'         => $workflowRecord->id,
            'tipe_trx'      => $engine->tipe_trx,
            'wf_comment'    => $fullComment,
            'last_updateby' => $userName,
        ]);

        return $workflowRecord;
    }
public static function getRemarks(int $trxId, string $tipeTrx)
    {
        return WorkflowRemark::join('workflow_record as b', 'workflow_remark.wf_id', '=', 'b.id')
            ->where('b.tipe_trx', $tipeTrx)
            ->where('b.trx_id', $trxId)
            ->orderBy('workflow_remark.created_at', 'asc')
            ->get([
                'workflow_remark.wf_comment as comment',
                'workflow_remark.created_at',
                'workflow_remark.last_updateby as user'
            ]);
    }

}