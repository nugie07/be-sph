<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


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

            return response()->json(['success' => true, 'data' => $note]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan Delivery Note: ' . $e->getMessage()], 500);
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
            select a.dn_no,a.drs_no,a.drs_unique,a.po_number as customer_po,a.customer_name,a.po_date,a.request_date as arrival_date,
            b.delivery_to,b.qty,b.description,a.transporter_name as transportir
            from sph_db.delivery_request a
            left join sph_db.purchase_order b ON b.drs_unique = a.drs_unique
            where b.status = 4 and b.category = 2
        ");
        return response()->json($result);
    }

public function uploadBast(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validasi input dari form
            $request->validate([
                'bast_drs_unique' => 'required|string|exists:delivery_note,drs_unique',
                'bast_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024', // max 1MB
                'tgl_bongkar' => 'nullable|string',
                'jam_mulai' => 'nullable|string',
                'jam_akhir' => 'nullable|string',
                'meter_awal' => 'nullable|string',
                'meter_akhir' => 'nullable|string',
                'tinggi_sounding' => 'nullable|string',
                'jenis_suhu' => 'nullable|string',
                'volume_diterima' => 'nullable|string',
                'bast_file'       => 'nullable|file|mimes:pdf|max:2048',
                'bast_date'       => 'nullable|date',
            ]);

            // Cari record berdasarkan drs_unique
            $drs_unique = $request->input('bast_drs_unique');
            $note = DeliveryNote::where('drs_unique', $drs_unique)->first();

            if (!$note) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Delivery Note tidak ditemukan.'], 404);
            }

            // Proses penyimpanan file
            if ($request->hasFile('bast_file')) {
                $path = $request->file('bast_file')->store(
                        'bast',
                        'idcloudhost'
                    );
                $note->file = $path;
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
