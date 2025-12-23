<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Helpers\AuthValidator;
use App\Helpers\ApiLog;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\SphNotificationMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

use App\Models\MasterCustomer;
use App\Models\DataProduct;
use App\Models\DataTrxSph;
use App\Models\WorkflowRecord;
use App\Models\WorkflowRemark;
use App\Models\User;
use App\Helpers\WorkflowHelper;
use App\Helpers\UserSysLogHelper;

class SphController extends Controller
{
public function getCustomers(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);

            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $type = $request->type;

        $customers = DB::table('master_customer as mc')
            ->leftJoin('sph_template as st', 'st.id', '=', 'mc.template_id')
            ->where('mc.type', $type)
            ->where('mc.status', 1)
            ->select('mc.id', 'mc.name', 'mc.template_id', 'st.form')
            ->get();

        return response()->json($customers);
    }

public function updateSph(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $userId = $result['id'] ?? null;
        $user = $userId ? User::find($userId) : null;
        $fullName  = $user ? "{$user->first_name} {$user->last_name}" : 'System';
        $updaterId = $user->id ?? $userId;

        // Accept alias: if FE sends 'id' for SPH record, normalize to 'sph_id'
        if (!$request->has('sph_id') && $request->has('id')) {
            $request->merge(['sph_id' => $request->id]);
        }

        $validated = $request->validate([
            'sph_id'       => 'required|integer|exists:data_trx_sph,id',
            'template_id'  => 'required|integer|exists:sph_template,id',
            'tipe_sph'     => 'nullable|string',
            'kode_sph'     => 'nullable|string',
            'comp_name'    => 'nullable|string',
            'pic'          => 'nullable|string',
            'contact_no'   => 'nullable|string',
            'product'      => 'nullable|string',
            'price_liter'  => 'nullable|numeric',
            'biaya_lokasi' => 'nullable|string',
            'ppn'          => 'nullable|numeric',
            'pbbkb'        => 'nullable|numeric',
            'total_price'  => 'nullable|numeric',
            'pay_method'   => 'nullable|string',
            'susut'        => 'nullable|string',
            'note_berlaku' => 'nullable|string',
            'oat'          => 'nullable|numeric',
            'ppn_oat'      => 'nullable|numeric',
            'oat_lokasi'   => 'nullable|string',
            // optional details when has_details=1
            'details'      => 'nullable|array',
            'details.*.cname_lname' => 'nullable|string',
            'details.*.product'     => 'nullable|string',
            'details.*.biaya_lokasi'=> 'nullable|string',
            'details.*.qty'         => 'nullable|integer',
            'details.*.price_liter' => 'nullable|numeric',
            'details.*.ppn'         => 'nullable|numeric',
            'details.*.pbbkb'       => 'nullable|numeric',
            'details.*.transport'   => 'nullable|numeric',
            'details.*.total_price' => 'nullable|numeric',
            'details.*.grand_total' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            // determine has_details
            $tpl = DB::table('sph_template')->select('has_details')->where('id', $validated['template_id'])->first();
            $hasDetails = isset($tpl->has_details) ? (int) $tpl->has_details : 0;

            // update header
            $update = [
                'tipe_sph'     => $validated['tipe_sph'] ?? null,
                'kode_sph'     => $validated['kode_sph'] ?? null,
                'comp_name'    => $validated['comp_name'] ?? null,
                'pic'          => $validated['pic'] ?? null,
                'contact_no'   => $validated['contact_no'] ?? null,
                'product'      => $validated['product'] ?? null,
                'price_liter'  => $validated['price_liter'] ?? null,
                'biaya_lokasi' => $validated['biaya_lokasi'] ?? null,
                'ppn'          => $validated['ppn'] ?? null,
                'pbbkb'        => $validated['pbbkb'] ?? null,
                'total_price'  => $validated['total_price'] ?? null,
                'oat'          => $validated['oat'] ?? null,
                'ppn_oat'      => $validated['ppn_oat'] ?? null,
                'oat_lokasi'   => $validated['oat_lokasi'] ?? null,
                'pay_method'   => $validated['pay_method'] ?? null,
                'susut'        => $validated['susut'] ?? null,
                'note_berlaku' => $validated['note_berlaku'] ?? null,
                'template_id'  => $validated['template_id'],
                'status'       => 1,
                'last_updateby'=> $updaterId,
            ];

            DB::table('data_trx_sph')->where('id', $validated['sph_id'])->update($update);

            if ($hasDetails === 1) {
                // replace details
                DB::table('sph_details')->where('sph_id', $validated['sph_id'])->delete();
                $rows = [];
                $now = now();
                foreach (($validated['details'] ?? []) as $row) {
                    $rows[] = [
                        'sph_id'      => $validated['sph_id'],
                        'cname_lname' => $row['cname_lname'] ?? null,
                        'product'     => $row['product'] ?? null,
                        'biaya_lokasi'=> $row['biaya_lokasi'] ?? null,
                        'qty'         => $row['qty'] ?? null,
                        'price_liter' => $row['price_liter'] ?? null,
                        'ppn'         => $row['ppn'] ?? null,
                        'pbbkb'       => $row['pbbkb'] ?? null,
                        'transport'   => $row['transport'] ?? null,
                        'total_price' => $row['total_price'] ?? null,
                        'grand_total' => $row['grand_total'] ?? null,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::table('sph_details')->insert($rows);
                }
            }

            // Add workflow remark: User Mengajukan Kembali SPH {kode_sph}
            $kodeForRemark = $update['kode_sph'] ?? DB::table('data_trx_sph')->where('id', $validated['sph_id'])->value('kode_sph');
            WorkflowHelper::createWorkflowWithRemark(
                $validated['sph_id'],
                'sph',
                'User Mengajukan Kembali SPH ' . ($kodeForRemark ?? ''),
                $fullName
            );

            DB::commit();
            UserSysLogHelper::logFromAuth($result, 'Sph', 'updateSph');
            return response()->json(['message' => 'SPH berhasil diupdate!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengupdate SPH.', 'error' => $e->getMessage()], 500);
        }
    }
public function sphDetails(Request $request)
    {
        // Support alias: if FE sends 'id' instead of 'sph_id', map it
        if (!$request->has('sph_id') && $request->has('id')) {
            $request->merge(['sph_id' => $request->id]);
        }

        $request->validate([
            'template_id' => 'required|integer|exists:sph_template,id',
            'sph_id'      => 'required|integer|exists:data_trx_sph,id',
        ]);

        $tpl = DB::table('sph_template')->select('has_details')->where('id', $request->template_id)->first();
        $hasDetails = isset($tpl->has_details) ? (int) $tpl->has_details : 0;

        $header = DB::table('data_trx_sph')->where('id', $request->sph_id)->first();
        if (!$header) {
            return response()->json(['message' => 'SPH not found'], 404);
        }

        // Attach email from master_customer where name = comp_name
        $customerEmail = null;
        if (!empty($header->comp_name)) {
            $customerEmail = DB::table('master_customer')
                ->where('name', $header->comp_name)
                ->value('email');
        }
        $header->email = $customerEmail;

        if ($hasDetails === 1) {
            $details = DB::table('sph_details')->where('sph_id', $request->sph_id)->get();
            // Enrich each detail with pbbkb_persen from master_lov.value where code = biaya_lokasi (case-insensitive)
            $details = $details->map(function($d){
                $code = strtolower(trim($d->biaya_lokasi ?? ''));
                $val = null;
                if ($code !== '') {
                    $val = DB::table('master_lov')->whereRaw('LOWER(code) = ?', [$code])->value('value');
                }
                $d->pbbkb_persen = $val; // keep raw value (e.g., 10.00)
                return $d;
            });
        } else {
            $details = [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'header' => $header,
                'details' => $details,
            ]
        ]);
    }
public function SphValidator(Request $request)
    {
        // Validasi minimal: butuh template_id
        $request->validate([
            'template_id' => 'required|integer|exists:sph_template,id'
        ]);

        // Ambil has_details dari sph_template
        $tpl = DB::table('sph_template')->select('has_details')->where('id', $request->template_id)->first();
        $hasDetails = isset($tpl->has_details) ? (int) $tpl->has_details : 0;

        // Delegasi sesuai has_details
        if ($hasDetails === 1) {
            return $this->SphStoreDetails($request);
        }
        return $this->store($request);
    }
public function SphStoreDetails(Request $request)
    {
        // 1) Validasi token & dapatkan user
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";

        // 2) Validasi input utama (mengikuti store, semua nullable)
        $validated = $request->validate([
            'tipe_sph'     => 'nullable|string',
            'kode_sph'     => 'nullable|string',
            'comp_name'    => 'nullable|string',
            'pic'          => 'nullable|string',
            'contact_no'   => 'nullable|string',
            'product'      => 'nullable|string',
            'price_liter'  => 'nullable|numeric',
            'biaya_lokasi' => 'nullable|string',
            'ppn'          => 'nullable|numeric',
            'pbbkb'        => 'nullable|numeric',
            'total_price'  => 'nullable|numeric',
            'pay_method'   => 'nullable|string',
            'susut'        => 'nullable|string',
            'note_berlaku' => 'nullable|string',
            'template_id'  => 'nullable|integer|exists:sph_template,id',
            'oat'          => 'nullable|numeric',
            'ppn_oat'      => 'nullable|numeric',
            'oat_lokasi'   => 'nullable|string',

            'details'      => 'required|array',
            'details.*.cname_lname' => 'nullable|string',
            'details.*.product'     => 'nullable|string',
            'details.*.biaya_lokasi'=> 'nullable|string',
            'details.*.qty'         => 'nullable|integer',
            'details.*.price_liter' => 'nullable|numeric',
            'details.*.ppn'         => 'nullable|numeric',
            'details.*.pbbkb'       => 'nullable|numeric',
            'details.*.transport'   => 'nullable|numeric',
            'details.*.total_price' => 'nullable|numeric',
            'details.*.grand_total' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            // a) Simpan SPH utama
            $sph = new DataTrxSph();
            $sph->tipe_sph     = $validated['tipe_sph'] ?? null;
            $sph->kode_sph     = $validated['kode_sph'] ?? null;
            $sph->comp_name    = $validated['comp_name'] ?? null;
            $sph->pic          = $validated['pic'] ?? null;
            $sph->contact_no   = $validated['contact_no'] ?? null;
            $sph->product      = $validated['product'] ?? null;
            $sph->price_liter  = $validated['price_liter'] ?? null;
            $sph->biaya_lokasi = $validated['biaya_lokasi'] ?? null;
            $sph->ppn          = $validated['ppn'] ?? null;
            $sph->pbbkb        = $validated['pbbkb'] ?? null;
            $sph->total_price  = $validated['total_price'] ?? null;
            $sph->oat          = $validated['oat'] ?? null;
            $sph->ppn_oat      = $validated['ppn_oat'] ?? null;
            $sph->pay_method   = $validated['pay_method'] ?? null;
            $sph->susut        = $validated['susut'] ?? null;
            $sph->note_berlaku = $validated['note_berlaku'] ?? null;
            $sph->template_id  = $validated['template_id'] ?? null;
            $sph->oat_lokasi   = $validated['oat_lokasi'] ?? null;
            $sph->created_by   = $fullName;
            $sph->created_by_id   = $user->id;
            $sph->last_updateby = $user->id;
            $sph->status       = 1;
            $sph->save();

            // b) Simpan details (array)
            $now = now();
            $detailRows = [];
            foreach ($validated['details'] as $row) {
                $detailRows[] = [
                    'sph_id'      => $sph->id,
                    'cname_lname' => $row['cname_lname'] ?? null,
                    'product'     => $row['product'] ?? null,
                    'biaya_lokasi'=> $row['biaya_lokasi'] ?? null,
                    'qty'         => $row['qty'] ?? null,
                    'price_liter' => $row['price_liter'] ?? null,
                    'ppn'         => $row['ppn'] ?? null,
                    'pbbkb'       => $row['pbbkb'] ?? null,
                    'transport'   => $row['transport'] ?? null,
                    'total_price' => $row['total_price'] ?? null,
                    'grand_total' => $row['grand_total'] ?? null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
            if (!empty($detailRows)) {
                DB::table('sph_details')->insert($detailRows);
            }

            // c) Workflow + log sama seperti store
            WorkflowHelper::createWorkflowWithRemark(
                $sph->id,
                'sph',
                'User {user} membuat SPH (with details) di {time}',
                $fullName
            );

            DB::commit();

            UserSysLogHelper::logFromAuth($result, 'Sph', 'SphStoreDetails');

            return response()->json([
                'message' => 'SPH dan details berhasil disimpan!',
                'sph_id'  => $sph->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan SPH & details.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
public function getProducts()
    {
        $products = DataProduct::where('status', 1)
            ->get(['id', 'product_name', 'price']);

        return response()->json($products);
    }

public function getCustomerDetail(Request $request)
        {
         $result = AuthValidator::validateTokenAndClient($request);

            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $customer = MasterCustomer::where('id', $request->id)->first();
        return response()->json([
            'cust_code' => $customer->cust_code,
            'alias'     => $customer->alias,
            'type'      => $customer->type,
            'pic_name'  => $customer->pic_name,
            'pic_contact' => $customer->pic_contact,
            'email'    => $customer->email,
        ]);
        }
public function store(Request $request)
    {
        // 1) Validasi token & dapatkan user
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";

        // 2) Validasi input (semua nullable sesuai permintaan)
        $validated = $request->validate([
            'tipe_sph'     => 'nullable|string',
            'kode_sph'     => 'nullable|string',
            'comp_name'    => 'nullable|string',
            'pic'          => 'nullable|string',
            'contact_no'   => 'nullable|string',
            'product'      => 'nullable|string',
            'price_liter'  => 'nullable|numeric',
            'biaya_lokasi' => 'nullable|string',
            'ppn'          => 'nullable|numeric',
            'pbbkb'        => 'nullable|numeric',
            'total_price'  => 'nullable|numeric',
            'pay_method'   => 'nullable|string',
            'susut'        => 'nullable|string',
            'note_berlaku' => 'nullable|string',
            'template_id'  => 'nullable|integer|exists:sph_template,id',
            'oat'          => 'nullable|numeric',
            'ppn_oat'      => 'nullable|numeric',
            'oat_lokasi'   => 'nullable|string',
        ]);

        // 3) Mulai transaksi
        DB::beginTransaction();
        try {
            // a) Simpan SPH
            $sph = new DataTrxSph();
            $sph->tipe_sph     = $validated['tipe_sph'] ?? null;
            $sph->kode_sph     = $validated['kode_sph'] ?? null;
            $sph->comp_name    = $validated['comp_name'] ?? null;
            $sph->pic          = $validated['pic'] ?? null;
            $sph->contact_no   = $validated['contact_no'] ?? null;
            $sph->product      = $validated['product'] ?? null;
            $sph->price_liter  = $validated['price_liter'] ?? null;
            $sph->biaya_lokasi = $validated['biaya_lokasi'] ?? null;
            $sph->ppn          = $validated['ppn'] ?? null;
            $sph->pbbkb        = $validated['pbbkb'] ?? null;
            $sph->total_price  = $validated['total_price'] ?? null;
            $sph->oat          = $validated['oat'] ?? null;
            $sph->ppn_oat      = $validated['ppn_oat'] ?? null;
            $sph->pay_method   = $validated['pay_method'] ?? null;
            $sph->susut        = $validated['susut'] ?? null;
            $sph->note_berlaku = $validated['note_berlaku'] ?? null;
            $sph->template_id  = $validated['template_id'] ?? null;
            $sph->oat_lokasi   = $validated['oat_lokasi'] ?? null;
            $sph->created_by   = $fullName;
            $sph->created_by_id   = $user->id;
            $sph->last_updateby = $user->id;
            $sph->status       = 1;
            $sph->save();

            // Update master_customer dengan pic_name dan pic_contact
            try {
                $masterCustomer = MasterCustomer::where('name', $sph->comp_name)->first();
                if ($masterCustomer) {
                    $masterCustomer->update([
                        'pic_name' => $sph->pic,
                        'pic_contact' => $sph->contact_no
                    ]);
                }
            } catch (\Exception $e) {
                // Log error tapi tidak menghentikan proses
                Log::warning('Failed to update master_customer', [
                    'comp_name' => $sph->comp_name,
                    'pic' => $sph->pic,
                    'contact_no' => $sph->contact_no,
                    'error' => $e->getMessage()
                ]);
            }

            // Buat workflow record dan remark menggunakan helper
            WorkflowHelper::createWorkflowWithRemark(
                $sph->id,
                'sph',
                'User {user} membuat SPH di {time}',
                $fullName
            );

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Sph', 'store');

            return response()->json(['message' => 'SPH berhasil disimpan!'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan SPH.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

public function list(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $filterId = $result['id'];
        $status = $request->query('status');
        $month = $request->query('month');
        $restrict = $request->query('restrict');

        $query = DataTrxSph::query();
        if ($status === 'waiting') {
            $query->where('status', 1);
        } elseif ($status === 'approved') {
            $query->where('status', 4);
        } elseif ($status === 'revisi') {
            $query->where('status', 2);
        } elseif ($status === 'reject') {
            $query->where('status', 3);
        } elseif ($status === 'approvallist') {
            // Tampilkan data dengan status selain 4 dan 3
            $query->whereNotIn('status', [3, 4]);
        }
        // else 'all' → no filter
        if ($month) {
        [$year, $mon] = explode('-', $month);
        $query->whereYear('created_at', $year)
              ->whereMonth('created_at', $mon);
        }

        // Filter berdasarkan restrict
        if ($restrict == 1) {
            $query->where('created_by_id', $filterId);
        }
        // ambil data SPH sesuai filter
        $sphs = $query->get();

        // prefetch form dari sph_template berdasarkan template_id
        $templateIdMap = $sphs->pluck('template_id')->filter()->unique();
        $templateFormById = collect();
        if ($templateIdMap->isNotEmpty()) {
            $templateFormById = DB::table('sph_template')
                ->whereIn('id', $templateIdMap)
                ->pluck('form', 'id');
        }

        // mapping status code ke teks
        $statusTextMap = [
            1 => 'Menunggu Approval',
            2 => 'Perlu Revisi',
            3 => 'Reject',
            4 => 'Approved',
        ];

        // tambahkan field `workflow` per item dan `pic_name` dari MasterCustomer
        $data = $sphs->map(function($sph) use ($statusTextMap, $templateFormById) {
        $text = $statusTextMap[$sph->status] ?? '';

            // cari workflow_record
            $wr = WorkflowRecord::where('trx_id', $sph->id)
                    ->where('tipe_trx', 'sph')
                    ->first();
            if ($wr && $wr->curr_role) {
                $role = Role::find($wr->curr_role);
                if ($role) {
                    $text .= ' oleh ' . $role->name;
                } else {
                    $text .= 'Tidak ada yang approved, hubungi Admin';
                }
            } else {
                $text .= 'Tidak ada yang approved, hubungi Admin';
            }

            // Ambil pic_name dari MasterCustomer berdasarkan comp_name
            $pic_email = null;
            if ($sph->comp_name) {
                $masterCustomer = MasterCustomer::where('name', $sph->comp_name)->first();
                $pic_email = $masterCustomer ? $masterCustomer->email : null;
            }
            //dd($sph->comp_name);
            // pasang ke atribut baru
            $sph->workflow = $text;
            $sph->pic_email = $pic_email;
            // tambahkan nama form dari sph_template
            $sph->template_form = $templateFormById->get($sph->template_id)
                ?? $templateFormById->get((string) $sph->template_id)
                ?? null;
            return $sph;
        });

        // hitung jumlah SPH per status
        $cardsQuery = DataTrxSph::query();
        if ($restrict == 1) {
            $cardsQuery->where('created_by_id', $filterId);
        }

        $cards = [
            'total_sph' => $cardsQuery->count(),
            'waiting'   => (clone $cardsQuery)->where('status', 1)->count(),
            'revisi'    => (clone $cardsQuery)->where('status', 2)->count(),
            'approved'  => (clone $cardsQuery)->where('status', 4)->count(),
            'reject'    => (clone $cardsQuery)->where('status', 3)->count(),
        ];

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'Sph', 'list');

        return response()->json([
            'data'  => $data,
            'cards' => $cards,
        ]);
    }
public function remarks($id)
    {
        $remarks = DB::table('workflow_remark as a')
            ->leftJoin('workflow_record as b', 'b.id', '=', 'a.wf_id')
            ->leftJoin('data_trx_sph     as c', 'c.id', '=', 'b.trx_id')
            ->where('b.tipe_trx', 'sph')
            ->where('c.id', $id)
            ->select([
                'a.wf_comment',
                'a.created_at',
                'a.last_updateby',
            ])
            ->orderBy('a.created_at', 'asc')
            ->get();

        return response()->json($remarks);
    }

public function destroy(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $id = $request->id;
        DB::transaction(function() use ($id) {
            $sph = DataTrxSph::findOrFail($id);
            // jika ada relasi lain yang perlu dihapus, tambahkan di sini
            $sph->delete();
        });

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'Sph', 'destroy');

        return response()->json([
            'message' => 'SPH berhasil dihapus dan semua relasi dibersihkan.'
        ], 200);
    }

public function approveSph(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $user = User::find($result['id']);
        $userRoleId = DB::table('model_has_roles')
        ->where('model_type', 'App\\Models\\User')
        ->where('model_id', $user->id)
        ->value('role_id');

        $request->validate([
            'approval_status' => 'required|in:approve,revisi,reject',
            'approvalComment' => 'required|string|min:2',
        ]);

        DB::beginTransaction();
        try {
            $status = $request->approval_status;
            $comment = $request->approvalComment;
            $userId = $user->first_name . ' ' . $user->last_name;

            // Get current workflow record (status=1, aktif)
        $currWf = WorkflowRecord::where([
                'trx_id'   => $id,
                'tipe_trx' => 'sph',
                'wf_status'   => 1
            ])->first();

            if (!$currWf) throw new \Exception("Workflow aktif tidak ditemukan!");
            // Cek role user harus sama dengan curr_role, kecuali jika role_id == 1 (admin/superuser)
            if ($userRoleId != $currWf->curr_role && $userRoleId != 1) {
                return response()->json(['message' => 'Gagal ! , Role anda tidak berhak untuk simpan data ini'], 403);
            }

            // Tutup workflow current
            $currWf->wf_status = 0;
            $currWf->save();

            $wf_remark_id = null;
            $newWf = null;

            if ($status == 'approve') {
                // Cek next_role
                if ($currWf->next_role) {
                    // 1. Masih ada next approval
                    $newWf = WorkflowRecord::create([
                        'trx_id'   => $id,
                        'tipe_trx' => 'sph',
                        'curr_role'=> $currWf->next_role,
                        'next_role'=> null,
                        'wf_status'   => 1
                    ]);
                    $wf_remark_id = $newWf->id;
                } else {
                    // 2. Sudah end of approval, set status SPH = 4 (approved)
                    DataTrxSph::where('id', $id)->update([
                        'status' => 4,
                        'last_updateby' => $user->id
                    ]);
                    $wf_remark_id = $currWf->id;

                    // Auto-generate PDF after final approval dan simpan URL ke file_sph
                    try {
                        // Pilih generator berdasarkan .env DEFAULT_TEMPLATE dan template_id pada SPH
                        $defaultTemplatesEnv = env('DEFAULT_TEMPLATE', '');
                        $defaultTemplateIds = [];
                        if (!empty($defaultTemplatesEnv)) {
                            foreach (explode(',', $defaultTemplatesEnv) as $val) {
                                $val = trim($val);
                                if ($val !== '') { $defaultTemplateIds[] = (int) $val; }
                            }
                        }
                        $tplId = DataTrxSph::where('id', $id)->value('template_id');
                        if (!empty($defaultTemplateIds) && in_array((int) $tplId, $defaultTemplateIds, true)) {
                            $pdfResponse = $this->generatePdf($id);
                        } else {
                            $pdfResponse = $this->generateKmpPdfFile($id);
                        }
                        if (is_a($pdfResponse, \Illuminate\Http\JsonResponse::class)) {
                            $pdfData = $pdfResponse->getData(true);
                            if (!empty($pdfData['pdf_url'])) {
                                DataTrxSph::where('id', $id)->update([
                                    'file_sph' => $pdfData['pdf_url']
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Auto generate PDF after final approval gagal', [
                            'sph_id' => $id,
                            'error'  => $e->getMessage()
                        ]);
                    }
                }
            } else {
                // Reject = 3, Revisi = 2
                $updateStatus = $status == 'reject' ? 3 : 2;
                DataTrxSph::where('id', $id)->update([
                    'status' => $updateStatus,
                    'last_updateby' => $user->id
                ]);
                $wf_remark_id = $currWf->id;
            }

            // Simpan remark
            WorkflowRemark::create([
                'wf_id'        => $wf_remark_id,
                'tipe_trx'     => 'sph',
                'wf_comment'   => $comment,
                'last_updateby'=> $userId,
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Sph', 'approveSph', 'Approve SPH: ' . $status);

            return response()->json(['message'=>'Konfirmasi  berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message'=>$e->getMessage()], 422);
        }

    }

public function send(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'fullname' => 'required|string',
            'company_name' => 'required|string',
            'sph_kode' => 'required|string',
            'product' => 'required|string',
            'total' => 'required|string',
            'file_url' => 'nullable|url',
        ]);

        $to = $validated['to'];
        $data = $validated;
        $fileUrl = $validated['file_url'] ?? null;

        try {
            Mail::to($to)->send(new SphNotificationMail($data, $fileUrl, 'Penawaran Surat Penawaran Harga - SPH', 'emails.sph_notification'));
            $response = ['message' => 'Email sent successfully!'];

        } catch (\Exception $e) {
            $response = [
                'message' => 'Failed to send email.',
                'error'   => $e->getMessage()
            ];

        }
        log_system('sph', 'SPH -> Email', 'Email', $validated, $response);
        return response()->json($response);
    }

public function generatePdf($id)
    {
        /**
         * Generate PDF SPH.
         * Sekarang TIPE SPH diambil LANGSUNG dari record di DB (field tipe_sph),
         * bukan lagi dari input FE. FE cukup panggil endpoint dengan ID saja.
         *
         * Logic:
         *  - Ambil $sph->tipe_sph (IASE atau MMTEI). Jika tidak valid fallback ke MMTEI.
         *  - IASE  => parent_id = 37, template = pdf.iase_pdf_template
         *  - MMTEI => parent_id = 2,  template = pdf.mmtei_pdf_template
         */
        $sph = DataTrxSph::findOrFail($id);
        $detailSph = DB::table('sph_details')->where('sph_id', $id)->first();

        // Kembalikan incomingType untuk kebutuhan report/remark (tidak mempengaruhi pemilihan template)
        $incomingType = strtoupper(trim($sph->tipe_sph ?? ''));
        if (!in_array($incomingType, ['IASE', 'MMTEI'])) {
            $incomingType = 'MMTEI';
        }
        // parent_id untuk mengambil settings parent-specific
        $parentId      = $incomingType === 'IASE' ? 37 : 2;

        // Tidak lagi menggunakan incomingType; template ditentukan dari sph_template.template

        // Tentukan nama blade template BERDASARKAN template_id → sph_template.form
        $templateForm = null;
        if (!empty($sph->template_id)) {
            $templateForm = DB::table('sph_template')
                ->where('id', $sph->template_id)
                ->value('template');
        }
        $bladeTemplate = null;
        if (!empty($templateForm)) {
            // Normalisasi nama view:
            // - ganti '/' atau '\\' menjadi '.'
            // - hilangkan akhiran .blade atau .blade.php jika ada
            // - jika tidak mengandung '.', prefix 'pdf.' agar menunjuk ke folder resources/views/pdf
            $candidateView = str_replace(['\\', '/'], '.', $templateForm);
            $candidateView = preg_replace('/\.blade(\.php)?$/i', '', $candidateView);
            if (strpos($candidateView, '.') === false) {
                $candidateView = 'pdf.' . $candidateView;
            }

            if (view()->exists($candidateView)) {
                $bladeTemplate = $candidateView;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Template view not found',
                    'template_id' => $sph->template_id,
                    'template' => $templateForm,
                    'candidate' => $candidateView
                ], 422);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Template is not configured for this SPH (missing template in sph_template)'
            ], 422);
        }

        // Settings utama sesuai parent id (dikembalikan karena dipakai di report/remark)
        $settings = DB::table('reporting_lov')
            ->where('parent_id', $parentId)
            ->pluck('value', 'code')
            ->toArray();

        // Other config (parent_id NULL) - tetap global
        $settings['other_config'] = DB::table('reporting_lov')
            ->whereNull('parent_id')
            ->pluck('value', 'code')
            ->toArray();

        // Pastikan key Logo ada (case insensitive)
        if (!array_key_exists('Logo', $settings['other_config']) && !array_key_exists('logo', $settings['other_config'])) {
            $explicitLogo = DB::table('reporting_lov')
                ->whereRaw('LOWER(code) = ?', ['logo'])
                ->value('value');
            if ($explicitLogo) {
                $settings['other_config']['Logo'] = $explicitLogo;
            }
        }

        // --- Prepare normalized config arrays for parent-specific and global ---
        $parentConfigNormalized = [];
        foreach ($settings as $k => $v) {
            if ($k === 'other_config') continue;
            $parentConfigNormalized[strtolower(trim($k))] = $v;
        }
        $globalConfigNormalized = [];
        foreach (($settings['other_config'] ?? []) as $k => $v) {
            $globalConfigNormalized[strtolower(trim($k))] = $v;
        }

        // Helper fetch remote
        $fetchRemote = function($url){
            if(!filter_var($url, FILTER_VALIDATE_URL)) return null;
            try{
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $data = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if($code >= 200 && $code < 300 && $data) {
                    return $data;
                }
            }catch(\Exception $e){
                Log::warning('Fetch remote image failed: '.$e->getMessage());
            }
            return null;
        };

        $buildBase64 = function($url) use ($fetchRemote){
            if(!$url) return '';
            if(filter_var($url, FILTER_VALIDATE_URL)){
                $bin = $fetchRemote($url);
                if(!$bin){
                    $bin = @file_get_contents($url);
                }
                if($bin){
                    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                    $ext = strtolower($ext);
                    if(!in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
                        $ext = 'png';
                    }
                    // Compress raster images to reduce final PDF size
                    if($ext !== 'svg' && function_exists('imagecreatefromstring') && function_exists('imagejpeg')){
                        $img = @\imagecreatefromstring($bin);
                        if($img){
                            $width = \imagesx($img);
                            $height = \imagesy($img);
                            $maxWidth = 1200;
                            if($width > $maxWidth){
                                $newWidth = $maxWidth;
                                $newHeight = (int) round(($maxWidth / $width) * $height);
                                $resized = \imagecreatetruecolor($newWidth, $newHeight);
                                \imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                \imagedestroy($img);
                                $img = $resized;
                            }
                            ob_start();
                            \imagejpeg($img, null, 70);
                            $bin = ob_get_clean();
                            \imagedestroy($img);
                            $ext = 'jpeg';
                        }
                    }
                    return 'data:image/'.$ext.';base64,'.base64_encode($bin);
                }
                return '';
            }
            if(file_exists($url)){
                $ext = pathinfo($url, PATHINFO_EXTENSION) ?: 'png';
                $ext = strtolower($ext);
                $data = file_get_contents($url);
                if($ext !== 'svg' && function_exists('imagecreatefromstring') && function_exists('imagejpeg')){
                    $img = @\imagecreatefromstring($data);
                    if($img){
                        $width = \imagesx($img);
                        $height = \imagesy($img);
                        $maxWidth = 1200;
                        if($width > $maxWidth){
                            $newWidth = $maxWidth;
                            $newHeight = (int) round(($maxWidth / $width) * $height);
                            $resized = \imagecreatetruecolor($newWidth, $newHeight);
                            \imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            \imagedestroy($img);
                            $img = $resized;
                        }
                        ob_start();
                        \imagejpeg($img, null, 70);
                        $data = ob_get_clean();
                        \imagedestroy($img);
                        $ext = 'jpeg';
                    }
                }
                return 'data:image/'.$ext.';base64,'.base64_encode($data);
            }
            $candidate = public_path($url);
            if(file_exists($candidate)){
                $ext = pathinfo($candidate, PATHINFO_EXTENSION) ?: 'png';
                $ext = strtolower($ext);
                $data = file_get_contents($candidate);
                if($ext !== 'svg' && function_exists('imagecreatefromstring') && function_exists('imagejpeg')){
                    $img = @\imagecreatefromstring($data);
                    if($img){
                        $width = \imagesx($img);
                        $height = \imagesy($img);
                        $maxWidth = 1200;
                        if($width > $maxWidth){
                            $newWidth = $maxWidth;
                            $newHeight = (int) round(($maxWidth / $width) * $height);
                            $resized = \imagecreatetruecolor($newWidth, $newHeight);
                            \imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            \imagedestroy($img);
                            $img = $resized;
                        }
                        ob_start();
                        \imagejpeg($img, null, 70);
                        $data = ob_get_clean();
                        \imagedestroy($img);
                        $ext = 'jpeg';
                    }
                }
                return 'data:image/' . $ext . ';base64,' . base64_encode($data);
            }
            return '';
        };

        // --- Merge parent-specific config over global config for flexible search ---
        $mergedSources = $parentConfigNormalized + $globalConfigNormalized; // parent has priority
        $logoKeyList = ['logo','mmtei','iase','main_logo','company_logo'];
        $asibKeyList = ['asib_logo','asiblogo','logo_asib'];
        $gmiKeyList  = ['gmi_logo','gmilogo','logo_gmi'];
        // Helper to find value and source
        $findFlexibleWithSource = function($parentNorm, $globalNorm, $candidates) {
            foreach ($candidates as $c) {
                if (isset($parentNorm[$c])) return [$parentNorm[$c], 'parent'];
            }
            foreach ($candidates as $c) {
                if (isset($globalNorm[$c])) return [$globalNorm[$c], 'global'];
            }
            return ['', ''];
        };

        list($logoUrl, $logoSource) = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $logoKeyList);
        list($asibUrl, $asibSource) = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $asibKeyList);
        list($gmiUrl, $gmiSource)   = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $gmiKeyList);

        $logoBase64     = $buildBase64($logoUrl);
        $asibLogoBase64 = $buildBase64($asibUrl);
        $gmiLogoBase64  = $buildBase64($gmiUrl);

        // Fallback logo default
        if(empty($logoBase64)){
            $defaultLogoPath = public_path('static/images/logo/default-logo.png');
            if(file_exists($defaultLogoPath)){
                $ext = pathinfo($defaultLogoPath, PATHINFO_EXTENSION) ?: 'png';
                $logoBase64 = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($defaultLogoPath));
            }
        }

        // Simpan ke settings untuk template
        $settings['other_config']['LogoBase64']     = $logoBase64;
        $settings['other_config']['ASIBLogoBase64'] = $asibLogoBase64;
        $settings['other_config']['GMILogoBase64']  = $gmiLogoBase64;

        // Data customers (OAT) – sesuai struktur table baru
        $customers = DB::table('oat_customer as a')
            ->leftJoin('master_customer as b', 'b.id', '=', 'a.cust_id')
            ->leftJoin('data_trx_sph as c', 'c.comp_name', '=', 'b.name')
            ->where('c.comp_name', $sph->comp_name)
            ->select('a.location', 'a.qty', 'a.oat')
            ->get();

        // (rollback) tidak perlu mengambil details untuk generatePdf

        // Get email, first_name, dan last_name dari user yang membuat SPH
        $userData = null;
        if (!empty($sph->created_by_id)) {
            $userData = User::where('id', $sph->created_by_id)
                ->select('email', 'first_name', 'last_name')
                ->first();
        }
        $email = (object) [
            'useremail' => $userData->email ?? '',
            'first_name' => $userData->first_name ?? '',
            'last_name' => $userData->last_name ?? ''
        ];

        // Pilih view sesuai tipe
        $pdf = Pdf::setOptions([
                'enable_remote'   => true,
                'isRemoteEnabled' => true,
                'dpi'             => 96,
                'defaultFont'     => 'sans-serif'
            ])
            ->loadView($bladeTemplate, [
                'sph'             => $sph,
                'customers'       => $customers,
                'remarks'         => $sph->remarks,
                'settings'        => $settings,
                'logoBase64'      => $logoBase64,
                'asibLogoSrc'     => $asibLogoBase64 ?: $asibUrl,
                'gmiLogoSrc'      => $gmiLogoBase64 ?: $gmiUrl,
                'email'           => $email,
            ])->setPaper('a4', 'portrait');

        // Build filename: /{tipesph}/{bulanberjalan}.{uniqueidtimestamp}.{kodesph}.sph
        $bulanBerjalan = Carbon::now()->format('m');
        $uniqueTimestamp = time();
        $kodeSphRaw = (string) ($sph->kode_sph ?? '');
        $kodeSphSanitized = preg_replace('/[^A-Za-z0-9]+/', '', $kodeSphRaw);
        if(!$kodeSphSanitized){
            $kodeSphSanitized = (string) $sph->id;
        }
        $typeFolder = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $incomingType)) ?: 'SPH';
        $pdfFileName = $bulanBerjalan . '.' . $uniqueTimestamp . '.' . $kodeSphSanitized . '.sph';
        $pdfPath = $typeFolder . '/' . $pdfFileName;

        $pdfContent = $pdf->output();

        // Save to remote storage
        Storage::disk('idcloudhost')->put($pdfPath, $pdfContent);
        // If file size > 1MB, try re-render at lower DPI
        try {
            $currentSize = Storage::disk('idcloudhost')->size($pdfPath);
            if ($currentSize !== false && $currentSize > 1024 * 1024) {
                Log::info('SPH PDF >1MB, re-render with lower DPI', ['sph_id' => $sph->id, 'size' => $currentSize]);
                $pdfLow = Pdf::setOptions([
                        'enable_remote'   => true,
                        'isRemoteEnabled' => true,
                        'dpi'             => 72,
                        'defaultFont'     => 'sans-serif'
                    ])
                    ->loadView($bladeTemplate, [
                        'sph'             => $sph,
                        'customers'       => $customers,
                        'remarks'         => $sph->remarks,
                        'settings'        => $settings,
                        'logoBase64'      => $logoBase64,
                        'asibLogoSrc'     => $asibLogoBase64 ?: $asibUrl,
                        'gmiLogoSrc'      => $gmiLogoBase64 ?: $gmiUrl,
                        'email'           => $email,
                    ])->setPaper('a4', 'portrait');
                Storage::disk('idcloudhost')->put($pdfPath, $pdfLow->output());
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to re-render lower DPI for SPH PDF', ['error' => $e->getMessage()]);
        }

        $sph->file_sph = $pdfPath;
        $sph->save();

        Log::info('SPH PDF Generated', [
            'sph_id'        => $sph->id,
            'tipe_used'     => $incomingType,
            'template'      => $bladeTemplate,
            'parent_id'     => $parentId,
            'logo_url'      => $logoUrl,
            'asib_url'      => $asibUrl,
            'gmi_url'       => $gmiUrl,
            'logo_source'   => $logoSource,
            'asib_source'   => $asibSource,
            'gmi_source'    => $gmiSource,
        ]);

        return response()->json([
            'success'         => true,
            'tipe_sph_record' => $sph->tipe_sph,
            'tipe_sph_used'   => $incomingType,
            'template_used'   => $bladeTemplate,
            'settings_parent' => $parentId,
            'pdf_url'         => 'https://is3.cloudhost.id/bensinkustorage/' . $pdfPath,
            'logo_loaded'     => !empty($logoBase64),
            'asib_loaded'     => !empty($asibLogoBase64),
            'gmi_loaded'      => !empty($gmiLogoBase64),
            'raw_logo_url'    => $logoUrl,
            'asib_logo_url'   => $asibUrl,
            'gmi_logo_url'    => $gmiUrl,
            'logo_source'     => $logoSource,
            'asib_source'     => $asibSource,
            'gmi_source'      => $gmiSource,
        ]);
    }

    // Download PDF (jika ingin via endpoint/secure)
    public function backup_generatePdf($id)
    {
        /**
         * Generate PDF SPH.
         * Sekarang TIPE SPH diambil LANGSUNG dari record di DB (field tipe_sph),
         * bukan lagi dari input FE. FE cukup panggil endpoint dengan ID saja.
         *
         * Logic:
         *  - Ambil $sph->tipe_sph (IASE atau MMTEI). Jika tidak valid fallback ke MMTEI.
         *  - IASE  => parent_id = 37, template = pdf.iase_pdf_template
         *  - MMTEI => parent_id = 2,  template = pdf.mmtei_pdf_template
         */
        $sph = DataTrxSph::findOrFail($id);

        // Ambil tipe_sph langsung dari record (abaikan input FE)
        $incomingType = strtoupper(trim($sph->tipe_sph ?? ''));
        if (!in_array($incomingType, ['IASE', 'MMTEI'])) {
            // fallback default
            $incomingType = 'MMTEI';
        }

        // Tentukan parent_id settings & nama blade template
        $parentId      = $incomingType === 'IASE' ? 37 : 2;
        $bladeTemplate = $incomingType === 'IASE' ? 'pdf.iase_pdf_template' : 'pdf.mmtei_pdf_template';

        // Settings utama sesuai parent id
        $settings = DB::table('reporting_lov')
            ->where('parent_id', $parentId)
            ->pluck('value', 'code')
            ->toArray();

        // Other config (parent_id NULL) - tetap global
        $settings['other_config'] = DB::table('reporting_lov')
            ->whereNull('parent_id')
            ->pluck('value', 'code')
            ->toArray();

        // Pastikan key Logo ada (case insensitive)
        if (!array_key_exists('Logo', $settings['other_config']) && !array_key_exists('logo', $settings['other_config'])) {
            $explicitLogo = DB::table('reporting_lov')
                ->whereRaw('LOWER(code) = ?', ['logo'])
                ->value('value');
            if ($explicitLogo) {
                $settings['other_config']['Logo'] = $explicitLogo;
            }
        }

        // --- Prepare normalized config arrays for parent-specific and global ---
        $parentConfigNormalized = [];
        foreach ($settings as $k => $v) {
            if ($k === 'other_config') continue;
            $parentConfigNormalized[strtolower(trim($k))] = $v;
        }
        $globalConfigNormalized = [];
        foreach (($settings['other_config'] ?? []) as $k => $v) {
            $globalConfigNormalized[strtolower(trim($k))] = $v;
        }

        // Helper fetch remote
        $fetchRemote = function($url){
            if(!filter_var($url, FILTER_VALIDATE_URL)) return null;
            try{
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $data = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if($code >= 200 && $code < 300 && $data) {
                    return $data;
                }
            }catch(\Exception $e){
                Log::warning('Fetch remote image failed: '.$e->getMessage());
            }
            return null;
        };

        $buildBase64 = function($url) use ($fetchRemote){
            if(!$url) return '';
            if(filter_var($url, FILTER_VALIDATE_URL)){
                $bin = $fetchRemote($url);
                if(!$bin){
                    $bin = @file_get_contents($url);
                }
                if($bin){
                    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                    $ext = strtolower($ext);
                    if(!in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
                        $ext = 'png';
                    }
                    return 'data:image/'.$ext.';base64,'.base64_encode($bin);
                }
                return '';
            }
            if(file_exists($url)){
                $ext = pathinfo($url, PATHINFO_EXTENSION) ?: 'png';
                return 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($url));
            }
            $candidate = public_path($url);
            if(file_exists($candidate)){
                $ext = pathinfo($candidate, PATHINFO_EXTENSION) ?: 'png';
                return 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($candidate));
            }
            return '';
        };

        // --- Merge parent-specific config over global config for flexible search ---
        $mergedSources = $parentConfigNormalized + $globalConfigNormalized; // parent has priority
        $logoKeyList = ['logo','mmtei','iase','main_logo','company_logo'];
        $asibKeyList = ['asib_logo','asiblogo','logo_asib'];
        $gmiKeyList  = ['gmi_logo','gmilogo','logo_gmi'];
        // Helper to find value and source
        $findFlexibleWithSource = function($parentNorm, $globalNorm, $candidates) {
            foreach ($candidates as $c) {
                if (isset($parentNorm[$c])) return [$parentNorm[$c], 'parent'];
            }
            foreach ($candidates as $c) {
                if (isset($globalNorm[$c])) return [$globalNorm[$c], 'global'];
            }
            return ['', ''];
        };

        list($logoUrl, $logoSource) = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $logoKeyList);
        list($asibUrl, $asibSource) = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $asibKeyList);
        list($gmiUrl, $gmiSource)   = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $gmiKeyList);

        $logoBase64     = $buildBase64($logoUrl);
        $asibLogoBase64 = $buildBase64($asibUrl);
        $gmiLogoBase64  = $buildBase64($gmiUrl);

        // Fallback logo default
        if(empty($logoBase64)){
            $defaultLogoPath = public_path('static/images/logo/default-logo.png');
            if(file_exists($defaultLogoPath)){
                $ext = pathinfo($defaultLogoPath, PATHINFO_EXTENSION) ?: 'png';
                $logoBase64 = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($defaultLogoPath));
            }
        }

        // Simpan ke settings untuk template
        $settings['other_config']['LogoBase64']     = $logoBase64;
        $settings['other_config']['ASIBLogoBase64'] = $asibLogoBase64;
        $settings['other_config']['GMILogoBase64']  = $gmiLogoBase64;

        // Data customers (OAT) – sesuai struktur table baru
        $customers = DB::table('oat_customer as a')
            ->leftJoin('master_customer as b', 'b.id', '=', 'a.cust_id')
            ->leftJoin('data_trx_sph as c', 'c.comp_name', '=', 'b.name')
            ->where('c.comp_name', $sph->comp_name)
            ->select('a.location', 'a.qty', 'a.oat')
            ->get();

        // Pilih view sesuai tipe
        $pdf = Pdf::setOptions([
                'enable_remote'   => true,
                'isRemoteEnabled' => true,
                'dpi'             => 96,
                'defaultFont'     => 'sans-serif'
            ])
            ->loadView($bladeTemplate, [
                'sph'             => $sph,
                'customers'       => $customers,
                'remarks'         => $sph->remarks,
                'settings'        => $settings,
                'logoBase64'      => $logoBase64,
                'asibLogoSrc'     => $asibLogoBase64 ?: $asibUrl,
                'gmiLogoSrc'      => $gmiLogoBase64 ?: $gmiUrl,
            ])->setPaper('a4', 'portrait');

        // Build filename: /{tipesph}/{bulanberjalan}.{uniqueidtimestamp}.{kodesph}.sph (backup method too)
        $bulanBerjalan = Carbon::now()->format('m');
        $uniqueTimestamp = time();
        $kodeSphRaw = (string) ($sph->kode_sph ?? '');
        $kodeSphSanitized = preg_replace('/[^A-Za-z0-9]+/', '', $kodeSphRaw);
        if(!$kodeSphSanitized){
            $kodeSphSanitized = (string) $sph->id;
        }
        $typeFolder = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $incomingType)) ?: 'SPH';
        $pdfFileName = $bulanBerjalan . '.' . $uniqueTimestamp . '.' . $kodeSphSanitized . '.sph';
        $pdfPath = $typeFolder . '/' . $pdfFileName;

        $pdfContent = $pdf->output();
        Storage::disk('idcloudhost')->put($pdfPath, $pdfContent);
        // If file size > 1MB, try re-render at lower DPI
        try {
            $currentSize = Storage::disk('idcloudhost')->size($pdfPath);
            if ($currentSize !== false && $currentSize > 1024 * 1024) {
                Log::info('SPH PDF >1MB (backup), re-render with lower DPI', ['sph_id' => $sph->id, 'size' => $currentSize]);
                $pdfLow = Pdf::setOptions([
                        'enable_remote'   => true,
                        'isRemoteEnabled' => true,
                        'dpi'             => 72,
                        'defaultFont'     => 'sans-serif'
                    ])
                    ->loadView($bladeTemplate, [
                        'sph'             => $sph,
                        'customers'       => $customers,
                        'remarks'         => $sph->remarks,
                        'settings'        => $settings,
                        'logoBase64'      => $logoBase64,
                        'asibLogoSrc'     => $asibLogoBase64 ?: $asibUrl,
                        'gmiLogoSrc'      => $gmiLogoBase64 ?: $gmiUrl,
                    ])->setPaper('a4', 'portrait');
                Storage::disk('idcloudhost')->put($pdfPath, $pdfLow->output());
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to re-render lower DPI for SPH PDF (backup)', ['error' => $e->getMessage()]);
        }

        $sph->file_sph = $pdfPath;
        $sph->save();

        Log::info('SPH PDF Generated', [
            'sph_id'        => $sph->id,
            'tipe_used'     => $incomingType,
            'template'      => $bladeTemplate,
            'parent_id'     => $parentId,
            'logo_url'      => $logoUrl,
            'asib_url'      => $asibUrl,
            'gmi_url'       => $gmiUrl,
            'logo_source'   => $logoSource,
            'asib_source'   => $asibSource,
            'gmi_source'    => $gmiSource,
        ]);

        return response()->json([
            'success'         => true,
            'tipe_sph_record' => $sph->tipe_sph,
            'tipe_sph_used'   => $incomingType, // tipe_sph_used bisa saja fallback ke MMTEI jika value di DB tidak valid
            'template_used'   => $bladeTemplate,
            'settings_parent' => $parentId,
            'pdf_url'         => 'https://is3.cloudhost.id/bensinkustorage/' . $pdfPath,
            'logo_loaded'     => !empty($logoBase64),
            'asib_loaded'     => !empty($asibLogoBase64),
            'gmi_loaded'      => !empty($gmiLogoBase64),
            'raw_logo_url'    => $logoUrl,
            'asib_logo_url'   => $asibUrl,
            'gmi_logo_url'    => $gmiUrl,
            'logo_source'     => $logoSource,
            'asib_source'     => $asibSource,
            'gmi_source'      => $gmiSource,
        ]);
    }
public function downloadPdf($id)
    {
        $sph = DataTrxSph::findOrFail($id);

        if (!$sph->pdf_file || !Storage::disk('public')->exists($sph->pdf_file)) {
            return response()->json(['error' => 'PDF belum digenerate'], 404);
        }

        return response()->download(storage_path('app/public/' . $sph->pdf_file));
    }

    // New: return details info for SPH if template has_details=1
    public function generateKmpPdf($id)
    {
        $sph = DataTrxSph::findOrFail($id);
        $hasDetails = 0;
        if (!empty($sph->template_id)) {
            $tplHas = DB::table('sph_template')->select('has_details')->where('id', $sph->template_id)->first();
            $hasDetails = isset($tplHas->has_details) ? (int) $tplHas->has_details : 0;
        }

        $details = [];
        if ($hasDetails === 1) {
            $details = DB::table('sph_details')->where('sph_id', $sph->id)->get();
        }

        // Group details by biaya_lokasi (uppercase keys) and attach PBBKB from master_lov (code = biaya_lokasi)
        $detailsGrouped = [];
        if (!empty($details)) {
            foreach ($details as $row) {
                $key = strtoupper(trim($row->biaya_lokasi ?? ''));
                if ($key === '') {
                    $key = 'UNKNOWN';
                }
                if (!array_key_exists($key, $detailsGrouped)) {
                    // Fetch PBBKB percent for this lokasi from master_lov (case-insensitive match)
                    $pbbkbVal = DB::table('master_lov')
                        ->whereRaw('LOWER(code) = ?', [strtolower($row->biaya_lokasi ?? '')])
                        ->value('value');
                    $pbbkbPercent = is_null($pbbkbVal) ? null : (string) $pbbkbVal . '%';
                    $detailsGrouped[$key] = [
                        'pbbkb' => $pbbkbPercent,
                        'items' => [],
                    ];
                }
                $detailsGrouped[$key]['items'][] = $row;
            }
        }

        return response()->json([
            'success'       => true,
            'sph_id'        => $sph->id,
            'kode_sph'      => $sph->kode_sph,
            'has_details'   => $hasDetails,
            'details_count' => (is_object($details) && method_exists($details, 'count')) ? $details->count() : (is_array($details) ? count($details) : 0),
            'details_group_count' => count($detailsGrouped),
            'details_grouped'     => $detailsGrouped,
        ]);
    }

    // Generate and store KMP PDF using template pdf.mmtei_pdf_bundle2_template
    public function generateKmpPdfFile($id)
    {
        $startTime = microtime(true);
        $sph = DataTrxSph::findOrFail($id);

        // Determine type and parent settings
        $incomingType = strtoupper(trim($sph->tipe_sph ?? ''));
        if (!in_array($incomingType, ['IASE', 'MMTEI'])) {
            $incomingType = 'MMTEI';
        }
        $parentId = $incomingType === 'IASE' ? 37 : 2;

        // Load settings from reporting_lov (optimized: single query for each)
        $settings = DB::table('reporting_lov')
            ->where('parent_id', $parentId)
            ->pluck('value', 'code')
            ->toArray();
        $settings['other_config'] = DB::table('reporting_lov')
            ->whereNull('parent_id')
            ->pluck('value', 'code')
            ->toArray();

        // Ensure Logo key
        if (!array_key_exists('Logo', $settings['other_config']) && !array_key_exists('logo', $settings['other_config'])) {
            $explicitLogo = DB::table('reporting_lov')
                ->whereRaw('LOWER(code) = ?', ['logo'])
                ->value('value');
            if ($explicitLogo) {
                $settings['other_config']['Logo'] = $explicitLogo;
            }
        }

        // Normalize configs
        $parentConfigNormalized = [];
        foreach ($settings as $k => $v) {
            if ($k === 'other_config') continue;
            $parentConfigNormalized[strtolower(trim($k))] = $v;
        }
        $globalConfigNormalized = [];
        foreach (($settings['other_config'] ?? []) as $k => $v) {
            $globalConfigNormalized[strtolower(trim($k))] = $v;
        }

        // Helper to fetch remote (reduced timeout for faster failure)
        $fetchRemote = function($url){
            if(!filter_var($url, FILTER_VALIDATE_URL)) return null;
            try{
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 5, // Reduced from 10 to 5 seconds
                    CURLOPT_CONNECTTIMEOUT => 3, // Add connection timeout
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $data = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if($code >= 200 && $code < 300 && $data) return $data;
            }catch(\Exception $e){
                Log::warning('Fetch remote image failed: '.$e->getMessage());
            }
            return null;
        };

        // Build base64 image with simple compression
        $buildBase64 = function($url) use ($fetchRemote){
            if(!$url) return '';
            if(filter_var($url, FILTER_VALIDATE_URL)){
                $bin = $fetchRemote($url) ?: @file_get_contents($url);
                if($bin){
                    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                    $ext = strtolower($ext);
                    if($ext !== 'svg' && function_exists('imagecreatefromstring') && function_exists('imagejpeg')){
                        $img = @\imagecreatefromstring($bin);
                        if($img){
                            $width = \imagesx($img); $height = \imagesy($img);
                            $maxWidth = 1200;
                            if($width > $maxWidth){
                                $newWidth = $maxWidth;
                                $newHeight = (int) round(($maxWidth / $width) * $height);
                                $resized = \imagecreatetruecolor($newWidth, $newHeight);
                                \imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                \imagedestroy($img); $img = $resized;
                            }
                            ob_start(); \imagejpeg($img, null, 70); $bin = ob_get_clean(); \imagedestroy($img);
                            $ext = 'jpeg';
                        }
                    }
                    return 'data:image/'.$ext.';base64,'.base64_encode($bin);
                }
                return '';
            }
            if(file_exists($url)){
                $ext = pathinfo($url, PATHINFO_EXTENSION) ?: 'png';
                $data = file_get_contents($url);
                return 'data:image/'.$ext.';base64,'.base64_encode($data);
            }
            $candidate = public_path($url);
            if(file_exists($candidate)){
                $ext = pathinfo($candidate, PATHINFO_EXTENSION) ?: 'png';
                $data = file_get_contents($candidate);
                return 'data:image/'.$ext.';base64,'.base64_encode($data);
            }
            return '';
        };

        // Find logos
        $findFlexibleWithSource = function($parentNorm, $globalNorm, $candidates) {
            foreach ($candidates as $c) { if (isset($parentNorm[$c])) return [$parentNorm[$c], 'parent']; }
            foreach ($candidates as $c) { if (isset($globalNorm[$c])) return [$globalNorm[$c], 'global']; }
            return ['', ''];
        };
        $logoKeyList = ['logo','mmtei','iase','main_logo','company_logo'];
        $asibKeyList = ['asib_logo','asiblogo','logo_asib'];
        $gmiKeyList  = ['gmi_logo','gmilogo','logo_gmi'];
        list($logoUrl,) = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $logoKeyList);
        list($asibUrl,) = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $asibKeyList);
        list($gmiUrl,)  = $findFlexibleWithSource($parentConfigNormalized, $globalConfigNormalized, $gmiKeyList);
        $logoBase64 = $buildBase64($logoUrl);
        $asibLogoBase64 = $buildBase64($asibUrl);
        $gmiLogoBase64  = $buildBase64($gmiUrl);

        // Build details grouped with PBBKB per lokasi (OPTIMIZED: batch load master_lov)
        $hasDetails = 0; $details = [];
        if (!empty($sph->template_id)) {
            $tplHas = DB::table('sph_template')->select('has_details')->where('id', $sph->template_id)->first();
            $hasDetails = isset($tplHas->has_details) ? (int) $tplHas->has_details : 0;
        }
        if ($hasDetails === 1) {
            $details = DB::table('sph_details')->where('sph_id', $sph->id)->get();
        }
        $detailsGrouped = [];
        if (!empty($details)) {
            // OPTIMIZATION: Pre-load all unique biaya_lokasi codes and fetch PBBKB values in one query
            $uniqueLokasiCodes = [];
            foreach ($details as $row) {
                $lokasiCode = trim($row->biaya_lokasi ?? '');
                if ($lokasiCode !== '') {
                    $uniqueLokasiCodes[strtolower($lokasiCode)] = $lokasiCode; // Store original case for lookup
                }
            }
            // Batch load all PBBKB values for unique lokasi codes (case-insensitive)
            $pbbkbMap = [];
            if (!empty($uniqueLokasiCodes)) {
                $codesLower = array_keys($uniqueLokasiCodes);
                // Build query with multiple OR conditions for case-insensitive matching
                $pbbkbResults = DB::table('master_lov')
                    ->where(function($query) use ($codesLower) {
                        foreach ($codesLower as $codeLower) {
                            $query->orWhereRaw('LOWER(TRIM(code)) = ?', [$codeLower]);
                        }
                    })
                    ->select('code', 'value')
                    ->get();
                foreach ($pbbkbResults as $pbbkbRow) {
                    $pbbkbMap[strtolower(trim($pbbkbRow->code))] = $pbbkbRow->value;
                }
            }
            
            // Now process details with pre-loaded PBBKB values
            foreach ($details as $row) {
                $lokasiKey = strtoupper(trim($row->biaya_lokasi ?? ''));
                if ($lokasiKey === '') { $lokasiKey = 'UNKNOWN'; }
                if (!array_key_exists($lokasiKey, $detailsGrouped)) {
                    // Use pre-loaded PBBKB value instead of querying
                    $lokasiCodeLower = strtolower(trim($row->biaya_lokasi ?? ''));
                    $pbbkbVal = isset($pbbkbMap[$lokasiCodeLower]) ? $pbbkbMap[$lokasiCodeLower] : null;
                    $pbbkbPercent = is_null($pbbkbVal) ? null : (string) $pbbkbVal . '%';
                    $detailsGrouped[$lokasiKey] = [ 'pbbkb' => $pbbkbPercent, 'items' => [], 'areas' => [], 'row_count_total' => 0 ];
                }
                $detailsGrouped[$lokasiKey]['items'][] = $row;
                // Build nested grouping by area name (cname_lname)
                $areaKey = strtoupper(trim($row->cname_lname ?? ''));
                $areaDisplay = trim($row->cname_lname ?? '');
                if ($areaKey === '') { $areaKey = 'UNKNOWN'; $areaDisplay = 'UNKNOWN'; }
                if (!array_key_exists($areaKey, $detailsGrouped[$lokasiKey]['areas'])) {
                    $detailsGrouped[$lokasiKey]['areas'][$areaKey] = [
                        'area' => $areaDisplay,
                        'rows' => []
                    ];
                }
                $detailsGrouped[$lokasiKey]['areas'][$areaKey]['rows'][] = $row;
                $detailsGrouped[$lokasiKey]['row_count_total'] += 1;
            }
            // Reindex areas to preserve display order neatly
            foreach ($detailsGrouped as $k => $grp) {
                $detailsGrouped[$k]['areas'] = array_values($grp['areas']);
            }
        }

        // Tentukan blade template berdasarkan sph_template.template (fallback ke mmtei bundle2)
        $templateForm = null;
        if (!empty($sph->template_id)) {
            $templateForm = DB::table('sph_template')
                ->where('id', $sph->template_id)
                ->value('template');
        }
        $bladeTemplate = 'pdf.mmtei_pdf_bundle2_template';
        $reportedTemplateFile = 'mmtei_pdf_bundle2_template.blade.php';
        if (!empty($templateForm)) {
            $candidateView = str_replace(['\\', '/'], '.', $templateForm);
            $candidateView = preg_replace('/\.blade(\.php)?$/i', '', $candidateView);
            if (strpos($candidateView, '.') === false) {
                $candidateView = 'pdf.' . $candidateView;
            }
            if (view()->exists($candidateView)) {
                $bladeTemplate = $candidateView;
                $base = $templateForm;
                if (!preg_match('/\.blade(\.php)?$/i', $base)) {
                    $base .= '.blade.php';
                }
                $reportedTemplateFile = basename(str_replace('\\', '/', $base));
            }
        }

        // Get email, first_name, dan last_name dari user yang membuat SPH
        $userData = null;
        if (!empty($sph->created_by_id)) {
            $userData = User::where('id', $sph->created_by_id)
                ->select('email', 'first_name', 'last_name')
                ->first();
        }
        $email = (object) [
            'useremail' => $userData->email ?? '',
            'first_name' => $userData->first_name ?? '',
            'last_name' => $userData->last_name ?? ''
        ];

        // Render PDF dengan template terpilih
        $pdf = Pdf::setOptions([
                'enable_remote'   => true,
                'isRemoteEnabled' => true,
                'dpi'             => 96,
                'defaultFont'     => 'sans-serif'
            ])
            ->loadView($bladeTemplate, [
                'sph'              => $sph,
                'settings'         => $settings,
                'logoBase64'       => $logoBase64,
                'asibLogoBase64'   => $asibLogoBase64,
                'gmiLogoBase64'    => $gmiLogoBase64,
                // pass both grouped and raw details to support different templates
                'details'          => $details,
                'data'             => ['details' => $details],
                'details_grouped'  => $detailsGrouped,
                'email'            => $email,
            ])->setPaper('a4', 'portrait');

        // File path: /{tipesph}/{bulanberjalan}.{uniqueidtimestamp}.{kodesph}.sph
        $bulanBerjalan = Carbon::now()->format('m');
        $uniqueTimestamp = time();
        $kodeSphRaw = (string) ($sph->kode_sph ?? '');
        $kodeSphSanitized = preg_replace('/[^A-Za-z0-9]+/', '', $kodeSphRaw) ?: (string) $sph->id;
        $typeFolder = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $incomingType)) ?: 'SPH';
        $pdfFileName = $bulanBerjalan . '.' . $uniqueTimestamp . '.' . $kodeSphSanitized . '.sph';
        $pdfPath = $typeFolder . '/' . $pdfFileName;

        $pdfContent = $pdf->output();
        Storage::disk('idcloudhost')->put($pdfPath, $pdfContent);
        try {
            $currentSize = Storage::disk('idcloudhost')->size($pdfPath);
            if ($currentSize !== false && $currentSize > 1024 * 1024) {
                $pdfLow = Pdf::setOptions([
                        'enable_remote'   => true,
                        'isRemoteEnabled' => true,
                        'dpi'             => 72,
                        'defaultFont'     => 'sans-serif'
                    ])
                    ->loadView($bladeTemplate, [
                        'sph'              => $sph,
                        'settings'         => $settings,
                        'logoBase64'       => $logoBase64,
                        'asibLogoBase64'   => $asibLogoBase64,
                        'gmiLogoBase64'    => $gmiLogoBase64,
                        // pass both grouped and raw details to support different templates
                        'details'          => $details,
                        'data'             => ['details' => $details],
                        'details_grouped'  => $detailsGrouped,
                        'email'            => $email,
                    ])->setPaper('a4', 'portrait');
                Storage::disk('idcloudhost')->put($pdfPath, $pdfLow->output());
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to re-render KMP PDF lower DPI', ['error' => $e->getMessage()]);
        }

        // Convert details collection to array for JSON response
        $detailsArray = [];
        if (!empty($details)) {
            foreach ($details as $detail) {
                $detailsArray[] = [
                    'id' => $detail->id,
                    'sph_id' => $detail->sph_id,
                    'cname_lname' => $detail->cname_lname,
                    'product' => $detail->product,
                    'biaya_lokasi' => $detail->biaya_lokasi,
                    'qty' => $detail->qty,
                    'price_liter' => $detail->price_liter,
                    'ppn' => $detail->ppn,
                    'pbbkb' => $detail->pbbkb,
                    'transport' => $detail->transport,
                    'total_price' => $detail->total_price,
                    'grand_total' => $detail->grand_total,
                    'created_at' => $detail->created_at,
                    'updated_at' => $detail->updated_at,
                ];
            }
        }

        // Get template name from sph_template table
        $templateName = null;
        if (!empty($sph->template_id)) {
            $templateName = DB::table('sph_template')
                ->where('id', $sph->template_id)
                ->value('nama');
        }

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        Log::info('KMP PDF Generation Completed', [
            'sph_id' => $id,
            'execution_time_seconds' => $executionTime,
            'details_count' => count($detailsArray),
            'has_details' => $hasDetails
        ]);

        return response()->json([
            'success'        => true,
            'pdf_url'        => 'https://is3.cloudhost.id/bensinkustorage/' . $pdfPath,
            'path'           => $pdfPath,
            'tipe_sph_used'  => $incomingType,
            'has_details'    => $hasDetails,
            'details'        => $detailsArray,
            'details_count'  => count($detailsArray),
            'details_grouped' => $detailsGrouped,
            'details_group_count' => count($detailsGrouped),
            'template_used'  => $bladeTemplate,
            'template_file'  => $reportedTemplateFile,
            'template_name'  => $templateName,
            'template_id'    => $sph->template_id,
            'execution_time' => $executionTime . 's',
        ]);
    }

}
