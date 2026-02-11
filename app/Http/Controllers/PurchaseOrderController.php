<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\PurchaseOrder;
use App\Models\DeliveryRequest;
use App\Models\User;
use App\Helpers\WorkflowHelper;
use App\Helpers\AuthValidator;
use App\Helpers\UserSysLogHelper;
use App\Models\GoodReceipt;
use App\Models\DataTrxSph;

class PurchaseOrderController extends Controller
{
public function list(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        // List Data untuk datatable
        $query = PurchaseOrder::query();

        // Month filter (YYYY-MM)
        if ($request->has('month') && $request->month) {
            $query->whereRaw("DATE_FORMAT(tgl_po, '%Y-%m') = ?", [$request->month]);
        }

        // Status filter (supports named and numeric values)
        if ($request->filled('status')) {
            $statusParam = $request->status;
            if (is_numeric($statusParam)) {
                // Direct numeric status filter
                $query->where('status', (int) $statusParam);
            } else {
                // Named status filters
                switch ($statusParam) {
                    case 'draft':
                        $query->where('status', 0);
                        break;
                    case 'approvallist':
                        $query->where('status', 1);
                        break;
                    case 'reject':
                        $query->where('status', 3);
                        break;
                    case 'approved':
                        $query->where('status', 4);
                        break;
                }
            }
        }

        // Category filter (Supplier=1, Transporter=2)
        if ($request->has('category') && in_array($request->category, [1,2])) {
            $query->where('category', $request->category);
        }

        // id filter with optional category filter
        if ($request->filled('id')) {
            $query->where('id', $request->id);
            // Also apply category filter when present alongside id
            if ($request->filled('category') && in_array((int)$request->category, [1,2])) {
                $query->where('category', (int)$request->category);
            }
        }

        // drs_unique filter with optional category filter
        if ($request->filled('drs_unique')) {
            $query->where('drs_unique', $request->drs_unique);
            // Also apply category filter when present alongside drs_unique
            if ($request->filled('category') && in_array((int)$request->category, [1,2])) {
                $query->where('category', (int)$request->category);
            }
        }

        $poList = $query->orderBy('created_at', 'desc')->get();


        $data = [];
        foreach ($poList as $i => $row) {
            $DnNo = DeliveryRequest::where('drs_unique', $row->drs_unique)->pluck('dn_no')->first();
            $custPO = GoodReceipt::where('po_no', $row->customer_po)->select('total','nama_customer')->first();

            $data[] = [
                'no'               => $i + 1,
                'id'               => $row->id,
                'dn_no'            => $DnNo,
                'drs_no'           => $row->drs_no,
                'drs_unique'       => $row->drs_unique,
                'cust_total'       => $custPO['total'] ?? 0,
                'customer_po'      => $row->customer_po,
                'customer_name'     => $custPO['nama_customer'] ?? null,
                'vendor_name'      => $row->vendor_name,
                'vendor_po'        => $row->vendor_po,
                'tgl_po'           => $row->tgl_po ? Carbon::parse($row->tgl_po)->format('Y-m-d') : null,
                'nama'             => $row->nama,
                'alamat'           => $row->alamat,
                'contact'          => $row->contact,
                'fob'              => $row->fob,
                'term'             => $row->term,
                'transport'        => $row->transport,
                'loading_point'    => $row->loading_point,
                'shipped_via'      => $row->shipped_via,
                'delivery_to'      => $row->delivery_to,
                'qty'              => $row->qty,
                'harga'            => $row->harga,
                'ppn'              => $row->ppn,
                'pph'              => $row->pph,
                'pbbkb'            => $row->pbbkb,
                'bph'              => $row->bph,
                'portal'           => $row->portal,
                'sub_total'        => $row->sub_total,
                'total'            => $row->total,
                'terbilang'        => $row->terbilang,
                'description'      => $row->description,
                'additional_notes' => $row->additional_notes,
                'created_at'       => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i') : '',
                'created_by'       => $row->created_by,
                'status'           => $row->status,
                'id'               => $row->id,
                'category'         => $row->category,
                'file'             => $row->file,
            ];
        }

        // Summary Card Data
        $totalSupplier    = PurchaseOrder::where('category', 1)->count();
        $totalTransporter = PurchaseOrder::where('category', 2)->count();
        $totalSupplierAppr = PurchaseOrder::where('category', 1)->where('status', 1)->count();
        $totalTransporterAppr = PurchaseOrder::where('category', 2)->where('status', 1)->count();
        $waitingApproval  = PurchaseOrder::where('status', 1)->count();
        $approved         = PurchaseOrder::where('status', 4)->count();
        $rejected         = PurchaseOrder::where('status', 3)->count();

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'list');

        return response()->json([
            'data' => $data,
            'summary' => [
                'total_supplier'     => $totalSupplier,
                'total_transporter'  => $totalTransporter,
                'supplier_waiting'  => $totalSupplierAppr,
                'transporter_waiting' => $totalTransporterAppr,
                'waiting_approval'   => $waitingApproval,
                'approved'           => $approved,
                'rejected'           => $rejected,
            ]
        ]);
    }


public function poTransporter(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";

        // 1. Ambil data transporter (supplier_transporter)
        $vendorName = trim($request->input('vendor_name'));

        $vendor = DB::table('data_supplier_transporter')
            ->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower($vendorName)])
            ->first();

        if (!$vendor) {
            return response()->json(['message' => 'Data vendor tidak ditemukan'], 422);
        }

        // 2. Validasi: Cek apakah nomor DN sudah digunakan di purchase_order
        $dnNo = $request->input('dn_no');
        if ($dnNo) {
            $existingDn = PurchaseOrder::where('dn_no', $dnNo)->first();
            if ($existingDn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomer DN sudah di gunakan , gunakan nomer yang lain'
                ], 422);
            }
        }

        // 3. Ambil atau generate vendor_po
        // Gunakan vendor_po dari payload jika ada, jika tidak generate otomatis
        $vendor_po = $request->input('vendor_po');
        if (!$vendor_po) {
        $format = $vendor->format; // contoh: {nomor}/MMLN-AJS/{bulan}/{tahun}
        $dnNomer = $request->input('dn_no'); // misal: 03.032

        $bulanRomawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][date('n')-1];
        $tahunFull = date('Y');
        $vendor_po = str_replace(
            ['{nomor}', '{NOMOR}', '{bulan}', '{BULAN}', '{tahun}', '{TAHUN}'],
            [$dnNomer, $dnNomer, $bulanRomawi, $bulanRomawi, $tahunFull, $tahunFull],
            $format
        );
        }

        $now = Carbon::now()->format('Y-m-d');

        // 4. Buat PO
        $po = new PurchaseOrder();
        $po->dn_no        = $request->input('dn_no') ?: null;
        $po->drs_no       = $request->input('drs_no') ?: null;
        $po->drs_unique   = $request->input('drs_unique') ?: null;
        $po->customer_po  = $request->input('customer_po');
        $po->vendor_name  = $vendorName;
        $po->vendor_po    = $vendor_po;
        $po->tgl_po       = $request->input('po_date');;
        $po->nama         = $request->input('pic_site');
        $po->alamat       = $request->input('site_location');
        $po->contact      = $request->input('pic_site_telp');
        $po->fob          = $request->input('fob');
        $po->term         = $request->input('term');
        $po->transport    = $request->input('harga');
        $po->loading_point = $request->input('loading_point');
        $po->shipped_via  = $request->input('shipped_via');
        $po->delivery_to  = $request->input('delivery_to');
        $po->qty          = $request->input('qty');
        $po->harga        = $request->input('harga');
        $po->ppn          = $request->input('ppn');
        $po->sub_total    = $request->input('sub_total');
        $po->total        = $request->input('total');
        $po->terbilang    = $request->input('terbilang');
        $po->special_notes = $request->input('special_notes');
        $po->description = $request->input('description');
        $po->portal = $request->input('portal');
        $po->category     = 2; // Transporter
        $po->status       = 1; // Menunggu Approval
        $po->created_by   = $fullName;
        $po->last_updateby = $fullName;
        $po->created_at   = now();
        $po->updated_at   = now();
        $po->save();

        // Buat workflow record dan remark menggunakan helper
        // trx_id menggunakan ID purchase_order yang baru dibuat
        $poId = $po->id;
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        WorkflowHelper::createWorkflowWithRemark(
            $poId,
            'po_transporter',
            "User {$fullName} request pengajuan dengan no po {$vendor_po} di {$timestamp}",
            $fullName
        );

        $id = $po->id;

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'poTransporter');

        return response()->json([
            'success' => true,
            'message' => 'PO anda berhasil diajukan!',
            'vendor_po'      => $vendor_po
        ]);
    }

    /**
     * Update an existing PurchaseOrder.
     */
public function update(Request $request, $po_id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id'] ?? null);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 401);
        }
        $fullName  = "{$user->first_name} {$user->last_name}";
        // Validate incoming data
        $validated = $request->validate([
            'drs_no'          => 'required|string',
            // 'drs_unique'      => 'required|string', // removed as per instruction
            'customer_po'     => 'nullable|string',
            'vendor_name'     => 'required|string',
            'vendor_po'       => 'required|string',
            'tgl_po'          => 'required|date',
            'nama'            => 'nullable|string',
            'alamat'          => 'nullable|string',
            'contact'         => 'nullable|string',
            'fob'             => 'nullable|string',
            'term'            => 'nullable|string',
            'transport'       => 'nullable|numeric',
            'loading_point'   => 'nullable|string',
            'shipped_via'     => 'nullable|string',
            'delivery_to'     => 'nullable|string',
            'qty'             => 'nullable|numeric',
            'harga'           => 'nullable|numeric',
            'ppn'             => 'nullable|numeric',
            'pbbkb'           => 'nullable|numeric',
            'pph'             => 'nullable|numeric',
            'bph'             => 'nullable|numeric',
            'portal'          => 'nullable|numeric',
            'sub_total'       => 'nullable|numeric',
            'total'           => 'nullable|numeric',
            'terbilang'       => 'nullable|string',
            'description'     => 'nullable|string',
            'additional_notes'=> 'nullable|string',
            'category'        => 'required|numeric|in:1,2', // 1 = Supplier, 2 = Transporter
        ]);

        // Find and update
        $po = PurchaseOrder::findOrFail($po_id);

        // Assign all validated fields to the model
        foreach ($validated as $field => $value) {
            $po->{$field} = $value;
        }
        $po->status = 1;
        $po->save();

        $poId = $po->id;
        $vendor_po = $po->vendor_po;
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        WorkflowHelper::createWorkflowWithRemark(
            $poId,
            'po_transporter',
            "User {$fullName} mengajukan approval untuk no po {$vendor_po} di {$timestamp}",
            $fullName
        );

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'update');

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order berhasil diperbarui.'
        ]);
    }

    /**
     * Verify or reject a Purchase Order
     */
public function verify(Request $request, $poId)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";
        // Get all assigned role IDs for the user
        $userRoleIds = DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->pluck('role_id')
            ->toArray();

        // Validate inputs
        $validated = $request->validate([
            'verify_status'  => 'required|in:approve,revisi,reject',
            'verify_comment' => 'required|string',
        ]);

        // Find the PurchaseOrder
        $po = PurchaseOrder::findOrFail($poId);
        $category = $request['category'];
        if ($category == 1) {
            $cat = "po_supplier";
        }else{
            $cat = "po_transporter";
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // 1. Check active workflow_record
            $wf = DB::table('workflow_record')
                ->where('trx_id', $po->id)
                ->where('tipe_trx', $cat)
                ->where('wf_status', 1)
                ->lockForUpdate()
                ->first();
            if (!$wf) {
                return response()->json(['message' => 'Workflow aktif tidak ditemukan'], 422);
            }

            // Allow if user has the current role in workflow or is superadmin (role_id = 1)
            if (!in_array($wf->curr_role, $userRoleIds) && !in_array(1, $userRoleIds)) {
                return response()->json(['message' => 'Anda tidak memiliki hak untuk verifikasi'], 403);
            }

            // 3. Advance or finalize workflow
            $nextWfId = $wf->id;
            if ($validated['verify_status'] === 'approve') {
                if ($wf->next_role) {
                    // Create next workflow step
                    $newWf = DB::table('workflow_record')->insertGetId([
                        'trx_id'    => $wf->trx_id,
                        'tipe_trx'  => $wf->tipe_trx,
                        'curr_role' => $wf->next_role,
                        'next_role' => null,
                        'wf_status' => 1,
                        'created_at'=> now(),
                        'updated_at'=> now(),
                    ]);
                    $nextWfId = $newWf;
                } else {
                    // Final approval: update PO status
                    $po->status = 4;
                    $po->save();

                    // Generate PDF when approved and save URL to database
                    try {
                        $pdfResponse = $this->generatePDF($po->id);
                        if (is_a($pdfResponse, \Illuminate\Http\JsonResponse::class)) {
                            $pdfData = $pdfResponse->getData(true);
                            if (!empty($pdfData['pdf_url'])) {
                                $po->file = $pdfData['pdf_url'];
                                $po->save();
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Auto generate PDF after final approval failed', [
                            'po_id' => $po->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
           } elseif ($validated['verify_status'] === 'revisi') {
                // Mark workflow as revisi
                DB::table('workflow_record')
                    ->where('id', $wf->id)
                    ->update(['wf_status' => 2, 'updated_at' => now()]);
                // Set PO status to 'revisi' (status code 2)
                $po->status = 2;
                $po->save();
            } else {
                // Reject
                $po->status = 3;
                $po->save();
            }

            // 4. Insert workflow_remark with correct action word
            switch ($validated['verify_status']) {
                case 'approve':
                    $action = 'approved';
                    break;
                case 'revisi':
                    $action = 'requested revision for';
                    break;
                default:
                    $action = 'rejected';
                    break;
            }
            $msg = "User {$fullName} {$action} PO {$po->vendor_po}: {$validated['verify_comment']}";

            WorkflowHelper::createWorkflowWithRemark(
                $po->id,
                $cat,
                $msg,
                $fullName
            );

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'verify', 'Verify PO: ' . $validated['verify_status']);

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi berhasil.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Verifikasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }
public function savePoSupplier(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";
        // Validasi
        $validated = $request->validate([
            'drs_no'          => 'nullable|string',
            'drs_unique'      => 'nullable|string',
            'po_no'           => 'nullable|string', // dari frontend, akan di-map ke customer_po
            'customer_po'     => 'nullable|string',
            'vendor_name'     => 'required|string',
            'vendor_po'       => 'required|string',
            'tgl_po'          => 'required|date',
            'nama'            => 'nullable|string',
            'alamat'          => 'nullable|string',
            'contact'         => 'nullable|string',
            'fob'             => 'nullable|string',
            'term'            => 'nullable|string',
            'loading_point'   => 'nullable|string',
            'shipped_via'     => 'nullable|string',
            'delivery_to'     => 'nullable|string',
            'qty'             => 'nullable|numeric',
            'harga'           => 'nullable|numeric',
            'ppn'             => 'nullable|numeric',
            'pbbkb'           => 'nullable|numeric',
            'pph'             => 'nullable|numeric',
            'bph'             => 'nullable|numeric',
            'sub_total'       => 'nullable|numeric',
            'total'           => 'nullable|numeric',
            'terbilang'       => 'nullable|string',
            'description'     => 'nullable|string',
            'additional_notes'=> 'nullable|string',
            'category'        => 'required|numeric|in:1', // 1 = Supplier
             // 0 = Draft, 1 = Menunggu Approval, dll.

        ]);

        // Simpan ke database
        $po = new PurchaseOrder();
        $po->drs_no          = $validated['drs_no'] ?? null;
        $po->drs_unique      = !empty($validated['drs_unique']) ? $validated['drs_unique'] : null;
        // Map po_no dari frontend ke customer_po, atau gunakan customer_po jika ada
        $po->customer_po     = $validated['po_no'] ?? $validated['customer_po'] ?? null;
        $po->vendor_name     = $validated['vendor_name'];
        $po->vendor_po       = $validated['vendor_po'];
        $po->tgl_po          = $validated['tgl_po'];
        $po->nama            = $validated['nama'];
        $po->alamat          = $validated['alamat'];
        $po->contact         = $validated['contact'];
            $po->fob             = $validated['fob'];
            $po->term            = $validated['term'];
            $po->loading_point   = $validated['loading_point'];
        $po->shipped_via     = $validated['shipped_via'];
        $po->delivery_to     = $validated['delivery_to'];
        $po->qty             = $validated['qty'];
        $po->harga           = $validated['harga'];
        $po->ppn             = $validated['ppn'];
        $po->pbbkb           = $validated['pbbkb'];
        $po->pph             = $validated['pph'];
        $po->bph             = $validated['bph'];
        $po->sub_total       = $validated['sub_total'];
        $po->total           = $validated['total'];
        $po->terbilang       = $validated['terbilang'];
        $po->description     = $validated['description'];
        $po->additional_notes= $validated['additional_notes'];
        $po->category        = 1; // 1 untuk supplier
        $po->status          = 1;
        $po->created_by      = $fullName;
        $po->save();

        $vendor_po = $po->vendor_po;
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        
        // WorkflowHelper mengharapkan int untuk trxId
        // Gunakan po->id karena trx_id di workflow_record adalah int
        // dan po->id selalu int dan tidak null
        $poId = $po->id;
        
        WorkflowHelper::createWorkflowWithRemark(
            $poId,
            'po_supplier',
            "User {$fullName} mengajukan approval untuk no po {$vendor_po} di {$timestamp}",
            $fullName
        );

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'savePoSupplier');

        return response()->json([
            'success' => true,
            'message' => 'PO Supplier berhasil disimpan.',
            'data'    => $po
        ]);
    }

public function listPoDrs(Request $request)
    {
        $drsList = DB::table('delivery_request as a')
            ->leftJoin('purchase_order as b', 'b.drs_unique', '=', 'a.drs_unique')
            ->where('b.category', 2)
            ->select('a.drs_no', 'a.drs_unique')
            ->get();

        return response()->json([
            'data' => $drsList
        ]);
    }

        /**
     * Generate PDF for Purchase Order
     */
public function generatePDF($poId)
    {
        try {
            Log::info('Starting PDF generation for PO', ['po_id' => $poId]);

            $po = PurchaseOrder::findOrFail($poId);
            Log::info('PO found', [
                'po_id' => $po->id,
                'category' => $po->category,
                'vendor_po' => $po->vendor_po,
                'vendor_name' => $po->vendor_name
            ]);

            // Determine template based on category
            $template = $po->category == 1 ? 'pdf.po_supplier_template' : 'pdf.po_transporter_template';
            Log::info('Template selected', ['template' => $template, 'category' => $po->category]);

            // Prepare data for PDF template
            $poData = [
                'to' => $po->vendor_name,
                'name' => $po->nama,
                'address' => $po->alamat,
                'phone_fax' => $po->contact,
                'po_number' => $po->vendor_po,
                'po_date' => $po->tgl_po ? Carbon::parse($po->tgl_po)->format('d/m/Y') : '',
                'delivered_to' => $po->delivery_to,
                'loading_point' => $po->loading_point,
                'comments' => $po->additional_notes ?? '',
                'shipped_via' => $po->shipped_via,
                'fob_point' => $po->fob,
                'term' => $po->term,
                'transport' => $po->transport ? number_format($po->transport, 0, ',', '.') : '0',
                'quantity' => $po->qty,
                'description' => $po->description,
                'unit_price' => $po->harga ? number_format($po->harga, 0, ',', '.') : '0',
                'amount' => $po->harga && $po->qty ? number_format($po->harga * $po->qty, 0, ',', '.') : '0',
                'sub_total' => $po->sub_total ? number_format($po->sub_total, 0, ',', '.') : '0',
                'ppn' => $po->ppn ? number_format($po->ppn, 0, ',', '.') : '0',
                'pbbkb' => $po->pbbkb ? number_format($po->pbbkb, 0, ',', '.') : '0',
                'pph' => $po->pph ? number_format($po->pph, 0, ',', '.') : '0',
                'bph' => $po->bph ? number_format($po->bph, 0, ',', '.') : '0',
                'portal_money' => $po->portal ? number_format($po->portal, 0, ',', '.') : '0',
                'total' => $po->total ? number_format($po->total, 0, ',', '.') : '0',
                'type' => $po->category == 1 ? 'SUPPLIER' : 'TRANSPORTER'
            ];

            Log::info('PO data prepared', ['po_data' => $poData]);

            // Get user data for the template
            $user = User::where('first_name', 'LIKE', '%' . explode(' ', $po->created_by)[0] . '%')
                       ->orWhere('last_name', 'LIKE', '%' . explode(' ', $po->created_by)[1] . '%')
                       ->first();

            $userData = [
                'name' => $user ? $user->first_name . ' ' . $user->last_name : $po->created_by
            ];

            Log::info('User data prepared', ['user_data' => $userData]);

            // Check if template exists
            if (!view()->exists($template)) {
                Log::error('Template not found', ['template' => $template]);
                return response()->json([
                    'success' => false,
                    'error' => 'Template not found: ' . $template
                ], 500);
            }

            // Generate PDF
            Log::info('Starting PDF generation with template', ['template' => $template]);

            $pdf = Pdf::setOptions([
                'enable_remote' => true,
                'isRemoteEnabled' => true,
                'dpi' => 110,
                'defaultFont' => 'sans-serif'
            ])
            ->loadView($template, [
                'poTransport' => (object) $poData,
                'user' => (object) $userData
            ])
            ->setPaper('a4', 'portrait');

            Log::info('PDF object created successfully');

            // Generate filename and save to storage
            $pdfFileName = 'po_' . ($po->category == 1 ? 'supplier' : 'transporter') . '_' . $po->vendor_po . '_' . time() . '.pdf';
            $pdfPath = 'purchase_orders/' . $pdfFileName;

            Log::info('Attempting to save PDF to storage', [
                'pdf_path' => $pdfPath,
                'storage_disk' => 'byteplus'
            ]);

            // Check if storage disk exists
            if (!Storage::disk('byteplus')) {
                Log::error('Storage disk byteplus not found');
                return response()->json([
                    'success' => false,
                    'error' => 'Storage disk byteplus not configured'
                ], 500);
            }

            $pdfContent = $pdf->output();
            Log::info('PDF content generated', ['content_size' => strlen($pdfContent)]);

            $saved = Storage::disk('byteplus')->put($pdfPath, $pdfContent);

            if (!$saved) {
                Log::error('Failed to save PDF to storage', ['pdf_path' => $pdfPath]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to save PDF to storage'
                ], 500);
            }

            Log::info('PDF saved to storage successfully', ['pdf_path' => $pdfPath]);

            // Generate full public URL
            $fullUrl = byteplus_url($pdfPath);

            Log::info('Purchase Order PDF Generated Successfully', [
                'po_id' => $po->id,
                'category' => $po->category,
                'template' => $template,
                'pdf_path' => $pdfPath,
                'full_url' => $fullUrl,
                'file_size' => Storage::disk('byteplus')->size($pdfPath)
            ]);

            return response()->json([
                'success' => true,
                'category' => $po->category,
                'template_used' => $template,
                'pdf_url' => $fullUrl,
                'pdf_path' => $pdfPath
            ]);

        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'po_id' => $poId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'PDF generation failed: ' . $e->getMessage(),
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * List payment data for PO with status = 4 (approved)
     */
public function listPayment(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Base query for PO with status = 4 (approved)
            $query = PurchaseOrder::where('status', 4);

            // Filter by payment status
            if ($request->filled('filter_status')) {
                $paymentStatus = $request->filter_status;
                if ($paymentStatus === 'pending') {
                    // Belum dibayar - payment_status = 0
                    $query->where('payment_status', 0);
                } elseif ($paymentStatus === 'paid') {
                    // Sudah dibayar - payment_status = 1
                    $query->where('payment_status', 1);
                }
            }

            // Filter by category (Supplier=1, Transporter=2)
            if ($request->filled('filter_category')) {
                $category = $request->filter_category;
                if (in_array($category, ['1', '2'])) {
                    $query->where('category', (int) $category);
                }
            }

            // Get the filtered data
            $poList = $query->orderBy('created_at', 'desc')->get();

            $data = [];
            foreach ($poList as $i => $row) {
                $DnNo = DeliveryRequest::where('drs_unique', $row->drs_unique)->pluck('dn_no')->first();
                $custPO = GoodReceipt::where('po_no', $row->customer_po)->select('total','nama_customer')->first();

                // Determine payment status based on payment_status column
                $paymentStatus = $row->payment_status == 1 ? 'paid' : 'pending';
                $paymentStatusText = $row->payment_status == 1 ? 'Sudah Dibayar' : 'Belum Dibayar';

                $data[] = [
                    'no'               => $i + 1,
                    'id'               => $row->id,
                    'dn_no'            => $DnNo,
                    'drs_no'           => $row->drs_no,
                    'drs_unique'       => $row->drs_unique,
                    'cust_total'       => $custPO['total'] ?? 0,
                    'customer_po'      => $row->customer_po,
                    'customer_name'    => $custPO['nama_customer'] ?? null,
                    'vendor_name'      => $row->vendor_name,
                    'vendor_po'        => $row->vendor_po,
                    'tgl_po'           => $row->tgl_po ? Carbon::parse($row->tgl_po)->format('Y-m-d') : null,
                    'nama'             => $row->nama,
                    'alamat'           => $row->alamat,
                    'contact'          => $row->contact,
                    'fob'              => $row->fob,
                    'term'             => $row->term,
                    'transport'        => $row->transport,
                    'loading_point'    => $row->loading_point,
                    'shipped_via'      => $row->shipped_via,
                    'delivery_to'      => $row->delivery_to,
                    'qty'              => $row->qty,
                    'harga'            => $row->harga,
                    'ppn'              => $row->ppn,
                    'pph'              => $row->pph,
                    'pbbkb'            => $row->pbbkb,
                    'bph'              => $row->bph,
                    'portal'           => $row->portal,
                    'sub_total'        => $row->sub_total,
                    'total'            => $row->total,
                    'terbilang'        => $row->terbilang,
                    'description'      => $row->description,
                    'additional_notes' => $row->additional_notes,
                    'created_at'       => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i') : '',
                    'created_by'       => $row->created_by,
                    'status'           => $row->status,
                    'category'         => $row->category,
                    'file'             => $row->file,
                    'receipt_file'     => $row->receipt_file,
                    'payment_date'     => $row->payment_date ? Carbon::parse($row->payment_date)->format('Y-m-d') : null,
                    'receipt_number'   => $row->receipt_number,
                    'payment_status'   => $paymentStatus,
                    'payment_status_text' => $paymentStatusText,
                    'category_text'    => $row->category == 1 ? 'Supplier' : 'Transporter'
                ];
            }

            // Summary data - Calculate total amounts instead of counts
            $totalPendingAmount = PurchaseOrder::where('status', 4)->where('payment_status', 0)->sum('total');
            $totalPaidAmount = PurchaseOrder::where('status', 4)->where('payment_status', 1)->sum('total');
            $totalSupplierAmount = PurchaseOrder::where('status', 4)->where('category', 1)->sum('total');
            $totalTransporterAmount = PurchaseOrder::where('status', 4)->where('category', 2)->sum('total');

            return response()->json([
                'success' => true,
                'data' => $data,
                'summary' => [
                    'total_pending'    => 'Rp ' . number_format($totalPendingAmount, 0, ',', '.'),
                    'total_paid'       => 'Rp ' . number_format($totalPaidAmount, 0, ',', '.'),
                    'total_supplier'   => 'Rp ' . number_format($totalSupplierAmount, 0, ',', '.'),
                    'total_transporter' => 'Rp ' . number_format($totalTransporterAmount, 0, ',', '.'),
                    'total_all'        => 'Rp ' . number_format($totalPendingAmount + $totalPaidAmount, 0, ',', '.')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting payment list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail items for a specific PO number
     */
    public function getPoDetails(Request $request)
    {
        // Validate request
        $request->validate([
            'po_no' => 'required|string'
        ]);

        try {
            $poNo = $request->po_no;

            // Query untuk mendapatkan detail items berdasarkan PO NO
            $details = DB::table('detail_good_receipt as a')
                ->leftJoin('good_receipt as b', 'b.id', '=', 'a.gr_id')
                ->where('b.po_no', $poNo)
                ->select([
                    'a.id',
                    'a.gr_id',
                    'a.description',
                    'a.quantity',
                    'a.price',
                    'a.total',
                    'a.created_at',
                    'a.updated_at',
                    'b.po_no',
                    'b.gr_no',
                    'b.tgl_gr',
                    'b.nama_customer',
                    'b.status as gr_status'
                ])
                ->orderBy('a.created_at', 'desc')
                ->get();

            // Check if PO exists
            if ($details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada detail items ditemukan untuk PO No: ' . $poNo
                ], 404);
            }

            // Get PO information
            $poInfo = PurchaseOrder::where('vendor_po', $poNo)
                ->orWhere('customer_po', $poNo)
                ->first();

            return response()->json([
                'success' => true,
                'po_info' => $poInfo,
                'po_no' => $poNo,
                'total_items' => $details->count(),
                'data' => $details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload bukti pembayaran untuk PO
     */
    public function uploadPaymentReceipt(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Validate request
            $validated = $request->validate([
                'po_id' => 'required|integer|exists:purchase_order,id',
                'payment_date' => 'required|date',
                'receipt_number' => 'required|string|max:100',
                'payment_receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:1024' // max 1MB
            ]);

            // Find PO
            $po = PurchaseOrder::findOrFail($validated['po_id']);

            // Check if PO status is approved (status = 4)
            if ($po->status != 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'PO harus dalam status approved untuk dapat upload bukti pembayaran'
                ], 422);
            }

            // Check if payment already exists
            if ($po->payment_status == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bukti pembayaran sudah diupload sebelumnya'
                ], 422);
            }

            // Handle file upload
            if ($request->hasFile('payment_receipt')) {
                $file = $request->file('payment_receipt');
                $nameFile = $request->receipt_number;

                // Generate unique filename
                $fileName = 'receipt_' . $nameFile. '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = 'po_receipts/' .$fileName;

                // Check if storage disk exists
                if (!Storage::disk('byteplus')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Storage disk byteplus not configured'
                    ], 500);
                }

                // Upload file to byteplus
                $uploaded = Storage::disk('byteplus')->put($filePath, file_get_contents($file));

                if (!$uploaded) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal upload file ke storage'
                    ], 500);
                }

                // Generate full public URL
                $fullUrl = byteplus_url($filePath);

                // Update PO with payment information
                $po->payment_date = $validated['payment_date'];
                $po->receipt_number = $validated['receipt_number'];
                $po->receipt_file = $fullUrl;
                $po->payment_status = 1; // Set as paid
                $po->save();

                Log::info('Payment receipt uploaded successfully', [
                    'po_id' => $po->id,
                    'vendor_po' => $po->vendor_po,
                    'payment_date' => $validated['payment_date'],
                    'receipt_number' => $validated['receipt_number'],
                    'file_path' => $filePath,
                    'file_size' => Storage::disk('byteplus')->size($filePath)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Bukti pembayaran berhasil diupload',
                    'data' => [
                        'po_id' => $po->id,
                        'vendor_po' => $po->vendor_po,
                        'payment_date' => $validated['payment_date'],
                        'receipt_number' => $validated['receipt_number'],
                        'receipt_file' => $fullUrl
                    ]
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File bukti pembayaran tidak ditemukan'
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error uploading payment receipt', [
                'po_id' => $request->po_id ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload bukti pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of po_no from good_receipt
     * Endpoint: GET /good_receipt/gr_list_no
     */
    public function grListNo(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Ambil list po_no dari good_receipt
            $poList = GoodReceipt::select('po_no')
                ->whereNotNull('po_no')
                ->where('po_no', '!=', '')
                ->distinct()
                ->orderBy('po_no', 'asc')
                ->get()
                ->pluck('po_no');

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'grListNo');

            return response()->json([
                'success' => true,
                'data' => $poList->values()->all()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting GR list no', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail good_receipt by po_no
     * Endpoint: GET /good_receipt/gr_detail/{po_no}
     */
    public function grDetail(Request $request, $po_no)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Ambil detail good_receipt berdasarkan po_no dengan join ke data_trx_sph untuk mendapatkan tipe_sph
            $gr = GoodReceipt::where('good_receipt.po_no', $po_no)
                ->leftJoin('data_trx_sph', function($join) {
                    $join->on('data_trx_sph.kode_sph', '=', 'good_receipt.kode_sph')
                         ->whereRaw('data_trx_sph.id = (SELECT MAX(id) FROM data_trx_sph WHERE kode_sph = good_receipt.kode_sph)');
                })
                ->select(
                    'good_receipt.po_no',
                    'good_receipt.no_seq',
                    'good_receipt.total',
                    'data_trx_sph.tipe_sph'
                )
                ->first();

            if (!$gr) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data good receipt dengan po_no ' . $po_no . ' tidak ditemukan'
                ], 404);
            }

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'grDetail');

            return response()->json([
                'success' => true,
                'data' => [
                    'po_no' => $gr->po_no,
                    'no_seq' => $gr->no_seq,
                    'total' => $gr->total,
                    'tipe_sph' => $gr->tipe_sph
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting GR detail', [
                'po_no' => $po_no,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List Purchase Order Supplier yang sudah approved
     * Endpoint: GET /list/purchase-order/supplier/approve
     */
    public function listPurchaseOrderSupplierApprove(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Query: SELECT id, po_no as customer_po FROM good_receipt
            $data = DB::table('good_receipt')
                ->select('id','nama_customer', DB::raw('po_no as customer_po'))
                ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'listPurchaseOrderSupplierApprove');

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting approved supplier PO list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail Purchase Order Supplier by po_no dengan validasi
     * Endpoint: GET /list/purchase-order/supplier/approve/{po_no}
     */
    public function getPurchaseOrderSupplierApproveDetail(Request $request, $po_no)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Check 1: SELECT id, po_no FROM good_receipt 
            // WHERE status = 4 AND bypass = 1 AND po_no = '{po_no}'
            $check1 = DB::table('good_receipt')
                ->where('status', 0)
                ->where('bypass', 1)
                ->where('po_no', $po_no)
                ->select('id', 'po_no','no_seq','nama_customer')
                ->first();

            if ($check1) {
                // Log aktivitas user
                UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'getPurchaseOrderSupplierApproveDetail');

                return response()->json([
                    'success' => true,
                    'message' => 'Data ditemukan (Check 1)',
                    'data' => $check1
                ], 200);
            }

            // Check 2: SELECT gr.id, gr.po_no as customer_po FROM good_receipt gr
            // LEFT JOIN purchase_order po ON po.customer_po = gr.po_no
            // WHERE gr.po_no = '{po_no}' AND po.status = 4
            $check2 = DB::table('good_receipt as gr')
                ->leftJoin('purchase_order as po', 'po.customer_po', '=', 'gr.po_no')
                ->where('gr.po_no', $po_no)
                ->where('po.status', 4)
                ->select('gr.id','gr.no_seq','gr.nama_customer', DB::raw('gr.po_no as customer_po'))
                ->first();

            if ($check2) {
                // Log aktivitas user
                UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'getPurchaseOrderSupplierApproveDetail');

                return response()->json([
                    'success' => true,
                    'message' => 'Data ditemukan (Check 2)',
                    'data' => $check2
                ], 200);
            }

            // Jika tidak ada di kedua check, return HTTP 400 dengan message
            return response()->json([
                'success' => false,
                'message' => 'Hubungi Finance untuk membuat PO Supplier terlebih dahulu'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error getting approved supplier PO detail', [
                'po_no' => $po_no,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail Purchase Order Supplier by ID
     * Endpoint: GET /list/purchase-order/supplier/{id}/details
     */
    public function listPurchaseOrderSupplierDetails(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Ambil data purchase_order berdasarkan id
            $purchaseOrder = PurchaseOrder::find($id);
            
            if (!$purchaseOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase Order tidak ditemukan'
                ], 404);
            }

            // Ambil data data_trx_sph melalui relasi: customer_po -> good_receipt -> kode_sph -> data_trx_sph
            $dataTrxSph = null;
            if ($purchaseOrder->customer_po) {
                $goodReceipt = GoodReceipt::where('po_no', $purchaseOrder->customer_po)->first();
                if ($goodReceipt && $goodReceipt->kode_sph) {
                    $dataTrxSph = DataTrxSph::where('kode_sph', $goodReceipt->kode_sph)->first();
                }
            }

            if (!$dataTrxSph) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data SPH tidak ditemukan untuk Purchase Order ini'
                ], 404);
            }

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'PurchaseOrder', 'listPurchaseOrderSupplierDetails');

            // Return kolom-kolom yang diminta dari data_trx_sph
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $dataTrxSph->id,
                    'template_id' => $dataTrxSph->template_id,
                    'tipe_sph' => $dataTrxSph->tipe_sph,
                    'kode_sph' => $dataTrxSph->kode_sph,
                    'comp_name' => $dataTrxSph->comp_name,
                    'pic' => $dataTrxSph->pic,
                    'contact_no' => $dataTrxSph->contact_no,
                    'product' => $dataTrxSph->product,
                    'price_liter' => $dataTrxSph->price_liter,
                    'biaya_lokasi' => $dataTrxSph->biaya_lokasi,
                    'ppn' => $dataTrxSph->ppn,
                    'pbbkb' => $dataTrxSph->pbbkb,
                    'total_price' => $dataTrxSph->total_price,
                    'oat' => $dataTrxSph->oat,
                    'ppn_oat' => $dataTrxSph->ppn_oat,
                    'oat_lokasi' => $dataTrxSph->oat_lokasi,
                    'pay_method' => $dataTrxSph->pay_method,
                    'susut' => $dataTrxSph->susut,
                    'note_berlaku' => $dataTrxSph->note_berlaku,
                    'status' => $dataTrxSph->status,
                    'file_sph' => $dataTrxSph->file_sph ? byteplus_url($dataTrxSph->file_sph) : null,
                    'created_by' => $dataTrxSph->created_by,
                    'created_by_id' => $dataTrxSph->created_by_id,
                    'last_updateby' => $dataTrxSph->last_updateby,
                    'created_at' => $dataTrxSph->created_at,
                    'updated_at' => $dataTrxSph->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting purchase order supplier details', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

}
