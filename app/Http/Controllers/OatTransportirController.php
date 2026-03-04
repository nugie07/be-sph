<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthValidator;
use Carbon\Carbon;
use App\Helpers\UserSysLogHelper;

class OatTransportirController extends Controller
{
    /**
     * List vendor untuk dropdown Select Vendor (dari data_supplier_transporter, transporter)
     */
    public function getVendors(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $vendors = DB::table('data_supplier_transporter')
                ->whereNull('deleted_at')
                ->where('status', 1)
                ->where('category', 2) // 2 = Transporter
                ->orderBy('nama')
                ->get(['id', 'nama', 'alias']);

            $list = $vendors->map(function ($v) {
                return [
                    'id' => $v->id,
                    'nama' => $v->nama,
                    'alias' => $v->alias ?? $v->nama,
                ];
            });

            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'getVendors');

            return response()->json([
                'success' => true,
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            Log::error('OatTransportir getVendors error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil list vendor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List oat_volume by vendor_id (wajib kirim vendor_id dari Select Vendor)
     */
    public function index(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $vendorId = $request->get('vendor_id');
        if (empty($vendorId)) {
            return response()->json([
                'success' => false,
                'message' => 'vendor_id wajib dikirim (pilih vendor dulu dari Select Vendor).',
            ], 422);
        }

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            $query = DB::table('oat_volume')
                ->leftJoin('master_wilayah as mw', 'oat_volume.wilayah_id', '=', 'mw.id')
                ->where('oat_volume.vendor_id', $vendorId)
                ->select(
                    'oat_volume.id',
                    'oat_volume.wilayah_id',
                    'oat_volume.vendor_id',
                    'oat_volume.name',
                    'oat_volume.oat',
                    'oat_volume.value',
                    'oat_volume.created_at',
                    'oat_volume.updated_at',
                    'mw.nama as wilayah_nama'
                )
                ->orderBy('oat_volume.created_at', 'desc');

            $totalCount = $query->count();
            $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

            $data = $items->map(function ($row) {
                return [
                    'id' => $row->id,
                    'wilayah_id' => $row->wilayah_id,
                    'vendor_id' => $row->vendor_id,
                    'name' => $row->name,
                    'oat' => $row->oat ?? null,
                    'value' => $row->value,
                    'wilayah' => $row->wilayah_nama ?? null,
                    'created_at' => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : null,
                    'updated_at' => $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : null,
                ];
            });

            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'index');

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => (int) ceil($totalCount / $perPage),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('OatTransportir index error', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data OAT: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail satu oat_volume by id
     */
    public function show(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $row = DB::table('oat_volume')
                ->leftJoin('master_wilayah as mw', 'oat_volume.wilayah_id', '=', 'mw.id')
                ->where('oat_volume.id', $id)
                ->select(
                    'oat_volume.id',
                    'oat_volume.wilayah_id',
                    'oat_volume.vendor_id',
                    'oat_volume.name',
                    'oat_volume.oat',
                    'oat_volume.value',
                    'oat_volume.created_at',
                    'oat_volume.updated_at',
                    'mw.nama as wilayah_nama'
                )
                ->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data OAT tidak ditemukan',
                ], 404);
            }

            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'show');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $row->id,
                    'wilayah_id' => $row->wilayah_id,
                    'vendor_id' => $row->vendor_id,
                    'name' => $row->name,
                    'oat' => $row->oat ?? null,
                    'value' => $row->value,
                    'wilayah' => $row->wilayah_nama ?? null,
                    'created_at' => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : null,
                    'updated_at' => $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('OatTransportir show error', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail OAT: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create oat_volume
     */
    public function store(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer',
            'wilayah_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'oat' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $id = DB::table('oat_volume')->insertGetId([
                'vendor_id' => $request->vendor_id,
                'wilayah_id' => $request->wilayah_id,
                'name' => $request->name,
                'oat' => $request->oat ?? null,
                'value' => $request->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'store');

            $row = DB::table('oat_volume')->where('id', $id)->first();
            return response()->json([
                'success' => true,
                'message' => 'OAT berhasil dibuat',
                'data' => [
                    'id' => (int) $id,
                    'wilayah_id' => $row->wilayah_id,
                    'vendor_id' => $row->vendor_id,
                    'name' => $row->name,
                    'oat' => $row->oat ?? null,
                    'value' => $row->value,
                    'created_at' => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($row->updated_at)->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('OatTransportir store error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat OAT: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update oat_volume
     */
    public function update(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer',
            'wilayah_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'oat' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exists = DB::table('oat_volume')->where('id', $id)->first();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Data OAT tidak ditemukan',
            ], 404);
        }

        try {
            DB::table('oat_volume')->where('id', $id)->update([
                'vendor_id' => $request->vendor_id,
                'wilayah_id' => $request->wilayah_id,
                'name' => $request->name,
                'oat' => $request->oat ?? null,
                'value' => $request->value,
                'updated_at' => now(),
            ]);

            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'update');

            $row = DB::table('oat_volume')->where('id', $id)->first();
            return response()->json([
                'success' => true,
                'message' => 'OAT berhasil diperbarui',
                'data' => [
                    'id' => (int) $id,
                    'wilayah_id' => $row->wilayah_id,
                    'vendor_id' => $row->vendor_id,
                    'name' => $row->name,
                    'oat' => $row->oat ?? null,
                    'value' => $row->value,
                    'created_at' => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($row->updated_at)->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('OatTransportir update error', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui OAT: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update oat_volume by id — hanya field name, oat, value (tanpa vendor_id/wilayah_id)
     */
    public function updatePartial(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'oat' => 'nullable|string|max:255',
            'value' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exists = DB::table('oat_volume')->where('id', $id)->first();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Data OAT tidak ditemukan',
            ], 404);
        }

        $payload = ['updated_at' => now()];
        if ($request->has('name')) {
            $payload['name'] = $request->name;
        }
        if ($request->has('oat')) {
            $payload['oat'] = $request->oat;
        }
        if ($request->has('value')) {
            $payload['value'] = $request->value;
        }

        if (count($payload) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Kirim minimal satu field: name, oat, atau value.',
            ], 422);
        }

        try {
            DB::table('oat_volume')->where('id', $id)->update($payload);
            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'updatePartial');

            $row = DB::table('oat_volume')->where('id', $id)->first();
            return response()->json([
                'success' => true,
                'message' => 'OAT berhasil diperbarui',
                'data' => [
                    'id' => (int) $id,
                    'wilayah_id' => $row->wilayah_id,
                    'vendor_id' => $row->vendor_id,
                    'name' => $row->name,
                    'oat' => $row->oat ?? null,
                    'value' => $row->value ?? null,
                    'created_at' => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($row->updated_at)->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('OatTransportir updatePartial error', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui OAT: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete oat_volume (hard delete)
     */
    public function destroy(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $row = DB::table('oat_volume')->where('id', $id)->first();
        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Data OAT tidak ditemukan',
            ], 404);
        }

        try {
            DB::table('oat_volume')->where('id', $id)->delete();
            UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'destroy');
            return response()->json([
                'success' => true,
                'message' => 'OAT berhasil dihapus',
                'data' => ['id' => (int) $id],
            ]);
        } catch (\Exception $e) {
            Log::error('OatTransportir destroy error', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus OAT: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 1. Get list lokasi by vendor_id (untuk dropdown/step 1)
     */
    public function getOatLokasi(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $vendorId = $request->get('vendor_id');
        if (empty($vendorId)) {
            return response()->json([
                'success' => false,
                'message' => 'vendor_id wajib dikirim.',
            ], 422);
        }

        $query = DB::table('oat_volume')
            ->where('vendor_id', $vendorId);

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        // Distinct per (name, wilayah_id). Response name saja untuk Delivery To (wilayah tidak ditampilkan)
        $data = $query
            ->select(DB::raw('MIN(id) as id'), 'name', 'wilayah_id')
            ->groupBy('name', 'wilayah_id')
            ->orderBy('name')
            ->get();

        $list = $data->map(fn ($row) => [
            'id' => (int) $row->id,
            'name' => $row->name,
            'wilayah_id' => $row->wilayah_id,
        ]);

        UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'getOatLokasi');
        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * 2. Get list oat (qty) by vendor_id + name dari step 1
     */
    public function getOatqty(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $vendorId = $request->get('vendor_id');
        $name = $request->get('name');
        if (empty($vendorId) || $name === null || $name === '') {
            return response()->json([
                'success' => false,
                'message' => 'vendor_id dan name wajib dikirim.',
            ], 422);
        }

        $data = DB::table('oat_volume')
            ->where('vendor_id', $vendorId)
            ->where('name', $name)
            ->select('id', 'name', 'oat')
            ->orderBy('id')
            ->get();

        $list = $data->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'oat' => $row->oat ?? null,
        ]);

        UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'getOatqty');
        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * 3. Get value by id (dari step 2)
     */
    public function getOatValue(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $row = DB::table('oat_volume')
            ->where('id', $id)
            ->select('id', 'value')
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        UserSysLogHelper::logFromAuth($result, 'OatTransportir', 'getOatValue');
        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $row->id,
                'value' => $row->value ?? null,
            ],
        ]);
    }
}
