<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterLov;
use App\Helpers\AuthValidator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\UserSysLogHelper;

class MasterLovController extends Controller
{


    public function getChildren(Request $request)
    {

        $request->validate([
            'parent_code' => 'required|string',
        ]);

        // 1. Cari parent_id dari code parent
        $parent = MasterLov::where('code', $request->parent_code)
                    ->whereNull('parent_id')
                    ->first();

        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }

        // 2. Ambil semua child dengan parent_id
        $children = MasterLov::where('parent_id', $parent->id)
                    ->get(['id', 'code', 'value']);

        return response()->json($children);
    }

    /**
     * Get list lokasi berdasarkan code 'LOKASI_MASTER'
     */
    public function getListLokasi(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search', '');

            // 1. Cari parent_id dari code 'LOKASI_MASTER'
            $parent = MasterLov::where('code', 'LOKASI_MASTER')
                        ->whereNull('parent_id')
                        ->whereNull('deleted_at')
                        ->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Master lokasi tidak ditemukan'
                ], 404);
            }

            // 2. Query untuk child dengan parent_id yang didapat
            $query = MasterLov::where('parent_id', $parent->id)
                        ->whereNull('deleted_at');

            // 3. Tambahkan search filter jika ada
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('value', 'LIKE', '%' . $search . '%')
                      ->orWhere('code', 'LIKE', '%' . $search . '%');
                });
            }

            // 4. Hitung total records
            $totalCount = $query->count();

            // 5. Ambil data dengan pagination
            $lokasiList = $query->orderBy('value', 'asc')
                            ->skip(($page - 1) * $perPage)
                            ->take($perPage)
                            ->get([
                                'id',
                                'code',
                                'value',
                                'parent_id',
                                'created_at',
                                'updated_at',
                                'deleted_at'
                            ]);

            // Debug logging
            Log::info('Lokasi search debug', [
                'search_term' => $search,
                'parent_id' => $parent->id,
                'total_count' => $totalCount,
                'results_count' => $lokasiList->count(),
                'query_sql' => $query->toSql(),
                'query_bindings' => $query->getBindings()
            ]);

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterLov', 'getListLokasi');

            return response()->json([
                'success' => true,
                'data' => $lokasiList,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => $page < ceil($totalCount / $perPage),
                    'has_prev_page' => $page > 1
                ],
                'parent_info' => [
                    'id' => $parent->id,
                    'code' => $parent->code,
                    'value' => $parent->value
                ],
                'filters' => [
                    'search' => $search
                ],
                'debug_info' => [
                    'search_term' => $search,
                    'parent_id' => $parent->id,
                    'query_sql' => $query->toSql(),
                    'query_bindings' => $query->getBindings()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting lokasi list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete lokasi berdasarkan ID
     */
    public function deleteLokasi(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        $fullName = "{$user->first_name} {$user->last_name}";

        try {
            DB::beginTransaction();

            $lokasi = MasterLov::findOrFail($id);

            // Validasi bahwa ini adalah child dari LOKASI_MASTER
            $parent = MasterLov::where('code', 'LOKASI_MASTER')
                        ->whereNull('parent_id')
                        ->whereNull('deleted_at')
                        ->first();

            if (!$parent || $lokasi->parent_id != $parent->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi tidak valid'
                ], 400);
            }

            // Soft delete dengan mengupdate deleted_at
            $lokasi->deleted_at = now();
            $lokasi->save();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterLov', 'deleteLokasi');

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil dihapus',
                'data' => [
                    'id' => $lokasi->id,
                    'code' => $lokasi->code,
                    'value' => $lokasi->value,
                    'deleted_by' => $fullName,
                    'deleted_at' => $lokasi->deleted_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting lokasi', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'lokasi_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create lokasi baru
     */
    public function createLokasi(Request $request)
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
                'code' => 'required|string|max:100',
                'value' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            // 1. Cari parent_id dari code 'LOKASI_MASTER'
            $parent = MasterLov::where('code', 'LOKASI_MASTER')
                        ->whereNull('parent_id')
                        ->whereNull('deleted_at')
                        ->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Master lokasi tidak ditemukan'
                ], 404);
            }

            // 2. Validasi code tidak boleh duplikat untuk parent yang sama
            $existingCode = MasterLov::where('parent_id', $parent->id)
                            ->where('code', $request->code)
                            ->whereNull('deleted_at')
                            ->first();

            if ($existingCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode lokasi sudah ada'
                ], 400);
            }



            // 3. Create lokasi baru
            $lokasi = MasterLov::create([
                'code' => $request->code,
                'value' => $request->value,
                'parent_id' => $parent->id
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterLov', 'createLokasi');

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil dibuat',
                'data' => [
                    'id' => $lokasi->id,
                    'code' => $lokasi->code,
                    'value' => $lokasi->value,
                    'parent_id' => $lokasi->parent_id,
                    'created_at' => $lokasi->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $lokasi->updated_at->format('Y-m-d H:i:s'),
                    'created_by' => $fullName
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error creating lokasi', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update lokasi berdasarkan ID
     */
    public function updateLokasi(Request $request, $id)
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
                'code' => 'required|string|max:100',
                'value' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            // 1. Cari lokasi yang akan diupdate
            $lokasi = MasterLov::findOrFail($id);

            // 2. Validasi bahwa ini adalah child dari LOKASI_MASTER
            $parent = MasterLov::where('code', 'LOKASI_MASTER')
                        ->whereNull('parent_id')
                        ->whereNull('deleted_at')
                        ->first();

            if (!$parent || $lokasi->parent_id != $parent->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi tidak valid'
                ], 400);
            }

            // 3. Validasi code tidak boleh duplikat (kecuali untuk record yang sama)
            $existingCode = MasterLov::where('parent_id', $parent->id)
                            ->where('code', $request->code)
                            ->where('id', '!=', $id)
                            ->whereNull('deleted_at')
                            ->first();

            if ($existingCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode lokasi sudah ada'
                ], 400);
            }

            // 4. Update lokasi
            $lokasi->update([
                'code' => $request->code,
                'value' => $request->value
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterLov', 'updateLokasi');

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil diupdate',
                'data' => [
                    'id' => $lokasi->id,
                    'code' => $lokasi->code,
                    'value' => $lokasi->value,
                    'parent_id' => $lokasi->parent_id,
                    'created_at' => $lokasi->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $lokasi->updated_at->format('Y-m-d H:i:s'),
                    'updated_by' => $fullName
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating lokasi', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'lokasi_id' => $id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate lokasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
