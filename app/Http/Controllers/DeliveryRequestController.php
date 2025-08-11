<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryRequest;
use App\Models\GoodReceipt;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuthValidator;
use App\Models\User;
use App\Helpers\WorkflowHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\DNS1D; // Assuming you are using this package for barcode generation

class DeliveryRequestController extends Controller
{

public function index(Request $request)
    {
       $query = DeliveryRequest::query();

    if ($request->has('month')) {
        $month = $request->get('month'); // format YYYY-MM
        $query->whereRaw("DATE_FORMAT(request_date, '%Y-%m') = ?", [$month]);
    }

    $data = $query->orderBy('request_date', 'desc')->get();

    // Add purchase_order existence check for each item
    foreach ($data as $row) {
        $row->purchase_order = DB::table('purchase_order')
            ->where('drs_unique', $row->drs_unique)
            ->exists() ? 1 : 0;
    }

    // Sort: yang purchase_order=0 duluan (belum pernah diajukan PO)
    $data = $data->sortBy('purchase_order')->values();

    return response()->json(['data' => $data]);
    }
    /**
     * Store a new Delivery Request.
     */
public function store(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";
        $validated = $request->validate([
            'po_number'         => 'required',
            'customer_name'     => 'required',
            'po_date'           => 'required|date',
            'source'            => 'required',
            'volume'            => 'required|numeric',
            'truck_capacity'    => 'required|numeric',
            'request_date'      => 'required|date',
            'transporter_name'  => 'required',
            'wilayah'           => 'required',
            'site_location'     => 'required',
            'pic_site'          => 'required',
            'pic_site_telp'     => 'required',
            'additional_note'   => 'nullable',
            'drs_no'            => 'required',
            'requested_by'      => 'required',
            'drs_unique'        => 'required',
            'wilayah_nama'      => 'nullable',
            'dn_no'     => 'nullable',
            
        ]);
        
        $validated['wilayah'] = $validated['wilayah_nama'];
        unset($validated['wilayah_nama']);
        // $validated['dn_no'] = $dnNo;
        $validated['created_by'] = $fullName;
       
        $drs = DeliveryRequest::create($validated);

        // Generate PDF & update file_drs
        try {
            $pdfResult = $this->generatePdf($drs->drs_unique);
            // Get PDF URL from response
            if (is_a($pdfResult, \Illuminate\Http\JsonResponse::class)) {
                $pdfData = $pdfResult->getData();
                if (isset($pdfData->pdf_url) && $pdfData->pdf_url) {
                    $drs->file_drs = $pdfData->pdf_url;
                    $drs->save();
                }
            }
        } catch (\Throwable $e) {
            \Log::error('PDF generation failed for DRS '.$drs->drs_unique, ['error'=>$e->getMessage()]);
        }

        return response()->json([
            'message' => 'Delivery Request created successfully',
            'data' => $drs
        ], 201);
    }

    /**
     * Delete a Delivery Request.
     */
public function destroy($id)
    {
        $drs = DeliveryRequest::findOrFail($id);
        $drs->delete();

        return response()->json([
            'message' => 'Delivery Request deleted successfully'
        ]);
    }

    /**
     * List PO numbers from good_receipt where status = 1 and not yet in purchase_order.
     */
public function listPo()
    {
        // Ambil semua PO aktif dari tabel good_receipt tanpa filter purchase_order
        $poList = DB::table('good_receipt')
            ->where('status', 1)
            ->select('id', 'daily_seq', 'po_no')
            ->get();

        return response()->json([
            'data' => $poList
        ]);
    }

public function wilayahList()
    {
    $parentId = DB::table('mst_lov')->where('code', 'CODE_MMTEI_LOKASI')->value('id');
    $data = DB::table('mst_lov')->where('parent_id', $parentId)->select('code', 'value')->get();
    return response()->json(['data' => $data]);
    }
/**
     * Generate Delivery Note otomatis berdasarkan aturan:
     * - Jika 5 digit awal PO = “IASE”, pakai DO_IASE_SEQ
     * - Jika bukan, pakai DO_{wilayah}_SEQ
     */
public function deliveryNoteSequence(Request $request)
    {
        $poNo    = $request->query('po');
        $wilayah = $request->query('wilayah');
        $sourceVal = $request->query('source');
        // cek prefix “IASE”
        //if (Str::upper(substr($poNo, 0, 4)) === 'IASE') {
        if ($sourceVal === 'IASE') {
            $code = 'DO_IASE_SEQ';
            $prefix = 'IASE';
        } else {
            $code = 'DO_' . $wilayah . '_SEQ';
            $prefix = $wilayah . '.';
        }

        // ambil current value (misal “005”)
        $lov = DB::table('master_lov')->where('code', $code)->first();
        $next = intval($lov->value) + 1;
        $newVal = str_pad($next, 3, '0', STR_PAD_LEFT);
        // Tidak update ke DB di sini!
        // bentuk delivery note
        $deliveryNote = $prefix . $newVal;

        return response()->json(['delivery_note' => $deliveryNote]);
    }

    /**
     * Generate PDF for a Delivery Request and upload to idcloudhost.
     *
     * @param string $drs_unique
     * @return \Illuminate\Http\JsonResponse
     */
public function generatePdf(string $drs_unique)
    {
        // Fetch data
        $drs = DeliveryRequest::where('drs_unique', $drs_unique)->firstOrFail();

        // Determine tipe_sph from drs_no
        $tipe = null;
        if (Str::contains($drs->drs_no, 'MMTEI')) {
            $tipe = 'MMTEI';
            $headerTitle ='PT MINA MARRET TRANS ENERGI INDONESIA';
        } elseif (Str::contains($drs->drs_no, 'IASE')) {
            $tipe = 'IASE';
            $headerTitle ='PT INDO ANUGERAH SUKSES ENERGI';
        }

        // Find parent LOV record whose value matches tipe_sph exactly
        $parentLov = $tipe
            ? DB::table('reporting_lov')
                ->where('code', $tipe)
                ->first()
            : null;

        $parentId = $parentLov->id ?? null;

        // Fetch logo URL from reporting_lov children where code = 'Logo'
        $logoUrl = $parentId
            ? DB::table('reporting_lov')
                ->where('parent_id', $parentId)
                ->where('code', 'Logo')
                ->value('value')
            : null;

        $no = $drs_unique;
     
        // Render PDF, pass user and logoUrl
        $pdf = PDF::loadView(
            'pdf.drs_pdf_template',
            compact('drs', 'no', 'logoUrl','headerTitle')
        );
        // Define filename and path
        $filename = 'drs/'.$drs_unique.'.pdf';
        // Store PDF on idcloudhost disk
        Storage::disk('idcloudhost')->put($filename, $pdf->output());
        // Get URL
        $url = Storage::disk('idcloudhost')->url($filename);
        return response()->json([
            'pdf_url'    => $url,
            'logo_url'   => $logoUrl,
            'tipe'       => $tipe,
            'parent'    => $parentId,
            'logo_found' => !empty($logoUrl)
        ], 200);
    }

}
    