<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\FinanceInvoice;
use App\Helpers\UserSysLogHelper;
use App\Helpers\AuthValidator;

class DeliveryNoteController extends Controller
{
    // List & filter
public function index(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $date = $request->get('created_at');
        $query = DeliveryNote::query();
        if ($date) {
            $query->whereDate('created_at', $date);
        }
        $data = $query->orderBy('id','desc')->get();
        $data = $data->map(function ($item) {
            $item->vendor_po = DB::table('purchase_order')->where('drs_unique', $item->drs_unique)->value('vendor_po');
            $item->fob = DB::table('purchase_order')->where('drs_unique', $item->drs_unique)->value('fob');
            $item->shipped_via = DB::table('purchase_order')->where('drs_unique', $item->drs_unique)->value('shipped_via');
            return $item;
        });
        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'DeliveryNote', 'index');

        return response()->json(['data' => $data]);
    }

    // Ambil detail satu baris
public function show($id)
    {
        $row = DeliveryNote::findOrFail($id);
        return response()->json($row);
    }

    // Simpan data baru
    public function store(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        
        DB::beginTransaction();
        try {
            $data = $request->all();
            $note = DeliveryNote::create($data);
            DB::commit();
            
            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'DeliveryNote', 'store');

            // Generate PDF setelah data berhasil disimpan
            $pdfResult = null;
            try {
                $pdfResult = $this->generateDeliveryNotePdf($note->id);
                
                // Jika PDF berhasil di-generate, update kolom file di delivery_note
                if ($pdfResult && isset($pdfResult['success']) && $pdfResult['success']) {
                    $note->dn_file = $pdfResult['pdf_url'];
                    $note->save();
                    
                    Log::info('Delivery Note PDF generated successfully', [
                        'delivery_note_id' => $note->id,
                        'dn_no' => $note->dn_no,
                        'pdf_url' => $pdfResult['pdf_url']
                    ]);
                }
            } catch (\Exception $pdfException) {
                // Log error PDF generation tapi tidak rollback data
                Log::error('Failed to generate Delivery Note PDF', [
                    'delivery_note_id' => $note->id,
                    'dn_no' => $note->dn_no ?? 'N/A',
                    'error' => $pdfException->getMessage(),
                    'file' => $pdfException->getFile(),
                    'line' => $pdfException->getLine()
                ]);
            }

            return response()->json([
                'success' => true, 
                'data' => $note,
                'pdf_generated' => $pdfResult['success'] ?? false,
                'pdf_url' => $pdfResult['pdf_url'] ?? null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan Delivery Note: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate Delivery Note PDF
     * 
     * @param int $id - ID dari delivery_note
     * @return array
     */
    public function generateDeliveryNotePdf($id)
    {
        try {
            // Ambil data delivery note
            $note = DeliveryNote::find($id);
            
            if (!$note) {
                Log::error('Delivery Note not found for PDF generation', ['id' => $id]);
                return [
                    'success' => false,
                    'error' => 'Delivery Note tidak ditemukan'
                ];
            }

            Log::info('Starting Delivery Note PDF generation', [
                'delivery_note_id' => $note->id,
                'dn_no' => $note->dn_no ?? 'N/A'
            ]);

            // Siapkan data untuk template
            // Mapping dari delivery_note ke variabel yang dibutuhkan template
            $missingFields = [];
            
            // Check required fields dan log jika kosong
            $requiredFields = [
                'dn_no' => 'DN Number',
                'customer_po' => 'Customer PO',
                'customer_name' => 'Customer Name',
                'delivery_to' => 'Delivery To',
            ];

            foreach ($requiredFields as $field => $label) {
                if (empty($note->$field)) {
                    $missingFields[] = $label;
                    Log::warning("Missing field for DN PDF: {$label}", [
                        'delivery_note_id' => $note->id,
                        'field' => $field
                    ]);
                }
            }

            if (!empty($missingFields)) {
                Log::warning('Delivery Note PDF generated with missing fields', [
                    'delivery_note_id' => $note->id,
                    'missing_fields' => $missingFields
                ]);
            }

            // Generate DN No dengan format: DN-{dn_no}/{alias}-{sph_type}/{bulan_romawi}/{tahun}
            // Contoh: DN-IASE115/GMK-IASE/VIII/2025
            $dnNoFormatted = $note->dn_no ?? '';
            if (!empty($note->dn_no)) {
                // Query untuk mendapatkan alias dan tipe_sph
                // SELECT DISTINCT mc.alias, dts.tipe_sph, mc.name
                // FROM master_customer mc
                // LEFT JOIN good_receipt gr ON gr.nama_customer = mc.name
                // LEFT JOIN delivery_note dn ON dn.customer_po = gr.po_no
                // LEFT JOIN data_trx_sph dts ON dts.kode_sph = gr.kode_sph
                // WHERE dn.customer_po = {customer_po}
                $customerAlias = '';
                $sphType = 'MMTEI'; // Default
                
                if (!empty($note->customer_po)) {
                    $aliasData = DB::table('master_customer as mc')
                        ->leftJoin('good_receipt as gr', 'gr.nama_customer', '=', 'mc.name')
                        ->leftJoin('delivery_note as dn', 'dn.customer_po', '=', 'gr.po_no')
                        ->leftJoin('data_trx_sph as dts', 'dts.kode_sph', '=', 'gr.kode_sph')
                        ->where('dn.customer_po', $note->customer_po)
                        ->select('mc.alias', 'dts.tipe_sph', 'mc.name')
                        ->distinct()
                        ->first();
                    
                    if ($aliasData) {
                        $customerAlias = $aliasData->alias ?? '';
                        $sphType = $aliasData->tipe_sph ?? 'MMTEI';
                    }
                }
                
                // Konversi bulan ke romawi
                $bulanRomawi = [
                    1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
                    5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
                    9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
                ];
                $bulanSekarang = (int) Carbon::now()->format('m');
                $bulanRomawiStr = $bulanRomawi[$bulanSekarang] ?? '';
                
                // Tahun berjalan
                $tahunSekarang = Carbon::now()->format('Y');
                
                // Format: DN-{dn_no}/{alias}-{sph_type}/{bulan_romawi}/{tahun}
                $dnNoFormatted = 'DN-' . $note->dn_no;
                if (!empty($customerAlias)) {
                    $dnNoFormatted .= '/' . $customerAlias . '-' . $sphType;
                }
                $dnNoFormatted .= '/' . $bulanRomawiStr . '/' . $tahunSekarang;
                
                Log::info('DN No formatted', [
                    'original_dn_no' => $note->dn_no,
                    'customer_po' => $note->customer_po,
                    'customer_alias' => $customerAlias,
                    'tipe_sph' => $sphType,
                    'bulan_romawi' => $bulanRomawiStr,
                    'tahun' => $tahunSekarang,
                    'formatted_dn_no' => $dnNoFormatted
                ]);
            }

            // Buat objek untuk template
            // Mapping sesuai dengan nama kolom di database delivery_note
            $dn = (object) [
                'sph_type' => $note->sph_type ?? 'MMTEI', // Default MMTEI jika tidak ada
                'no' => $dnNoFormatted,
                'po_from' => $note->customer_name ?? '',
                'po_date' => $note->po_date ? Carbon::parse($note->po_date)->format('d/m/Y') : '',
                'po_number' => $note->customer_po ?? '',
                'arrival_date' => $note->arrival_date ? Carbon::parse($note->arrival_date)->locale('id')->translatedFormat('l, d/m/Y') : '', // Req Arrival dengan nama hari (contoh: Rabu, 21/01/2026)
                'consignee' => $note->consignee ?? $note->customer_name ?? '', // Gunakan consignee dari DB, fallback ke customer_name
                'delivery_to' => $note->delivery_to ?? '',
                'address' => $note->address ?? '',
                'quantity' => $note->qty ?? '',
                'units' => $note->unit ?? 'Liter', // Kolom DB: unit (bukan units)
                'description' => $note->description ?? 'Solar HSD B35',
                // Field segel - langsung dari DB
                'segel_atas' => $note->segel_atas ?? '',
                'segel_bawah' => $note->segel_bawah ?? '',
                // Field catatan pengiriman - langsung dari DB
                'lo' => $note->lo ?? '', // No. LO
                'so' => $note->so ?? '', // No. SO
                'nopol' => $note->nopol ?? '', // No. Polisi
                'driver_name' => $note->driver_name ?? '',
                'transportir' => $note->transportir ?? '',
                'terra' => $note->terra ?? '',
                'berat_jenis' => $note->berat_jenis ?? '',
                'temperature' => $note->temperature ?? '',
                // Field catatan pembongkaran
                'tgl_bongkar' => $note->tgl_bongkar ? Carbon::parse($note->tgl_bongkar)->format('d/m/Y') : '',
                'jam_mulai' => $note->jam_mulai ?? '',
                'jam_akhir' => $note->jam_akhir ?? '',
                'meter_awal' => $note->meter_awal ?? '',
                'meter_akhir' => $note->meter_akhir ?? '',
                'tinggi_sounding' => $note->tinggi_sounding ?? '',
                'jenis_suhu' => $note->jenis_suhu ?? '',
                'volume_diterima' => $note->volume_diterima ?? '',
            ];
            
            // Settings untuk header template
            $settings = [
                'header_komoditi_produk_mmtei' => 'Solar HSD B35 MFO, Pertamax, Pertamina Turbo dan Dexlite',
                'Sub_Title_4' => 'Email: info@mmtei.com | Telp: 0811 - 8888 - 2221',
            ];

            $date = Carbon::now()->format('d/m/Y');

            Log::info('Delivery Note PDF data prepared', [
                'delivery_note_id' => $note->id,
                'dn_data' => (array) $dn
            ]);

            // Tentukan template berdasarkan dn_no
            // Jika dn_no mengandung "IASE" gunakan template dniase, selain itu gunakan dnmmtei
            $dnNo = $note->dn_no ?? '';
            if (stripos($dnNo, 'IASE') !== false) {
                $template = 'pdf.dniase_pdf_template';
                Log::info('Using IASE template for DN PDF', [
                    'delivery_note_id' => $note->id,
                    'dn_no' => $dnNo,
                    'template' => $template
                ]);
            } else {
                $template = 'pdf.dnmmtei_pdf_template';
                Log::info('Using MMTEI template for DN PDF', [
                    'delivery_note_id' => $note->id,
                    'dn_no' => $dnNo,
                    'template' => $template
                ]);
            }

            // Check if template exists
            if (!view()->exists($template)) {
                Log::error('Delivery Note PDF template not found', ['template' => $template]);
                return [
                    'success' => false,
                    'error' => 'Template PDF tidak ditemukan: ' . $template
                ];
            }

            // Generate PDF
            $pdf = Pdf::setOptions([
                'enable_remote' => true,
                'isRemoteEnabled' => true,
                'dpi' => 110,
                'defaultFont' => 'sans-serif'
            ])
            ->loadView($template, [
                'dn' => $dn,
                'date' => $date,
                'settings' => $settings
            ])
            ->setPaper('a4', 'portrait');

            // Generate filename
            $fileName = 'dn_' . ($note->dn_no ? str_replace(['/', '\\', ' '], '_', $note->dn_no) : $note->id) . '_' . time() . '.pdf';
            $filePath = 'delivery_notes/' . $fileName;

            Log::info('Attempting to save Delivery Note PDF to storage', [
                'delivery_note_id' => $note->id,
                'file_path' => $filePath
            ]);

            // Check if storage disk exists
            if (!Storage::disk('byteplus')) {
                Log::error('Storage disk byteplus not found for DN PDF');
                return [
                    'success' => false,
                    'error' => 'Storage disk byteplus tidak dikonfigurasi'
                ];
            }

            // Save PDF to storage
            $pdfContent = $pdf->output();
            $saved = Storage::disk('byteplus')->put($filePath, $pdfContent);

            if (!$saved) {
                Log::error('Failed to save Delivery Note PDF to storage', [
                    'delivery_note_id' => $note->id,
                    'file_path' => $filePath
                ]);
                return [
                    'success' => false,
                    'error' => 'Gagal menyimpan PDF ke storage'
                ];
            }

            // Generate full public URL
            $fullUrl = byteplus_url($filePath);

            Log::info('Delivery Note PDF generated and saved successfully', [
                'delivery_note_id' => $note->id,
                'dn_no' => $note->dn_no,
                'file_path' => $filePath,
                'pdf_url' => $fullUrl,
                'file_size' => Storage::disk('byteplus')->size($filePath)
            ]);

            return [
                'success' => true,
                'pdf_url' => $fullUrl,
                'pdf_path' => $filePath,
                'missing_fields' => $missingFields
            ];

        } catch (\Exception $e) {
            Log::error('Exception during Delivery Note PDF generation', [
                'delivery_note_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Gagal generate PDF: ' . $e->getMessage()
            ];
        }
    }

/**
     * Recreate PDF untuk Delivery Note yang sudah ada
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recreate(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:delivery_note,id',
        ], [
            'id.required' => 'ID Delivery Note wajib diisi',
            'id.integer' => 'ID Delivery Note harus berupa angka',
            'id.exists' => 'Delivery Note dengan ID tersebut tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $id = $request->id;

        try {
            // Ambil data delivery note
            $note = DeliveryNote::find($id);
            
            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery Note tidak ditemukan'
                ], 404);
            }

            Log::info('Starting Delivery Note PDF recreate', [
                'delivery_note_id' => $note->id,
                'dn_no' => $note->dn_no ?? 'N/A',
                'old_dn_file' => $note->dn_file ?? 'N/A'
            ]);

            // Generate PDF ulang menggunakan fungsi yang sudah ada
            $pdfResult = $this->generateDeliveryNotePdf($note->id);

            if ($pdfResult && isset($pdfResult['success']) && $pdfResult['success']) {
                // Update kolom dn_file dengan URL PDF baru
                $note->dn_file = $pdfResult['pdf_url'];
                $note->save();

                Log::info('Delivery Note PDF recreated successfully', [
                    'delivery_note_id' => $note->id,
                    'dn_no' => $note->dn_no,
                    'new_pdf_url' => $pdfResult['pdf_url']
                ]);

                // Log aktivitas user
                UserSysLogHelper::logFromAuth($result, 'DeliveryNote', 'recreate');

                return response()->json([
                    'success' => true,
                    'message' => 'PDF Delivery Note berhasil di-generate ulang',
                    'data' => [
                        'id' => $note->id,
                        'dn_no' => $note->dn_no,
                        'dn_file' => $pdfResult['pdf_url'],
                        'pdf_path' => $pdfResult['pdf_path'] ?? null,
                        'missing_fields' => $pdfResult['missing_fields'] ?? []
                    ]
                ]);
            } else {
                Log::error('Failed to recreate Delivery Note PDF', [
                    'delivery_note_id' => $note->id,
                    'dn_no' => $note->dn_no ?? 'N/A',
                    'error' => $pdfResult['error'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal generate ulang PDF: ' . ($pdfResult['error'] ?? 'Unknown error')
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Exception during Delivery Note PDF recreate', [
                'delivery_note_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

// Update
public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $note = DeliveryNote::findOrFail($id);
            $note->update($request->all());
            DB::commit();
            return response()->json(['success' => true, 'data' => $note]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mengupdate Delivery Note: ' . $e->getMessage()], 500);
        }
    }

// Hapus
public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $note = DeliveryNote::findOrFail($id);
            $note->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus Delivery Note: ' . $e->getMessage()], 500);
        }
    }

    // API untuk select DN No (otomatis isi data lain)
public function dnSource()
    {
        $result = DB::select("
            select dn_no, customer_po, nama as customer_name, tgl_po as po_date, 
            created_at as arrival_date, delivery_to, qty, description, vendor_name as transportir, alamat
            from purchase_order
            where status = 4 and category = 2
        ");
        return response()->json($result);
    }

public function uploadBast(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validasi input dari form
            $request->validate([
                'bast_id' => 'required|integer|exists:delivery_note,id',
                'bast_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'tgl_bongkar' => 'nullable|string',
                'jam_mulai' => 'nullable|string',
                'jam_akhir' => 'nullable|string',
                'meter_awal' => 'nullable|string',
                'meter_akhir' => 'nullable|string',
                'tinggi_sounding' => 'nullable|string',
                'jenis_suhu' => 'nullable|string',
                'volume_diterima' => 'nullable|string',
                'bast_date' => 'nullable|date',
            ]);

            // Cari record berdasarkan id
            $id = $request->input('bast_id');
            $note = DeliveryNote::findOrFail($id);

            // Proses penyimpanan file
            if ($request->hasFile('bast_file')) {
                $file = $request->file('bast_file');
                
                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $safeName = str_replace('/', '_', $originalName);
                $fileName = 'bast_' . $id . '_' . time() . '_' . $safeName;
                $filePath = 'bast/' . $fileName;

                // Check if storage disk exists
                if (!Storage::disk('byteplus')) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Storage disk byteplus not configured'
                    ], 500);
                }

                // Upload file to byteplus
                $uploaded = Storage::disk('byteplus')->put($filePath, file_get_contents($file));

                if (!$uploaded) {
                    DB::rollBack();
                    Log::error('Failed to upload BAST file', [
                        'bast_id' => $id,
                        'file_path' => $filePath
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal upload file BAST ke storage'
                    ], 500);
                }

                // Generate full public URL
                $fullUrl = byteplus_url($filePath);
                $note->file = $fullUrl;

                Log::info('BAST file uploaded successfully', [
                    'bast_id' => $id,
                    'file_path' => $filePath,
                    'full_url' => $fullUrl
                ]);
            }

            // Simpan data tambahan dari form BAST
            $note->tgl_bongkar = $request->input('tgl_bongkar');
            $note->jam_mulai = $request->input('jam_mulai');
            $note->jam_akhir = $request->input('jam_akhir');
            $note->meter_awal = $request->input('meter_awal');
            $note->meter_akhir = $request->input('meter_akhir');
            $note->tinggi_sounding = $request->input('tinggi_sounding');
            $note->jenis_suhu = $request->input('jenis_suhu');
            $note->volume_diterima = $request->input('volume_diterima');
            $note->bast_date = $request->input('bast_date') ? Carbon::parse($request->input('bast_date')) : null;
            $note->status = 1; // 1 menandakan BAST sudah diupload
            $note->save();

            $fob = $request->input('fob');
            $sentVia = $request->input('sent_via');

            // Simpan ke table finance_invoice jika belum ada (berdasarkan drs_unique)
            if ($note) {
                \App\Models\FinanceInvoice::firstOrCreate([
                    'drs_unique' => $note->drs_unique
                ], [
                    'drs_no'       => $note->drs_no,
                    'drs_unique'   => $note->drs_unique,
                    'bast_id'      => $note->id,
                    'invoice_no'   => null,
                    'invoice_date' => null,
                    'terms'        => null,
                    'po_no'        => $note->customer_po,
                    'bill_to'      => $note->customer_name,
                    'ship_to'      => $note->customer_name,
                    'fob'          => $fob,
                    'sent_date'    => null,
                    'sent_via'     => $sentVia,
                    'status'       => 0,
                    'created_by'   => $note->created_by,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'BAST berhasil diupload.',
                'data' => $note
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload BAST: ' . $e->getMessage(),
            ], 500);
        }
    }

}
