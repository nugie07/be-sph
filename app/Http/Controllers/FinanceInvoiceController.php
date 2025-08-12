<?php

// File: app/Http/Controllers/FinanceInvoiceController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\FinanceInvoice;
use App\Models\InvoiceDetail;
use App\Models\MasterCustomer;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthValidator;
use App\Models\User;
use Carbon\Carbon;
use App\Helpers\WorkflowHelper;
use App\Helpers\UserSysLogHelper;

class FinanceInvoiceController extends Controller
{
public function index(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        
        $status = $request->get('status');
        $category = $request->get('category');

            $query = FinanceInvoice::query();

            if ($status) {
                $query->where('status', $status);
            }
            if ($category) {
                $query->where('category', $category);
            }

            // Ambil semua invoices dulu
            $invoices = $query->orderBy('id', 'desc')->get();

            // Tambahkan dn_no di setiap item dan ubah id menjadi idd
            $invoices->map(function ($item) {
                $item->dn_no = DB::table('delivery_note')->where('id', $item->bast_id)->value('dn_no');
                $item->dn_file = DB::table('delivery_note')->where('id', $item->bast_id)->value('file');
                $item->po_file = \App\Models\GoodReceipt::where('po_no', $item->po_no)->value('po_file');

                // Tambahkan kolom payment
                $item->payment_status = $item->payment_status ?? 0;
                $item->payment_date = $item->payment_date ? Carbon::parse($item->payment_date)->format('Y-m-d') : null;
                $item->receipt_file = $item->receipt_file ?? null;
                $item->receipt_number = $item->receipt_number ?? null;

                // Ubah id menjadi idd
                $item->idd = $item->id;
                unset($item->id);

                return $item;
            });

            $summary = [
                'total' => FinanceInvoice::count(),
                'paid' => 'Rp ' . number_format(FinanceInvoice::where('status', 4)->where('payment_status', 1)->sum('total'), 0, ',', '.'),
                'unpaid' => 'Rp ' . number_format(FinanceInvoice::where('status', 4)->where('payment_status', 0)->sum('total'), 0, ',', '.'),
            ];

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'FinanceInvoice', 'index');

            return response()->json([
                'data' => $invoices,
                'summary' => $summary,
            ]);
    }

    /**
     * BARU: Mengambil detail satu invoice beserta item-itemnya.
     */
public function show($id)
    {
        $invoice = FinanceInvoice::with('details')->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        return response()->json($invoice);
    }

    /**
     * BARU: Mengupdate invoice beserta detail itemnya.
     */
public function update(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        
        DB::beginTransaction();
        try {
            $invoice = FinanceInvoice::findOrFail($id);

            // Update data utama invoice
            $invoice->update($request->except('details'));

            // Proses detail item
            $detailIds = [];
            foreach ($request->input('details', []) as $item) {
                if (isset($item['id'])) {
                    // Update item yang sudah ada
                    InvoiceDetail::find($item['id'])->update($item);
                    $detailIds[] = $item['id'];
                } else {
                    // Buat item baru
                    $newItem = $invoice->details()->create($item);
                    $detailIds[] = $newItem->id;
                }
            }

            // Hapus item yang tidak ada di request
            InvoiceDetail::where('invoice_id', $id)->whereNotIn('id', $detailIds)->delete();

            DB::commit();
            
            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'FinanceInvoice', 'update');

            return response()->json(['success' => true, 'message' => 'Invoice berhasil diperbarui.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui invoice: ' . $e->getMessage()], 500);
        }
    }
public function store(Request $request)
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
        $validator = Validator::make($request->all(), [
            'idd' => 'required|integer', // invoice_id dari payload
            'dn_no' => 'required|string|max:255',
            'po_no' => 'required|string|max:255',
            'invoice_no' => 'required|string|unique:finance_invoice,invoice_no',
            'invoice_date' => 'required|date',
            'bill_to' => 'required|string|max:255',
            'ship_to' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
            'fob' => 'required|string|max:255',
            'sent_via' => 'required|string|max:255',
            'sent_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'pbbkb' => 'required|numeric|min:0',
            'pph23' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'terbilang' => 'required|string|max:500',
            'details' => 'required|array|min:1',
            'details.*.nama_item' => 'required|string',
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.harga' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Use values from frontend instead of calculating
            $subtotal = $request->subtotal;
            $tax = $request->tax;
            $pbbkb = $request->pbbkb;
            $pph23 = $request->pph23;
            $total = $request->grand_total;
            $terbilang = $request->terbilang;

            // Cek apakah invoice dengan ID tersebut sudah ada
            $existingInvoice = FinanceInvoice::find($request->idd);

            if ($existingInvoice) {
                // Update invoice yang sudah ada
                $existingInvoice->update([
                    'drs_no' => $request->dn_no,
                    'po_no' => $request->po_no,
                    'invoice_no' => $request->invoice_no,
                    'invoice_date' => $request->invoice_date,
                    'bill_to' => $request->bill_to,
                    'ship_to' => $request->ship_to,
                    'terms' => $request->payment_method,
                    'fob' => $request->fob,
                    'sent_via' => $request->sent_via,
                    'sent_date' => $request->sent_date,
                    'sub_total' => $subtotal,
                    'ppn' => $tax,
                    'pbbkb' => $pbbkb,
                    'pph' => $pph23,
                    'total' => $total,
                    'terbilang' => $terbilang,
                    'status' => 1,
                ]);
                $invoice = $existingInvoice;
            } else {
                // Buat invoice baru dengan ID yang diberikan
            $invoice = FinanceInvoice::create([
                    'id' => $request->idd,
                    'drs_no' => $request->dn_no,
                'po_no' => $request->po_no,
                'invoice_no' => $request->invoice_no,
                'invoice_date' => $request->invoice_date,
                'bill_to' => $request->bill_to,
                    'ship_to' => $request->ship_to,
                    'terms' => $request->payment_method,
                    'fob' => $request->fob,
                    'sent_via' => $request->sent_via,
                    'sent_date' => $request->sent_date,
                    'sub_total' => $subtotal,
                    'ppn' => $tax,
                    'pbbkb' => $pbbkb,
                    'pph' => $pph23,
                'total' => $total,
                    'terbilang' => $terbilang,
                    'status' => 1,
                ]);
            }

                        // Hapus detail yang sudah ada (jika update)
            if ($existingInvoice) {
                $invoice->details()->delete();
            }

            foreach ($request->details as $item) {
                // Debug: Log data yang akan disimpan
                Log::info('Creating invoice detail:', [
                    'nama_item' => $item['nama_item'],
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'total' => $item['qty'] * $item['harga'],
                    'invoice_id' => $request->idd // Gunakan ID dari payload
                ]);

                $detailData = [
                    'invoice_id' => $request->idd, // Gunakan ID dari payload
                    'nama_item' => $item['nama_item'],
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'total' => $item['qty'] * $item['harga'],
                ];


            }
            InvoiceDetail::create($detailData);
                $invoiceId = $request->idd;
                $invoiceNo = $request->invoice_no;
                $timestamp = Carbon::now()->format('Y-m-d H:i:s');
                WorkflowHelper::createWorkflowWithRemark(
                    $invoiceId,
                    'invoice',
                    "User {$fullName} mengajukan approval untuk invoice nomer :  {$invoiceNo} di {$timestamp}",
                    $fullName
                );
            DB::commit();
            
            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'FinanceInvoice', 'store');

            return response()->json(['success' => true, 'message' => 'Invoice berhasil dibuat.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal membuat invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate invoice number berdasarkan customer_po
     * Alur: customer_po -> good_receipts.po_no -> nama_customer -> master_customer -> generate invoice_no
     */
    public function generateInvoiceNo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'po_no' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Step 1: Cari di good_receipt berdasarkan po_no
            $goodReceipt = DB::table('good_receipt')
                ->where('po_no', $request->po_no)
                ->first();

            if (!$goodReceipt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Good Receipt tidak ditemukan dengan PO: ' . $request->po_no
                ], 404);
            }

            // Step 2: Ambil nama_customer dari good_receipts
            $namaCustomer = $goodReceipt->nama_customer;

            if (!$namaCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama customer tidak ditemukan di Good Receipt dengan PO: ' . $request->po_no
                ], 404);
            }

            // Step 3: Cari detail customer di master_customer berdasarkan nama_customer
            $customer = MasterCustomer::where('name', $namaCustomer)
                ->where('status', 1) // Assuming status 1 means active
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan dengan nama: ' . $namaCustomer
                ], 404);
            }

            // Generate invoice number format: {alias}/{bulan_romawi}/{tahun}
            $bulanRomawi = [
                1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
            ];

            $bulanSekarang = date('n'); // 1-12
            $tahunSekarang = date('Y');
            $alias = $customer->alias ?? 'NULL';

            $invoiceNo = $alias . '/' . $bulanRomawi[$bulanSekarang] . '/' . $tahunSekarang;

            return response()->json([
                'success' => true,
                'invoice_no' => $invoiceNo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil detail lengkap invoice beserta detail items berdasarkan ID
     */
    public function getViewDetails($id)
    {
        try {
            // Cari invoice berdasarkan ID
            $invoice = FinanceInvoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice tidak ditemukan dengan ID: ' . $id
                ], 404);
            }

            // Ambil detail items dari invoice
            $details = InvoiceDetail::where('invoice_id', $id)->get();

            // Format response
            $response = [
                'success' => true,
                'data' => [
                    'invoice' => [
                        'id' => $invoice->id,
                        'drs_no' => $invoice->drs_no,
                        'drs_unique' => $invoice->drs_unique,
                        'bast_id' => $invoice->bast_id,
                        'invoice_no' => $invoice->invoice_no,
                        'invoice_date' => $invoice->invoice_date,
                        'terms' => $invoice->terms,
                        'po_no' => $invoice->po_no,
                        'bill_to' => $invoice->bill_to,
                        'ship_to' => $invoice->ship_to,
                        'fob' => $invoice->fob,
                        'sent_date' => $invoice->sent_date,
                        'sent_via' => $invoice->sent_via,
                        'sub_total' => $invoice->sub_total,
                        'ppn' => $invoice->ppn,
                        'pbbkb' => $invoice->pbbkb,
                        'pph' => $invoice->pph,
                        'total' => $invoice->total,
                        'terbilang' => $invoice->terbilang,
                        'status' => $invoice->status,
                        'created_by' => $invoice->created_by,
                        'created_at' => $invoice->created_at,
                        'updated_at' => $invoice->updated_at
                    ],
                    'details' => $details->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'invoice_id' => $detail->invoice_id,
                            'nama_item' => $detail->nama_item,
                            'qty' => $detail->qty,
                            'harga' => $detail->harga,
                            'total' => $detail->total,
                            'created_at' => $detail->created_at,
                            'updated_at' => $detail->updated_at
                        ];
                    })
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error getting invoice view details', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer data berdasarkan PO Number
     */
    public function getCustomerByPo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'po_no' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = DB::table('good_receipt as a')
                ->leftJoin('master_customer as b', 'b.name', '=', 'a.nama_customer')
                ->select('a.po_no', 'b.name', 'b.bill_to', 'b.ship_to')
                ->where('a.po_no', $request->po_no)
                ->first();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan untuk PO: ' . $request->po_no
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'po_no' => $result->po_no,
                    'customer_name' => $result->name,
                    'bill_to' => $result->bill_to,
                    'ship_to' => $result->ship_to
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer by PO', [
                'po_no' => $request->po_no ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List unpaid invoices dengan aging dan summary
     */
    public function listUnpaidInvoices(Request $request)
    {
        try {
            // Get query parameters
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search', '');

            // Build query
            $query = FinanceInvoice::where('status', 4)
                ->where('payment_status', 0);

            // Add search filter
            if (!empty($search)) {
                $query->where('invoice_no', 'LIKE', '%' . $search . '%');
            }

            // Get total count for pagination
            $totalCount = $query->count();

            // Get paginated results
            $unpaidInvoices = $query->orderBy('invoice_date', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $data = [];
            foreach ($unpaidInvoices as $invoice) {
                // Calculate aging
                $invoiceDate = Carbon::parse($invoice->invoice_date);
                $today = Carbon::now();
                $aging = $today->diffInDays($invoiceDate);

                // Determine aging category
                $agingCategory = '';
                if ($aging <= 30) {
                    $agingCategory = '0-30 hari';
                } elseif ($aging <= 60) {
                    $agingCategory = '31-60 hari';
                } elseif ($aging <= 90) {
                    $agingCategory = '61-90 hari';
                } else {
                    $agingCategory = '>90 hari';
                }

                $data[] = [
                    'id' => $invoice->id,
                    'drs_no' => $invoice->drs_no,
                    'drs_unique' => $invoice->drs_unique,
                    'bast_id' => $invoice->bast_id,
                    'invoice_no' => $invoice->invoice_no,
                    'invoice_date' => $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->format('Y-m-d') : null,
                    'terms' => $invoice->terms,
                    'po_no' => $invoice->po_no,
                    'bill_to' => $invoice->bill_to,
                    'ship_to' => $invoice->ship_to,
                    'fob' => $invoice->fob,
                    'sent_date' => $invoice->sent_date ? Carbon::parse($invoice->sent_date)->format('Y-m-d') : null,
                    'sent_via' => $invoice->sent_via,
                    'sub_total' => $invoice->sub_total,
                    'ppn' => $invoice->ppn,
                    'pbbkb' => $invoice->pbbkb,
                    'pph' => $invoice->pph,
                    'total' => $invoice->total,
                    'terbilang' => $invoice->terbilang,
                    'status' => $invoice->status,
                    'created_by' => $invoice->created_by,
                    'created_at' => $invoice->created_at ? Carbon::parse($invoice->created_at)->format('Y-m-d H:i') : null,
                    'updated_at' => $invoice->updated_at ? Carbon::parse($invoice->updated_at)->format('Y-m-d H:i') : null,
                    'file' => $invoice->file,
                    'aging_days' => $aging,
                    'aging_category' => $agingCategory
                ];
            }

            // Calculate summary
            $totalInvoices = FinanceInvoice::where('status', 4)->count();
            $totalUnpaidCount = FinanceInvoice::where('status', 4)->where('payment_status', 0)->count();
            $totalPaidAmount = FinanceInvoice::where('status', 4)->where('payment_status', 1)->sum('total');
            $totalUnpaidAmount = FinanceInvoice::where('status', 4)->where('payment_status', 0)->sum('total');

            // Aging summary
            $agingSummary = [
                '0_30' => FinanceInvoice::where('status', 4)
                    ->where('payment_status', 0)
                    ->whereRaw('DATEDIFF(CURDATE(), invoice_date) <= 30')
                    ->sum('total'),
                '31_60' => FinanceInvoice::where('status', 4)
                    ->where('payment_status', 0)
                    ->whereRaw('DATEDIFF(CURDATE(), invoice_date) > 30 AND DATEDIFF(CURDATE(), invoice_date) <= 60')
                    ->sum('total'),
                '61_90' => FinanceInvoice::where('status', 4)
                    ->where('payment_status', 0)
                    ->whereRaw('DATEDIFF(CURDATE(), invoice_date) > 60 AND DATEDIFF(CURDATE(), invoice_date) <= 90')
                    ->sum('total'),
                'over_90' => FinanceInvoice::where('status', 4)
                    ->where('payment_status', 0)
                    ->whereRaw('DATEDIFF(CURDATE(), invoice_date) > 90')
                    ->sum('total')
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => $page < ceil($totalCount / $perPage),
                    'has_prev_page' => $page > 1
                ],
                'summary' => [
                    'total_invoices' => $totalInvoices,
                    'total_unpaid_count' => $totalUnpaidCount,
                    'total_paid_amount' => 'Rp ' . number_format($totalPaidAmount, 0, ',', '.'),
                    'total_unpaid_amount' => 'Rp ' . number_format($totalUnpaidAmount, 0, ',', '.'),
                    'aging_summary' => [
                        '0_30_days' => 'Rp ' . number_format($agingSummary['0_30'], 0, ',', '.'),
                        '31_60_days' => 'Rp ' . number_format($agingSummary['31_60'], 0, ',', '.'),
                        '61_90_days' => 'Rp ' . number_format($agingSummary['61_90'], 0, ',', '.'),
                        'over_90_days' => 'Rp ' . number_format($agingSummary['over_90'], 0, ',', '.')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting unpaid invoices list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data invoice unpaid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload bukti pembayaran untuk Invoice
     */
    public function uploadReceipt(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Validate request
            $validated = $request->validate([
                'invoice_id' => 'required|integer|exists:finance_invoice,id',
                'receipt_number' => 'required|string|max:100',
                'payment_date' => 'required|date',
                'receipt_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:1024' // max 1MB
            ]);

            // Find Invoice
            $invoice = FinanceInvoice::findOrFail($validated['invoice_id']);

            // Check if Invoice status is approved (status = 4)
            if ($invoice->status != 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice harus dalam status approved untuk dapat upload bukti pembayaran'
                ], 422);
            }

            // Check if payment already exists
            if ($invoice->payment_status == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bukti pembayaran sudah diupload sebelumnya'
                ], 422);
            }

            // Handle file upload
            if ($request->hasFile('receipt_file')) {
                $file = $request->file('receipt_file');

                // Generate unique filename - replace "/" with "_" to avoid folder creation
                $originalName = $file->getClientOriginalName();
                $safeName = str_replace('/', '_', $originalName);
                $fileName = 'receipt_' . str_replace('/', '_', $invoice->invoice_no) . '_' . time() . '_' . $safeName;
                $filePath = 'invoices_receipt/' . $fileName;

                // Check if storage disk exists
                if (!Storage::disk('idcloudhost')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Storage disk idcloudhost not configured'
                    ], 500);
                }

                // Upload file to idcloudhost
                $uploaded = Storage::disk('idcloudhost')->put($filePath, file_get_contents($file));

                if (!$uploaded) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal upload file ke storage'
                    ], 500);
                }

                // Generate full public URL
                $fullUrl = 'https://is3.cloudhost.id/bensinkustorage/' . $filePath;

                // Update Invoice with payment information
                $invoice->payment_date = $validated['payment_date'];
                $invoice->receipt_number = $validated['receipt_number'];
                $invoice->receipt_file = $fullUrl;
                $invoice->payment_status = 1; // Set as paid
                $invoice->save();

                Log::info('Invoice payment receipt uploaded successfully', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'payment_date' => $validated['payment_date'],
                    'receipt_number' => $validated['receipt_number'],
                    'file_path' => $filePath,
                    'file_size' => Storage::disk('idcloudhost')->size($filePath)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Bukti pembayaran berhasil diupload',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
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
            Log::error('Error uploading invoice payment receipt', [
                'invoice_id' => $request->invoice_id ?? 'unknown',
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
}
