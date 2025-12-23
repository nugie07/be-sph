<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Helpers\AuthValidator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SphNotificationMail;

use App\Models\GoodReceipt;
use App\Models\DetailGoodReceipt;
use App\Models\RevGoodReceipt;
use App\Models\User;
use App\Models\DataTrxSph;
use App\Models\MasterLov;
use App\Helpers\UserSysLogHelper;

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
			// Join ke baris terakhir (MAX(id)) dari data_trx_sph per kode_sph agar tidak duplikat
			->leftJoin('data_trx_sph as a', function($join){
				$join->on('a.kode_sph', '=', 'b.kode_sph')
					->whereRaw('a.id = (SELECT MAX(id) FROM data_trx_sph WHERE kode_sph = b.kode_sph)');
			})
			->select([
                'b.id as po_id',
                'a.tipe_sph',
                'a.kode_sph',
                'a.comp_name',
                'a.product',
                'b.total as total_price', // Ambil dari good_receipt.total (alias total_harga di response)
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

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'GoodReceipt', 'list');

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
            'no_seq'     => 'nullable|string',
            'wilayah'    => 'required|string',
            'source'     => 'required|string',
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
            $gr->no_seq    = $data['no_seq'] ?? null;
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

            // 3) Update sequence berdasarkan source dan wilayah
            $this->updateSequence($gr->kode_sph, $data['source'], $data['wilayah']);

            DB::commit();
            // Kirim email notification ke user yang berhak
            try {
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
            } catch (\Exception $e) {
                // Log error tapi tidak mengganggu proses update
                Log::warning('Gagal mengirim email notification: ' . $e->getMessage());
            }

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'GoodReceipt', 'update');

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
        if (!$workflow) {
            Log::warning('Workflow email_po tidak ditemukan');
            return false;
        }

        $roleIds = [];
        if ($workflow->first_appr) $roleIds[] = $workflow->first_appr;
        if ($workflow->second_appr) $roleIds[] = $workflow->second_appr;

        if (empty($roleIds)) {
            Log::warning('Role IDs untuk email_po tidak ditemukan di workflow_engine');
            return false;
        }

        $users = DB::table('users as a')
            ->leftJoin('model_has_roles as b', 'b.model_id', '=', 'a.id')
            ->whereIn('b.role_id', $roleIds)
            ->select('a.first_name', 'a.last_name', 'a.email')
            ->groupBy('a.id', 'a.first_name', 'a.last_name', 'a.email')
            ->get();

        if ($users->isEmpty()) {
            Log::warning('User dengan role terkait tidak ditemukan untuk email notification');
            return false;
        }

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

    /**
     * Update sequence berdasarkan wilayah dengan logika IASE atau non-IASE
     * 
     * @param string $kodeSph Kode SPH dari good receipt
     * @param string $wilayah Wilayah dari request (contoh: "01")
     * @throws \Exception Jika terjadi error saat update sequence
     */
    private function updateSequenceByWilayah($kodeSph, $wilayah)
    {
        try {
            $wilayahTrimmed = trim($wilayah);
            $wilayahUpper = strtoupper($wilayahTrimmed);
            
            // Cek apakah ada value di master_lov yang mengandung "IASE" + wilayah (contoh: "IASE01")
            $iaseValue = 'IASE' . $wilayahUpper;
            $hasIaseValue = MasterLov::where('value', 'LIKE', '%' . $iaseValue . '%')
                ->orWhere('value', 'LIKE', '%IASE%')
                ->exists();
            
            // Atau cek langsung di code DO_IASE_SEQ apakah value-nya mengandung IASE + wilayah
            $iaseSeq = MasterLov::where('code', 'DO_IASE_SEQ')->first();
            $hasIaseLabel = false;
            
            if ($iaseSeq && !empty($iaseSeq->value)) {
                // Cek apakah value mengandung IASE + wilayah (contoh: IASE01)
                $hasIaseLabel = (stripos($iaseSeq->value, $iaseValue) !== false) || 
                                (stripos($iaseSeq->value, 'IASE') !== false);
            }
            
            $seqCode = '';
            $currentValue = '';
            $newValue = '';
            $masterLov = null;
            
            if ($hasIaseLabel && $iaseSeq) {
                // Kondisi a: Apabila value ada label IASE (contoh: IASE01)
                // Pencarian dengan code = DO_IASE_SEQ
                $seqCode = 'DO_IASE_SEQ';
                $masterLov = $iaseSeq;
                $currentValue = $masterLov->value;
                
                // Extract angka dari value (contoh: IASE01 -> 01, IASE02 -> 02)
                // Increment angka tersebut
                if (preg_match('/IASE(\d+)/i', $currentValue, $matches)) {
                    $number = (int)$matches[1];
                    $newNumber = $number + 1;
                    // Format ulang dengan padding 2 digit (01, 02, dst)
                    $newValue = 'IASE' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
                } else {
                    // Jika format tidak sesuai, mulai dari IASE01
                    $newValue = 'IASE01';
                }
                
            } else {
                // Kondisi b: Apabila tidak ada label IASE (hanya "01")
                // Pencarian dengan code = DO_{wilayah}_SEQ (contoh: DO_01_SEQ)
                $seqCode = 'DO_' . $wilayahUpper . '_SEQ';
                
                $masterLov = MasterLov::where('code', $seqCode)->first();
                
                if (!$masterLov) {
                    throw new \Exception("Master LOV dengan code '{$seqCode}' tidak ditemukan");
                }
                
                $currentValue = $masterLov->value;
                
                // Increment value +1
                if (is_numeric($currentValue)) {
                    $newValue = (string)((int)$currentValue + 1);
                } else {
                    // Jika value bukan numeric, mulai dari 1
                    $newValue = '1';
                }
            }
            
            // Update value di master_lov
            $masterLov->value = $newValue;
            $masterLov->save();
            
            Log::info('Sequence updated by wilayah', [
                'kode_sph' => $kodeSph,
                'wilayah' => $wilayah,
                'has_iase_label' => $hasIaseLabel,
                'seq_code' => $seqCode,
                'old_value' => $currentValue,
                'new_value' => $newValue
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating sequence by wilayah', [
                'kode_sph' => $kodeSph,
                'wilayah' => $wilayah,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e; // Re-throw exception agar bisa di-catch di try-catch utama
        }
    }

    /**
     * Update sequence berdasarkan source dan wilayah (function lama, tetap dipertahankan untuk backward compatibility)
     * 
     * @param string $kodeSph Kode SPH dari good receipt
     * @param string $source Source dari request (penanda IASE atau bukan)
     * @param string $wilayah Wilayah dari request
     * @throws \Exception Jika terjadi error saat update sequence
     */
    private function updateSequence($kodeSph, $source, $wilayah)
    {
        try {
            // Tentukan code sequence berdasarkan source
            $seqCode = '';
            $sourceUpper = strtoupper(trim($source));
            $wilayahUpper = strtoupper(trim($wilayah));
            
            // Cek apakah source adalah "IASE"
            if ($sourceUpper === 'IASE') {
                $seqCode = 'DO_IASE_SEQ';
            } else {
                // Ambil 2 karakter pertama dari wilayah untuk non-IASE
                $twoDigits = substr($wilayahUpper, 0, 2);
                if (strlen($twoDigits) < 2) {
                    throw new \Exception('Wilayah tidak valid: minimal 2 karakter diperlukan untuk non-IASE');
                }
                $seqCode = 'DO_' . $twoDigits . '_SEQ';
            }

            // Ambil value sequence saat ini dari master_lov
            $masterLov = MasterLov::where('code', $seqCode)->first();
            
            if (!$masterLov) {
                throw new \Exception("Master LOV dengan code '{$seqCode}' tidak ditemukan");
            }

            // Increment sequence value
            $currentValue = $masterLov->value;
            $newValue = (int)$currentValue + 1;
            
            // Update value (konversi kembali ke string)
            $masterLov->value = (string)$newValue;
            $masterLov->save();

            Log::info('Sequence updated', [
                'kode_sph' => $kodeSph,
                'source' => $source,
                'wilayah' => $wilayah,
                'seq_code' => $seqCode,
                'old_value' => $currentValue,
                'new_value' => $newValue
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating sequence', [
                'kode_sph' => $kodeSph,
                'source' => $source,
                'wilayah' => $wilayah,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e; // Re-throw exception agar bisa di-catch di try-catch utama
        }
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
                'no_seq'     => 'nullable|string',
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
                $gr->no_seq    = $data['no_seq'] ?? null;
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

                // Log aktivitas user
                UserSysLogHelper::logFromAuth($result, 'GoodReceipt', 'revisi');

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

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'GoodReceipt', 'cancelPo');

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

    /**
     * List SPH yang sudah approved (status = 4)
     * Menampilkan kode_sph dari data_trx_sph yang sudah di-approve
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sphApproved(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        // Query data SPH dengan status = 4 (approved)
        $sphApproved = DB::table('data_trx_sph')
            ->where('status', 4)
            ->select('id', 'kode_sph', 'comp_name', 'tipe_sph', 'product', 'total_price', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Log aktivitas user
        UserSysLogHelper::logFromAuth($result, 'GoodReceipt', 'sphApproved');

        return response()->json([
            'success' => true,
            'message' => 'Data SPH approved berhasil diambil',
            'data' => $sphApproved,
            'total' => $sphApproved->count()
        ]);
    }

    /**
     * Tambah PO baru dengan upload file PO
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tambahPo(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        $fullName = "{$user->first_name} {$user->last_name}";

        // Validasi payload
        $data = $request->validate([
            'sph_id'          => 'required|integer|exists:data_trx_sph,id',
            'nama_perusahaan' => 'required|string',
            'source'          => 'required|string',
            'po_no_customer' => 'required|string|min:3',
            'wilayah'        => 'required|string',
            'no_seq'         => 'nullable|string',
            'qty'            => 'nullable|numeric|min:0',
            'hsd_solar'      => 'required|numeric|min:0',
            'ongkos_angkut'  => 'required|numeric|min:0',
            'subtotal'       => 'required|numeric|min:0',
            'ppn'            => 'required|numeric|min:0',
            'pbbkb'          => 'required|numeric|min:0',
            'pph'            => 'required|numeric|min:0',
            'transport'      => 'required|numeric|min:0',
            'total'          => 'required|numeric|min:0',
            'terbilang'      => 'required|string', // Terbilang dari payload
            'bypass'         => 'nullable|integer|in:0,1', // Hanya menerima 0 atau 1
            'file_po'        => 'required|file|mimes:pdf,doc,docx|max:1024', // PDF dan Word, maksimal 1MB
        ]);

        DB::beginTransaction();

        try {
            // Ambil data SPH
            $sph = DataTrxSph::findOrFail($data['sph_id']);
            
            // Generate daily sequence
            $today = now()->toDateString();
            $nextSeq = GoodReceipt::whereDate('created_at', $today)->max('daily_seq');
            $nextSeq = ($nextSeq ?? 0) + 1;

            // Upload file PO (menggunakan logic yang sama dengan update)
            $filePath = null;
            if ($request->hasFile('file_po')) {
                $filePath = $request->file('file_po')->store(
                    'good_receipt',
                    'idcloudhost'
                );
            }

            // Buat Good Receipt baru
            $gr = new GoodReceipt();
            $gr->kode_sph = $sph->kode_sph;
            $gr->daily_seq = $nextSeq;
            $gr->nama_customer = $data['nama_perusahaan'];
            $gr->po_no = $data['po_no_customer'];
            $gr->no_seq = $data['no_seq'] ?? null;
            $gr->sub_total = $data['subtotal'];
            $gr->ppn = $data['ppn'];
            $gr->pbbkb = $data['pbbkb'];
            $gr->pph = $data['pph'];
            $gr->transport = $data['transport']; // Simpan transport ke tabel good_receipt
            $gr->bypass = isset($data['bypass']) ? (int)$data['bypass'] : 0; // Simpan bypass sebagai 0 atau 1 (default 0)
            $gr->total = $data['total'];
            $gr->terbilang = $data['terbilang']; // Gunakan terbilang dari payload
            $gr->po_file = $filePath;
            $gr->status = 0; // Status awal: waiting
            $gr->created_by = $fullName;
            $gr->last_updateby = $fullName;
            $gr->revisi_count = 0;
            $gr->save();

            // Simpan hsd_solar dan ongkos_angkut ke detail_good_receipt sebagai 2 record terpisah
            // Hanya insert jika nilainya > 0
            // Gunakan qty yang sama untuk kedua detail items
            $qty = !empty($data['qty']) && $data['qty'] > 0 ? (float)$data['qty'] : 1; // Default 1 jika qty tidak dikirim atau 0
            
            if (!empty($data['hsd_solar']) && $data['hsd_solar'] > 0) {
                // per_item = nilai dari payload hsd_solar
                // total_harga = hsd_solar * qty
                $perItemHsdSolar = (float)$data['hsd_solar'];
                $totalHargaHsdSolar = $perItemHsdSolar * $qty;
                
                DetailGoodReceipt::create([
                    'gr_id'        => $gr->id,
                    'nama_item'    => 'HSD Solar',
                    'qty'          => $qty,
                    'per_item'     => $perItemHsdSolar, // Gunakan hsd_solar dari payload
                    'total_harga'  => $totalHargaHsdSolar, // hsd_solar * qty
                ]);
            }

            if (!empty($data['ongkos_angkut']) && $data['ongkos_angkut'] > 0) {
                // per_item = nilai dari payload ongkos_angkut
                // total_harga = ongkos_angkut * qty
                $perItemOngkosAngkut = (float)$data['ongkos_angkut'];
                $totalHargaOngkosAngkut = $perItemOngkosAngkut * $qty;
                
                DetailGoodReceipt::create([
                    'gr_id'        => $gr->id,
                    'nama_item'    => 'Ongkos Angkut',
                    'qty'          => $qty,
                    'per_item'     => $perItemOngkosAngkut, // Gunakan ongkos_angkut dari payload
                    'total_harga'  => $totalHargaOngkosAngkut, // ongkos_angkut * qty
                ]);
            }

            // Update sequence berdasarkan wilayah dengan logika IASE atau non-IASE
            if (!empty($data['wilayah'])) {
                $this->updateSequenceByWilayah($gr->kode_sph, $data['wilayah']);
            }

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'GoodReceipt', 'tambahPo');

            return response()->json([
                'success' => true,
                'message' => 'PO berhasil ditambahkan',
                'data' => [
                    'id' => $gr->id,
                    'kode_sph' => $gr->kode_sph,
                    'po_no' => $gr->po_no,
                    'nama_customer' => $gr->nama_customer,
                    'total' => $gr->total,
                    'file_po' => $filePath ? 'https://is3.cloudhost.id/bensinkustorage/' . $filePath : null,
                    'status' => $gr->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error tambah PO: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan PO',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function untuk konversi angka ke terbilang
     * 
     * @param float $angka
     * @return string
     */
    private function terbilang($angka)
    {
        $angka = abs($angka);
        $bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        
        if ($angka < 12) {
            return $bilangan[$angka] . ' rupiah';
        } elseif ($angka < 20) {
            return $bilangan[$angka - 10] . ' belas rupiah';
        } elseif ($angka < 100) {
            $hasil = $bilangan[floor($angka / 10)] . ' puluh';
            if ($angka % 10 > 0) {
                $hasil .= ' ' . $bilangan[$angka % 10];
            }
            return $hasil . ' rupiah';
        } elseif ($angka < 1000) {
            $hasil = $bilangan[floor($angka / 100)] . ' ratus';
            $sisa = $angka % 100;
            if ($sisa > 0) {
                $hasil .= ' ' . $this->terbilang($sisa);
            }
            return $hasil;
        } elseif ($angka < 1000000) {
            $hasil = $this->terbilang(floor($angka / 1000)) . ' ribu';
            $sisa = $angka % 1000;
            if ($sisa > 0) {
                $hasil .= ' ' . $this->terbilang($sisa);
            }
            return $hasil;
        } elseif ($angka < 1000000000) {
            $hasil = $this->terbilang(floor($angka / 1000000)) . ' juta';
            $sisa = $angka % 1000000;
            if ($sisa > 0) {
                $hasil .= ' ' . $this->terbilang($sisa);
            }
            return $hasil;
        } else {
            // Untuk angka sangat besar, gunakan format numerik
            return number_format($angka, 0, ',', '.') . ' rupiah';
        }
    }

}
