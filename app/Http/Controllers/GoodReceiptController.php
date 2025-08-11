<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Helpers\AuthValidator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SphNotificationMail;

use App\Models\GoodReceipt;
use App\Models\DetailGoodReceipt;
use App\Models\RevGoodReceipt;
use App\Models\User;
use App\Models\DataTrxSph;

class GoodReceiptController extends Controller
{
public function list(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $user = User::find($result['id']);
        // Query utama (good_receipt sebagai tabel utama)
        $data = DB::table('good_receipt as b')
            ->leftJoin('data_trx_sph as a', 'a.kode_sph', '=', 'b.kode_sph')
            ->select([
                'b.id as po_id',
                'a.tipe_sph',
                'a.kode_sph',
                'a.comp_name',
                'a.product',
                'a.total_price',
                'a.created_by',
                'b.po_no',
                'b.po_file',
                'b.status',
                'b.revisi_count',
                'b.created_at',
                DB::raw('DATEDIFF(CURDATE(), DATE(b.created_at)) AS aging_po')
            ])
            ->get();

        // Card summary
       $total_po = DB::table('data_trx_sph')->where('status', 4)->count();
        $total_po_belum = $data->where('status', 0)->count();
        $total_po_terima = $data->where('status', 1)->count();

        $cards = [
            'total_sph'    => $total_po,
            'waiting'      => $total_po_belum,
            'received'     => $total_po_terima,
        ];

        return response()->json([
            'data'  => $data,
            'cards' => $cards
        ]);
    }

public function detail(Request $request ,$po_id)
    {
         $result = AuthValidator::validateTokenAndClient($request);
            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $user = User::find($result['id']);
        // Ambil good receipt beserta detailnya
        $gr = GoodReceipt::with('detail')->find($po_id);
        if (!$gr) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Ambil detaul SPH menurut SPH kode
        $sphGr = DataTrxSph::where('kode_sph', $gr->kode_sph)->first();        if (!$sphGr){
            return response()->json(['message' => ' Tidak ada data sph'],404);
        }

        return response()->json([
            'kode_sph'      => $gr->kode_sph,
            'nama_customer' => $gr->nama_customer,
            'po_file'       => $gr->po_file,
            'created_at'    => $gr->created_at,
            'daily_seq'     => $gr->daily_seq,
            'data_sph'      => [
                'tipe_sph'     => $sphGr->tipe_sph,
                'product'      => $sphGr->product,
                'price_liter'  => $sphGr->price_liter,
                'biaya_lokasi' => $sphGr->biaya_lokasi,
                'ppn'          => $sphGr->ppn,
                'pbbkb'        => $sphGr->pbbkb,
                'total_price'  => $sphGr->total_price,
                'pay_method'   => $sphGr->pay_method,
                'note_berlaku' => $sphGr->note_berlaku,
            ],
            'items'         => $gr->detail->map(function($d){
                return [
                    'nama_item'   => $d->nama_item,
                    'qty'         => $d->qty,
                    'per_item'    => $d->per_item,
                    'total_harga' => $d->total_harga,
                ];
            })->values(),
        ]);
    }

public function update(Request $request, $po_id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";
        if ($request->has('items') && is_string($request->items)) {
            $request->merge([
                'items' => json_decode($request->items, true)
            ]);
        }
        $data = $request->validate([
            'po_no'      => 'required|string|min:3',
            'sub_total'  => 'required|numeric',
            'ppn'        => 'required|numeric',
            'pbbkb'      => 'required|numeric',
            'pph'        => 'required|numeric',
            'total'      => 'required|numeric',
            'terbilang'  => 'required|string',
            'status'     => 'required|in:1',
            'items'      => 'required|array|min:1',
            'items.*.nama_item'    => 'required|string',
            'items.*.qty'          => 'required|numeric|min:1',
            'items.*.per_item'     => 'required|numeric|min:0',
            'items.*.total_harga'  => 'required|numeric|min:0',
            'file'       => 'nullable|file|mimes:pdf|max:2048',
        ]);


        DB::beginTransaction();

        try {
            // 1) Update header good_receipt
            $gr = GoodReceipt::findOrFail($po_id);

            // Backup data sebelum update ke table rev_good_receipt
            if ($gr->po_file) {
                DB::table('rev_good_receipt')->insert([
                    'gr_id' => $gr->id,
                    'old_po' => $gr->po_file,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            $gr->po_no     = $data['po_no'];
            $gr->sub_total = $data['sub_total'];
            $gr->ppn       = $data['ppn'];
            $gr->pbbkb     = $data['pbbkb'];
            $gr->pph       = $data['pph'];
            $gr->total     = $data['total'];
            $gr->terbilang = $data['terbilang'];
            $gr->status    = $data['status'];
            $gr->last_updateby = $fullName;
            $gr->revisi_count = $gr->revisi_count + 1; // Increment revisi count

            // if file was uploaded, store it and update po_file
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store(
                    'good_receipt',
                    'idcloudhost'
                );
                $gr->po_file = $path;
            }

            $gr->save();

            // 2) Rebuild details
            DetailGoodReceipt::where('gr_id', $po_id)->delete();
            foreach ($data['items'] as $item) {
                DetailGoodReceipt::create([
                    'gr_id'        => $po_id,
                    'nama_item'    => $item['nama_item'],
                    'qty'          => $item['qty'],
                    'per_item'     => $item['per_item'],
                    'total_harga'  => $item['total_harga'],
                ]);
            }

            DB::commit();
            // Kirim email notification ke user yang berhak
            $pofile = "https://is3.cloudhost.id/bensinkustorage/$gr->po_file";
            $validatedForMail = [
                'no_po'      => $gr->po_no,
                'sph'       => $gr->kode_sph,
                'penerima'   => $gr->last_updateby,
                'total'      => $gr->total,
                'terbilang'  => $gr->terbilang,
                'file'       => $pofile,
                'customer'  => $gr->nama_customer,
            ];

            $this->sendPoNotif($validatedForMail, $gr->po_file);

            return response()->json([
                'code' => 200,
                'message' => 'Good Receipt berhasil disimpan.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'code' => 500,
                'message' => 'Gagal menyimpan Good Receipt!',
                'error' => $e->getMessage(),
            ], 500);
        }
        log_system('po', 'PO -> Create', 'Pembuatan PO', $gr, $response);

    }

private function sendPoNotif($validated, $fileUrl = null)
    {
        $workflow = DB::table('workflow_engine')->where('tipe_trx', 'email_po')->first();
        if (!$workflow) throw new \Exception('Workflow tidak ditemukan');

        $roleIds = [];
        if ($workflow->first_appr) $roleIds[] = $workflow->first_appr;
        if ($workflow->second_appr) $roleIds[] = $workflow->second_appr;

        $users = DB::table('users as a')
            ->leftJoin('model_has_roles as b', 'b.model_id', '=', 'a.id')
            ->whereIn('b.role_id', $roleIds)
            ->select('a.first_name', 'a.last_name', 'a.email')
            ->groupBy('a.id', 'a.first_name', 'a.last_name', 'a.email')
            ->get();

        if ($users->isEmpty()) throw new \Exception('User dengan role terkait tidak ditemukan');

        $recipients = [];
        foreach ($users as $user) {
            $recipients[] = [
                'email' => $user->email,
                'name'  => trim($user->first_name . ' ' . $user->last_name)
            ];
        }

        // Panggil generic job async
        \App\Jobs\SendNotificationJob::dispatch(
            $recipients,
            $validated,
            $fileUrl,
            'Pemberitahuan PO Baru',
            'emails.po_notification'
        );

        log_system('po', 'PO -> Email', 'Job Informasi PO diterima & dikirim ke queue', $validated, [
            'message' => 'Notification job dispatched'
        ]);

        return ['message' => 'Notification job dispatched'];
    }


public function viewPdf(Request $request ,$path)
    {
        $result = AuthValidator::validateTokenAndClient($request);
            if (!is_array($result) || !$result['status']) {
                return $result;
            }
        $user = User::find($result['id']);
       // Jangan tambahkan lagi 'good_receipt/', path sudah lengkap!
    if (!Storage::disk('idcloudhost')->exists($path)) {
        abort(404, 'File not found');
    }
    $stream = Storage::disk('idcloudhost')->readStream($path);

    return response()->stream(function() use ($stream) {
        fpassthru($stream);
    }, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.basename($path).'"'
    ]);
    }

public function tambahGr(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
                if (!is_array($result) || !$result['status']) {
                    return $result;
                }
        $user = User::find($result['id']);
        $fullName  = "{$user->first_name} {$user->last_name}";
        $request->validate([
            'sph_id' => 'required|integer|exists:data_trx_sph,id',
            // validasi lain jika perlu
        ]);
        // logic buat GoodReceipt baru dari SPH
        $sph = DB::table('data_trx_sph')->find($request->sph_id);
        if (!$sph) return response()->json(['message' => 'SPH tidak ditemukan!'], 404);

        $today = now()->toDateString(); // format YYYY-MM-DD

        $nextSeq = GoodReceipt::whereDate('created_at', $today)
        ->max('daily_seq');

        $nextSeq = ($nextSeq ?? 0) + 1;

        $gr = new GoodReceipt();
        $gr->kode_sph = $sph->kode_sph;
        $gr->daily_seq = $nextSeq;
        $gr->nama_customer = $sph->comp_name;
        $gr->created_by = $fullName;
        $gr->status = 0;
        $gr->save();

        return response()->json(['message' => 'Good Receipt berhasil dibuat.']);
    }

public function revisi(Request $request, $id)
        {
            $result = AuthValidator::validateTokenAndClient($request);
            if (!is_array($result) || !$result['status']) {
                return $result;
            }
            $user = User::find($result['id']);
            $fullName  = "{$user->first_name} {$user->last_name}";

            if ($request->has('items') && is_string($request->items)) {
                $request->merge([
                    'items' => json_decode($request->items, true)
                ]);
            }

            $data = $request->validate([
                'po_no'      => 'required|string|min:3',
                'sub_total'  => 'required|numeric',
                'ppn'        => 'required|numeric',
                'pbbkb'      => 'required|numeric',
                'pph'        => 'required|numeric',
                'total'      => 'required|numeric',
                'terbilang'  => 'required|string',
                'status'     => 'required|in:1',
                'items'      => 'required|array|min:1',
                'items.*.nama_item'    => 'required|string',
                'items.*.qty'          => 'required|numeric|min:1',
                'items.*.per_item'     => 'required|numeric|min:0',
                'items.*.total_harga'  => 'required|numeric|min:0',
                'file'       => 'nullable|file|mimes:pdf|max:2048',
            ]);

            DB::beginTransaction();
            try {
                $gr = GoodReceipt::findOrFail($id);

                // Backup data sebelum revisi ke table rev_good_receipt
                if ($gr->po_file) {
                    DB::table('rev_good_receipt')->insert([
                        'gr_id' => $gr->id,
                        'old_po' => $gr->po_file,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                $gr->po_no     = $data['po_no'];
                $gr->sub_total = $data['sub_total'];
                $gr->ppn       = $data['ppn'];
                $gr->pbbkb     = $data['pbbkb'];
                $gr->pph       = $data['pph'];
                $gr->total     = $data['total'];
                $gr->terbilang = $data['terbilang'];
                $gr->status    = $data['status'];
                $gr->last_updateby = $fullName;
                $gr->revisi_count = $gr->revisi_count + 1; // Increment revisi count

                // Handle file jika ada upload revisi
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store(
                        'good_receipt',
                        'idcloudhost'
                    );
                    $gr->po_file = $path;
                }

                $gr->save();

                // Rebuild details
                DetailGoodReceipt::where('gr_id', $id)->delete();
                foreach ($data['items'] as $item) {
                    DetailGoodReceipt::create([
                        'gr_id'        => $id,
                        'nama_item'    => $item['nama_item'],
                        'qty'          => $item['qty'],
                        'per_item'     => $item['per_item'],
                        'total_harga'  => $item['total_harga'],
                    ]);
                }

                DB::commit();

                // (Opsional) Kirim notifikasi email, jika memang perlu pada revisi
                // $this->sendPoNotif(...);

                return response()->json([
                    'code' => 200,
                    'message' => 'Revisi Good Receipt berhasil disimpan.'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'code' => 500,
                    'message' => 'Gagal menyimpan revisi!',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        /**
     * Cancel PO - mengubah status menjadi 9
     */
    public function cancelPo(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        $fullName = "{$user->first_name} {$user->last_name}";

        try {
            DB::beginTransaction();

            // Cari Good Receipt
            $gr = GoodReceipt::findOrFail($id);

            // Update status menjadi cancelled (9)
            $gr->status = 9;
            $gr->last_updateby = $fullName;
            $gr->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'PO berhasil dibatalkan',
                'data' => [
                    'po_id' => $gr->id,
                    'po_no' => $gr->po_no,
                    'kode_sph' => $gr->kode_sph,
                    'status' => $gr->status,
                    'cancelled_by' => $fullName,
                    'cancelled_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan PO: ' . $e->getMessage()
            ], 500);
        }
    }

}
