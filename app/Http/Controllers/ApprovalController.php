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
use App\Helpers\WorkflowHelper;

class ApprovalController extends Controller
{
    /**
     * List approval untuk dashboard
     */
public function list(Request $request)
    {
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
        try {
                                    // SPH Approval Details
            $sphData = DataTrxSph::where('status', 1)
                ->select('id', 'tipe_sph', 'kode_sph', 'comp_name', 'product', 'price_liter', 'ppn', 'pbbkb', 'total_price', 'pay_method', 'created_at', 'created_by')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'tipe_sph' => $item->tipe_sph ?? '-',
                        'no_sph' => $item->kode_sph,
                        'nama_perusahaan' => $item->comp_name ?? 'Unknown Customer',
                        'produk_dibeli' => $item->product ?? 'Solar HSD B35',
                        'harga_per_liter' => $item->price_liter ? number_format($item->price_liter, 0, ',', '.') : '15.000',
                        'ppn' => $item->ppn ? number_format($item->ppn, 0, ',', '.') : '825.000',
                        'pbbkb' => $item->pbbkb ? number_format($item->pbbkb, 0, ',', '.') : '562.500',
                        'total_harga' => $item->total_price ? number_format($item->total_price, 0, ',', '.') : '0',
                        'metode_pembayaran' => $item->pay_method ?? 'TOP 30 Hari',
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : '',
                        'created_by' => $item->created_by
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
                        'trxId' => $item->drs_unique ?? '-',
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
                        'trxId' => $item->drs_unique ?? '-',
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

    public function generateInvoicePDF($invoiceId)
    {
        try {
            $invoice = FinanceInvoice::findOrFail($invoiceId);

            // Get invoice details
            $details = InvoiceDetail::where('invoice_id', $invoiceId)->get();

            // Determine template
            $template = 'pdf.invoice_mmtei';

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
                'ship_to' => $invoice->ship_to,
                'sub_total' => $invoice->sub_total ?? 0,
                'diskon' => $invoice->diskon ?? 0,
                'ppn' => $invoice->ppn ?? 0,
                'pbbkb' => $invoice->pbbkb ?? 0,
                'pph' => $invoice->pph ?? 0,
                'total' => $invoice->total ?? 0,
                'terbilang' => $invoice->terbilang ?? '-'
            ];

            Log::info('Invoice data prepared', ['invoice_data' => $invoiceData]);

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
}
