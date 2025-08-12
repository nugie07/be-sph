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

        $customers = MasterCustomer::where('type', $type)
            ->where('status', 1)
            ->get(['id', 'name']);

        return response()->json($customers);
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

        // 2) Validasi input
        $validated = $request->validate([
            'tipe_sph'     => 'required',
            'kode_sph'     => 'required',
            'comp_name'    => 'required',
            'pic'          => 'required',
            'contact_no'   => 'required',
            'product'      => 'required',
            'price_liter'  => 'required|numeric',
            'biaya_lokasi' => 'required',
            'ppn'          => 'required|numeric',
            'pbbkb'        => 'required|numeric',
            'total_price'  => 'required|numeric',
            'pay_method'   => 'required',
            'susut'        => 'nullable',
            'note_berlaku' => 'nullable',
        ]);

        // 3) Mulai transaksi
        DB::beginTransaction();
        try {
            // a) Simpan SPH
            $sph = new DataTrxSph();
            $sph->tipe_sph     = $validated['tipe_sph'];
            $sph->kode_sph     = $validated['kode_sph'];
            $sph->comp_name    = $validated['comp_name'];
            $sph->pic          = $validated['pic'];
            $sph->contact_no   = $validated['contact_no'];
            $sph->product      = $validated['product'];
            $sph->price_liter  = $validated['price_liter'];
            $sph->biaya_lokasi = $validated['biaya_lokasi'];
            $sph->ppn          = $validated['ppn'];
            $sph->pbbkb        = $validated['pbbkb'];
            $sph->total_price  = $validated['total_price'];
            $sph->pay_method   = $validated['pay_method'];
            $sph->susut        = $validated['susut'] ?? null;
            $sph->note_berlaku = $validated['note_berlaku'] ?? null;
            $sph->created_by   = $fullName;
            $sph->created_by_id   = $user->id;
            $sph->last_updateby = $user->id;
            $sph->status       = 1;
            $sph->save();

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

        // mapping status code ke teks
        $statusTextMap = [
            1 => 'Menunggu Approval',
            2 => 'Perlu Revisi',
            3 => 'Reject',
            4 => 'Approved',
        ];

        // tambahkan field `workflow` per item dan `pic_name` dari MasterCustomer
        $data = $sphs->map(function($sph) use ($statusTextMap) {
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
                        // panggil generatePdf dan simpan hasilnya
                        $pdfResponse = $this->generatePdf($id);
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

        // Data customers (OAT) – tetap sama
        $customers = DB::table('oat_customer as a')
            ->leftJoin('master_customer as b', 'b.id', '=', 'a.cust_id')
            ->leftJoin('data_trx_sph as c', 'c.comp_name', '=', 'b.name')
            ->where('c.comp_name', $sph->comp_name)
            ->select('a.location', 'a.qty', 'a.price')
            ->get();

        // Pilih view sesuai tipe
        $pdf = Pdf::setOptions([
                'enable_remote'   => true,
                'isRemoteEnabled' => true,
                'dpi'             => 110,
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

        $pdfFileName = 'sph_' . $incomingType . '_' . $sph->kode_sph . '_' . time() . '.pdf';
        $pdfPath = 'sph/' . $pdfFileName;
        Storage::disk('idcloudhost')->put($pdfPath, $pdf->output());

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

    // Download PDF (jika ingin via endpoint/secure)

public function downloadPdf($id)
    {
        $sph = DataTrxSph::findOrFail($id);

        if (!$sph->pdf_file || !Storage::disk('public')->exists($sph->pdf_file)) {
            return response()->json(['error' => 'PDF belum digenerate'], 404);
        }

        return response()->download(storage_path('app/public/' . $sph->pdf_file));
    }

}
