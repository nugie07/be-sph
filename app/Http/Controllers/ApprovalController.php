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
use App\Models\PurchaseOrder;
use App\Models\FinanceInvoice;
use App\Models\InvoiceDetail;
use App\Models\WorkflowEngine;
use App\Helpers\WorkflowHelper;
use App\Helpers\UserSysLogHelper;

class ApprovalController extends Controller
{
    /**
     * List approval untuk dashboard
     */
public function list(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // SPH Approval
            $sphApproval = DataTrxSph::where('status', 1)->count();

                        // Supplier PO Approval
            $supplierApproval = PurchaseOrder::where('status', 1)
                ->where('category', 1)
                ->count();

            // Transporter PO Approval
            $transporterApproval = PurchaseOrder::where('status', 1)
                ->where('category', 2)
                ->count();

            // Invoice Approval
            $invoiceApproval = FinanceInvoice::where('status', 1)->count();

            $data = [
                [
                    'no' => 1,
                    'tipe' => 'SPH',
                    'approval' => $sphApproval
                ],
                [
                    'no' => 2,
                    'tipe' => 'Supplier',
                    'approval' => $supplierApproval
                ],
                [
                    'no' => 3,
                    'tipe' => 'Transportir',
                    'approval' => $transporterApproval
                ],
                [
                    'no' => 4,
                    'tipe' => 'Invoice',
                    'approval' => $invoiceApproval
                ]
            ];

            // Hitung total approval
            $totalApproval = $sphApproval + $supplierApproval + $transporterApproval + $invoiceApproval;

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Approval', 'list');

            return response()->json([
                'success' => true,
                'data' => $data,
                'summary' => [
                    'total_approval' => $totalApproval,
                    'sph_count' => $sphApproval,
                    'supplier_count' => $supplierApproval,
                    'transporter_count' => $transporterApproval,
                    'invoice_count' => $invoiceApproval
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting approval list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data approval: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * Mengambil data detail approval untuk setiap tipe
     */
public function getApprovalDetails(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
                                    // SPH Approval Details
            $sphItems = DataTrxSph::where('status', 1)
                ->select('id', 'tipe_sph', 'kode_sph', 'comp_name', 'product', 'price_liter', 'ppn', 'pbbkb', 'total_price', 'pay_method', 'susut', 'note_berlaku', 'oat', 'ppn_oat', 'oat_lokasi', 'created_at', 'created_by', 'template_id')
                ->orderBy('created_at', 'desc')
                ->get();

            // Prefetch form from sph_template for all template_ids present
            $templateIds = $sphItems->pluck('template_id')->filter()->unique();
            $templateFormById = collect();
            if ($templateIds->isNotEmpty()) {
                $templateFormById = DB::table('sph_template')
                    ->whereIn('id', $templateIds)
                    ->pluck('form', 'id');
            }

            // Prefetch sph_details grouped by sph_id to avoid N+1
            $sphIds = $sphItems->pluck('id');
            $detailsBySphId = collect();
            if ($sphIds->isNotEmpty()) {
                $detailsBySphId = DB::table('sph_details')
                    ->whereIn('sph_id', $sphIds)
                    ->get()
                    ->groupBy('sph_id');
            }

            // Prefetch temp_sph grouped by sph_id to avoid N+1
            $tempSphBySphId = collect();
            if ($sphIds->isNotEmpty()) {
                $tempSphBySphId = DB::table('temp_sph')
                    ->whereIn('sph_id', $sphIds)
                    ->get()
                    ->keyBy('sph_id');
            }

            $sphData = $sphItems->map(function ($item) use ($templateFormById, $detailsBySphId, $tempSphBySphId) {
                    $tempSph = $tempSphBySphId->get($item->id);
                    return [
                        'id' => $item->id,
                        'tipe_sph' => $item->tipe_sph ?? '-',
                        'no_sph' => $item->kode_sph,
                        'nama_perusahaan' => $item->comp_name ?? 'Unknown Customer',
                        'produk_dibeli' => $item->product ?? 'Solar HSD B35',
                        'harga_per_liter' => $item->price_liter ? number_format($item->price_liter, 0, ',', '.') : '-',
                        'ppn' => $item->ppn ? number_format($item->ppn, 0, ',', '.') : '-',
                        'pbbkb' => $item->pbbkb ? number_format($item->pbbkb, 0, ',', '.') : '-',
                        'total_harga' => $item->total_price ? number_format($item->total_price, 0, ',', '.') : '-',
                        'metode_pembayaran' => $item->pay_method ?? 'TOP 30 Hari',
                        'pay_method' => $item->pay_method,
                        'susut' => $item->susut,
                        'note_berlaku' => $item->note_berlaku,
                        'oat' => $item->oat,
                        'ppn_oat' => $item->ppn_oat,
                        'oat_lokasi' => $item->oat_lokasi,
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '',
                        'created_by' => $item->created_by,
                        'template_id' => $item->template_id,
                        'template_form' => $templateFormById->get($item->template_id) ?? $templateFormById->get((string) $item->template_id) ?? null,
                        'details' => ($detailsBySphId->get($item->id) ?? collect())->values(),
                        'temp_file' => $tempSph ? ($tempSph->temp_link ?? null) : null,
                    ];
                });

            // Supplier PO Approval Details (category = 1)
            $supplierData = PurchaseOrder::where('status', 1)
                ->where('category', 1)
                ->select('id', 'drs_no', 'drs_unique', 'customer_po', 'vendor_name', 'vendor_po', 'tgl_po', 'nama', 'alamat', 'contact', 'fob', 'term', 'shipped_via', 'loading_point', 'delivery_to', 'transport', 'harga', 'qty', 'sub_total', 'ppn', 'pbbkb', 'bph', 'total', 'terbilang', 'description', 'additional_notes', 'created_at', 'created_by')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'trxId' => $item->id ?? '-',
                        'drs_no' => $item->drs_no ?? '-',
                        'customer_po' => $item->customer_po ?? '-',
                        'vendor_name' => $item->vendor_name ?? '-',
                        'vendor_po' => $item->vendor_po ?? '-',
                        'tgl_po' => $item->tgl_po ?? '-',
                        'nama' => $item->nama ?? '-',
                        'alamat' => $item->alamat ?? '-',
                        'contact' => $item->contact ?? '-',
                        'fob' => $item->fob ?? '-',
                        'term' => $item->term ?? '-',
                        'shipped_via' => $item->shipped_via ?? '-',
                        'loading_point' => $item->loading_point ?? '-',
                        'delivery_to' => $item->delivery_to ?? '-',
                        'transport' => $item->transport ? number_format($item->transport, 0, ',', '.') : '0',
                        'harga' => $item->harga ? number_format($item->harga, 0, ',', '.') : '0',
                        'qty' => $item->qty ? number_format($item->qty, 0, ',', '.') : '0',
                        'sub_total' => $item->sub_total ? number_format($item->sub_total, 0, ',', '.') : '0',
                        'ppn' => $item->ppn ? number_format($item->ppn, 0, ',', '.') : '0',
                        'pbbkb' => $item->pbbkb ? number_format($item->pbbkb, 0, ',', '.') : '0',
                        'bph' => $item->bph ? number_format($item->bph, 0, ',', '.') : '0',
                        'total' => $item->total ? number_format($item->total, 0, ',', '.') : '0',
                        'terbilang' => $item->terbilang ?? '-',
                        'description' => $item->description ?? '-',
                        'additional_notes' => $item->additional_notes ?? '-',
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '',
                        'created_by' => $item->created_by
                    ];
                });

            // Transporter PO Approval Details (category = 2)
            $transporterData = PurchaseOrder::where('status', 1)
                ->where('category', 2)
                ->select('id', 'drs_no', 'drs_unique', 'customer_po', 'vendor_name', 'vendor_po', 'tgl_po', 'nama', 'alamat', 'contact', 'fob', 'term', 'delivery_to', 'portal', 'harga', 'qty', 'sub_total', 'total', 'terbilang', 'created_at', 'created_by')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'trxId' => $item->id ?? '-',
                        'drs_no' => $item->drs_no ?? '-',
                        'customer_po' => $item->customer_po ?? '-',
                        'vendor_name' => $item->vendor_name ?? '-',
                        'vendor_po' => $item->vendor_po ?? '-',
                        'tgl_po' => $item->tgl_po ?? '-',
                        'nama' => $item->nama ?? '-',
                        'alamat' => $item->alamat ?? '-',
                        'contact' => $item->contact ?? '-',
                        'fob' => $item->fob ?? '-',
                        'term' => $item->term ?? '-',
                        'delivery_to' => $item->delivery_to ?? '-',
                        'portal' => $item->portal ? number_format($item->portal, 0, ',', '.') : '0',
                        'harga' => $item->harga ? number_format($item->harga, 0, ',', '.') : '0',
                        'qty' => $item->qty ? number_format($item->qty, 0, ',', '.') : '0',
                        'sub_total' => $item->sub_total ? number_format($item->sub_total, 0, ',', '.') : '0',
                        'total' => $item->total ? number_format($item->total, 0, ',', '.') : '0',
                        'terbilang' => $item->terbilang ?? '-',
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '',
                        'created_by' => $item->created_by
                    ];
                });

            // Invoice Approval Details
            $invoiceData = FinanceInvoice::where('status', 1)
                ->select('id', 'invoice_no', 'po_no', 'bill_to', 'total', 'sub_total', 'drs_unique', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'trxId' => $item->drs_unique ?? '-',
                        'nomer_invoice' => $item->invoice_no,
                        'nomer_po' => $item->po_no,
                        'nama_customer' => $item->bill_to,
                        'nilai_po' => $item->sub_total ? number_format($item->sub_total, 0, ',', '.') : '0',
                        'nilai_invoice' => $item->total ? number_format($item->total, 0, ',', '.') : '0',
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : ''
                    ];
                });

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Approval', 'getApprovalDetails');

            return response()->json([
                'success' => true,
                'data' => [
                    'sph' => [
                        'count' => $sphData->count(),
                        'items' => $sphData
                    ],
                    'supplier' => [
                        'count' => $supplierData->count(),
                        'items' => $supplierData
                    ],
                    'transporter' => [
                        'count' => $transporterData->count(),
                        'items' => $transporterData
                    ],
                    'invoice' => [
                        'count' => $invoiceData->count(),
                        'items' => $invoiceData
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting approval details', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail approval: ' . $e->getMessage()
            ], 500);
        }
    }

public function verifyInvoice(Request $request, $trx_id)
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
            'decision'  => 'required|in:approve,revisi,reject',
            'remark' => 'required|string',
        ]);
        $invoice = FinanceInvoice::findOrFail($trx_id);
        $invoiceId = $invoice->id;
        // Start transaction
        DB::beginTransaction();
        try {
            // 1. Check active workflow_record
            $wf = DB::table('workflow_record')
                ->where('trx_id', $invoiceId)
                ->where('tipe_trx', 'invoice')
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
            if ($validated['decision'] === 'approve') {
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
                    $invoice->status = 4;
                    $invoice->save();

                    // Generate PDF when approved and save URL to database
                    try {
                        $pdfResponse = $this->generateInvoicePDF($invoice->id);
                        if (is_a($pdfResponse, \Illuminate\Http\JsonResponse::class)) {
                            $pdfData = $pdfResponse->getData(true);
                            if (!empty($pdfData['pdf_url'])) {
                                $invoice->file = $pdfData['pdf_url'];
                                $invoice->save();
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Auto generate PDF after final approval failed', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
           } elseif ($validated['decision'] === 'revisi') {
                // Mark workflow as revisi
                DB::table('workflow_record')
                    ->where('id', $wf->id)
                    ->update(['wf_status' => 2, 'updated_at' => now()]);
                // Set PO status to 'revisi' (status code 2)
                $invoice->status = 2;
                $invoice->save();
            } else {
                // Reject
                $invoice->status = 3;
                $invoice->save();
            }

            // 4. Insert workflow_remark with correct action word
            switch ($validated['decision']) {
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
            $msg = "User {$fullName} {$action} Invoice {$invoice->invoice_no}: {$validated['remark']}";

            WorkflowHelper::createWorkflowWithRemark(
                $invoiceId,
                'invoice',
                $msg,
                $fullName
            );

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Approval', 'verifyInvoice', 'Verify Invoice: ' . $validated['decision']);

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

    /**
     * Generate Invoice PDF via API endpoint
     * Endpoint: GET /approval/generate-invoice-pdf/{invoiceId}
     */
    public function generateInvoicePDFApi(Request $request, $invoiceId)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        return $this->generateInvoicePDF($invoiceId);
    }

    /**
     * Generate Invoice PDF (internal function)
     * Can be called directly or via API endpoint
     */
    public function generateInvoicePDF($invoiceId)
    {
        try {
            $invoice = FinanceInvoice::findOrFail($invoiceId);

            // Get invoice details
            $details = InvoiceDetail::where('invoice_id', $invoiceId)->get();

            // Get dn_no from invoice
            $dnNo = $invoice->dn_no ?? '';

            // Determine template based on dn_no
            // If first 4 characters of dn_no is "IASE", use invoice_iase template
            // Otherwise, use invoice_mmtei template
            $template = 'pdf.invoice_mmtei'; // default template
            if (!empty($dnNo) && strlen($dnNo) >= 4) {
                $firstFourChars = strtoupper(substr($dnNo, 0, 4));
                if ($firstFourChars === 'IASE') {
                    $template = 'pdf.invoice_iase';
                }
            }

            // Validate template exists before proceeding
            if (!view()->exists($template)) {
                Log::error('Template not found', ['template' => $template, 'dn_no' => $dnNo]);
                return response()->json([
                    'success' => false,
                    'error' => 'Template not found: ' . $template
                ], 500);
            }

            // Prepare data for PDF template
            $invoiceData = [
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->format('d/m/Y') : '',
                'po_no' => $invoice->po_no,
                'drs_no' => $invoice->drs_no,
                'terms' => $invoice->terms,
                'fob' => $invoice->fob,
                'sent_via' => $invoice->sent_via,
                'sent_date' => $invoice->sent_date ? Carbon::parse($invoice->sent_date)->format('d/m/Y') : '',
                'bill_to' => $invoice->bill_to,
                'bill_to_address' => $invoice->bill_to_address ?? '',
                'ship_to' => $invoice->ship_to,
                'ship_to_address' => $invoice->ship_to_address ?? '',
                'sub_total' => $invoice->sub_total ?? 0,
                'diskon' => $invoice->diskon ?? 0,
                'ppn' => $invoice->ppn ?? 0,
                'pbbkb' => $invoice->pbbkb ?? 0,
                'pph' => $invoice->pph ?? 0,
                'oat' => $invoice->oat ?? 0,
                'transport' => $invoice->transport ?? 0,
                'total' => $invoice->total ?? 0,
                'terbilang' => $invoice->terbilang ?? '-',
                'type' => $invoice->type ?? null
            ];

            Log::info('Invoice data prepared', [
                'invoice_data' => $invoiceData,
                'dn_no' => $dnNo,
                'template_selected' => $template
            ]);

            // Generate PDF
            Log::info('Starting PDF generation with template', ['template' => $template]);

            $pdf = Pdf::setOptions([
                'enable_remote' => true,
                'isRemoteEnabled' => true,
                'dpi' => 110,
                'defaultFont' => 'sans-serif'
            ])
            ->loadView($template, [
                'invoice' => (object) $invoiceData,
                'details' => $details
            ])
            ->setPaper('a4', 'portrait');

            Log::info('PDF object created successfully');

            // Generate filename and save to storage
            $pdfFileName = 'invoice_' . $invoice->invoice_no . '_' . time() . '.pdf';
            $pdfPath = 'invoices/' . $pdfFileName;

            Log::info('Attempting to save PDF to storage', [
                'pdf_path' => $pdfPath,
                'storage_disk' => 'idcloudhost'
            ]);

            // Check if storage disk exists
            if (!Storage::disk('idcloudhost')) {
                Log::error('Storage disk idcloudhost not found');
                return response()->json([
                    'success' => false,
                    'error' => 'Storage disk idcloudhost not configured'
                ], 500);
            }

            $pdfContent = $pdf->output();
            Log::info('PDF content generated', ['content_size' => strlen($pdfContent)]);

            $saved = Storage::disk('idcloudhost')->put($pdfPath, $pdfContent);

            if (!$saved) {
                Log::error('Failed to save PDF to storage', ['pdf_path' => $pdfPath]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to save PDF to storage'
                ], 500);
            }

            Log::info('PDF saved to storage successfully', ['pdf_path' => $pdfPath]);

            // Generate full public URL
            $fullUrl = 'https://is3.cloudhost.id/bensinkustorage/' . $pdfPath;

            Log::info('Invoice PDF Generated Successfully', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'template' => $template,
                'pdf_path' => $pdfPath,
                'full_url' => $fullUrl,
                'file_size' => Storage::disk('idcloudhost')->size($pdfPath)
            ]);

            return response()->json([
                'success' => true,
                'invoice_no' => $invoice->invoice_no,
                'template_used' => $template,
                'pdf_url' => $fullUrl,
                'pdf_path' => $pdfPath
            ]);

        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'invoice_id' => $invoiceId,
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
     * List workflow engine dengan pagination dan search
     */
    public function listEngine(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search', '');

            // Base query dengan join ke roles untuk mendapatkan nama role
            $query = WorkflowEngine::query()
                ->leftJoin('roles as first_role', 'workflow_engine.first_appr', '=', 'first_role.id')
                ->leftJoin('roles as second_role', 'workflow_engine.second_appr', '=', 'second_role.id')
                ->select([
                    'workflow_engine.id',
                    'workflow_engine.tipe_trx',
                    'workflow_engine.first_appr',
                    'workflow_engine.second_appr',
                    'workflow_engine.timestamp',
                    'first_role.name as first_appr_name',
                    'second_role.name as second_appr_name'
                ]);

            // Search filter by tipe_trx
            if (!empty($search)) {
                $query->where('workflow_engine.tipe_trx', 'LIKE', '%' . $search . '%');
            }

            // Hitung total records
            $totalCount = $query->count();

            // Ambil data dengan pagination
            $workflowEngine = $query->orderBy('workflow_engine.tipe_trx', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Approval', 'listEngine');

            $response = [
                'success' => true,
                'data' => $workflowEngine,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => $page < ceil($totalCount / $perPage),
                    'has_prev_page' => $page > 1
                ]
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'Approval',
                'List workflow engine',
                'listEngine.ApprovalController',
                $request->all(),
                $response
            );

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error getting workflow engine list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengambil data workflow engine: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'Approval',
                'Error getting workflow engine list',
                'listEngine.ApprovalController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Update workflow engine berdasarkan ID
     */
    public function updateEngine(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        $fullName = "{$user->first_name} {$user->last_name}";

        try {
            // Validasi input
            $request->validate([
                'tipe_trx' => 'required|string|max:100',
                'first_appr' => 'required|integer|exists:roles,id',
                'second_appr' => 'nullable|integer|exists:roles,id'
            ]);

            DB::beginTransaction();

            // Cari workflow engine yang akan diupdate
            $workflowEngine = WorkflowEngine::findOrFail($id);

            // Cek apakah tipe_trx sudah ada (kecuali untuk record yang sama)
            $existingWorkflow = WorkflowEngine::where('tipe_trx', $request->tipe_trx)
                ->where('id', '!=', $id)
                ->first();

            if ($existingWorkflow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe transaksi sudah ada'
                ], 400);
            }

            // Update workflow engine
            $workflowEngine->update([
                'tipe_trx' => $request->tipe_trx,
                'first_appr' => $request->first_appr,
                'second_appr' => $request->second_appr,
                'timestamp' => now()
            ]);

            // Ambil nama role untuk response
            $firstRole = Role::find($request->first_appr);
            $secondRole = $request->second_appr ? Role::find($request->second_appr) : null;

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Approval', 'updateEngine');

            $response = [
                'success' => true,
                'message' => 'Workflow engine berhasil diupdate',
                'data' => [
                    'id' => $workflowEngine->id,
                    'tipe_trx' => $workflowEngine->tipe_trx,
                    'first_appr' => $workflowEngine->first_appr,
                    'first_appr_name' => $firstRole ? $firstRole->name : null,
                    'second_appr' => $workflowEngine->second_appr,
                    'second_appr_name' => $secondRole ? $secondRole->name : null,
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'updated_by' => $fullName
                ]
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'Approval',
                'Update workflow engine',
                'updateEngine.ApprovalController',
                $request->all(),
                $response
            );

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating workflow engine', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
                'workflow_id' => $id
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengupdate workflow engine: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'Approval',
                'Error updating workflow engine',
                'updateEngine.ApprovalController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Get roles untuk dropdown
     */
    public function getRolesForDropdown()
    {
        try {
            $roles = Role::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting roles for dropdown', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data roles: ' . $e->getMessage()
            ], 500);
        }
    }
}
