<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterWilayah;
use App\Models\User;
use App\Helpers\AuthValidator;
use App\Helpers\UserSysLogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MasterWilayahController extends Controller
{
    /**
     * Get list wilayah dengan pagination dan search
     */
    public function getList(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search', '');

            // Base query
            $query = MasterWilayah::query();

            // Search filter by nama
            if (!empty($search)) {
                $query->where('nama', 'LIKE', '%' . $search . '%');
            }

            // Hitung total records
            $totalCount = $query->count();

            // Ambil data dengan pagination
            $wilayah = $query->orderBy('nama', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterWilayah', 'getList');

            $response = [
                'success' => true,
                'data' => $wilayah,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => $page < ceil($totalCount / $perPage),
                    'has_prev_page' => $page > 1
                ]
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'List wilayah',
                'getList.MasterWilayahController',
                $request->all(),
                $response
            );

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error getting wilayah list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengambil data wilayah: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Error getting wilayah list',
                'getList.MasterWilayahController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Create wilayah baru
     */
    public function createWilayah(Request $request)
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
                'nama' => 'required|string|max:255',
                'value' => 'required|string|max:255',
                'status' => 'boolean'
            ]);

            DB::beginTransaction();

            // Cek apakah nama wilayah sudah ada
            $existingWilayah = MasterWilayah::where('nama', $request->nama)->first();
            if ($existingWilayah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama wilayah sudah ada'
                ], 400);
            }

            // Buat wilayah baru
            $wilayah = MasterWilayah::create([
                'nama' => $request->nama,
                'value' => $request->value,
                'status' => $request->status ?? true
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterWilayah', 'createWilayah');

            $response = [
                'success' => true,
                'message' => 'Wilayah berhasil dibuat',
                'data' => [
                    'id' => $wilayah->id,
                    'nama' => $wilayah->nama,
                    'value' => $wilayah->value,
                    'status' => $wilayah->status,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                    'created_by' => $fullName
                ]
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Create wilayah',
                'createWilayah.MasterWilayahController',
                $request->all(),
                $response
            );

            return response()->json($response, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error creating wilayah', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal membuat wilayah: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Error creating wilayah',
                'createWilayah.MasterWilayahController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Update wilayah berdasarkan ID
     */
    public function updateWilayah(Request $request, $id)
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
                'nama' => 'required|string|max:255',
                'value' => 'required|string|max:255',
                'status' => 'boolean'
            ]);

            DB::beginTransaction();

            // Cari wilayah yang akan diupdate
            $wilayah = MasterWilayah::findOrFail($id);

            // Cek apakah nama wilayah sudah ada (kecuali untuk record yang sama)
            $existingWilayah = MasterWilayah::where('nama', $request->nama)
                ->where('id', '!=', $id)
                ->first();

            if ($existingWilayah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama wilayah sudah ada'
                ], 400);
            }

            // Update wilayah
            $wilayah->update([
                'nama' => $request->nama,
                'value' => $request->value,
                'status' => $request->status
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterWilayah', 'updateWilayah');

            $response = [
                'success' => true,
                'message' => 'Wilayah berhasil diupdate',
                'data' => [
                    'id' => $wilayah->id,
                    'nama' => $wilayah->nama,
                    'value' => $wilayah->value,
                    'status' => $wilayah->status,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                    'updated_by' => $fullName
                ]
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Update wilayah',
                'updateWilayah.MasterWilayahController',
                $request->all(),
                $response
            );

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error updating wilayah', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
                'wilayah_id' => $id
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengupdate wilayah: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Error updating wilayah',
                'updateWilayah.MasterWilayahController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Delete wilayah berdasarkan ID
     */
    public function deleteWilayah(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        $fullName = "{$user->first_name} {$user->last_name}";

        try {
            DB::beginTransaction();

            // Cari wilayah yang akan dihapus
            $wilayah = MasterWilayah::findOrFail($id);

            // Simpan data sebelum dihapus untuk log
            $wilayahData = [
                'id' => $wilayah->id,
                'nama' => $wilayah->nama,
                'value' => $wilayah->value,
                'status' => $wilayah->status
            ];

            // Hapus wilayah (hard delete)
            $wilayah->delete();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterWilayah', 'deleteWilayah');

            $response = [
                'success' => true,
                'message' => 'Wilayah berhasil dihapus',
                'data' => [
                    'deleted_wilayah' => $wilayahData,
                    'deleted_at' => now()->format('Y-m-d H:i:s'),
                    'deleted_by' => $fullName
                ]
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Delete wilayah',
                'deleteWilayah.MasterWilayahController',
                ['wilayah_id' => $id],
                $response
            );

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting wilayah', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'wilayah_id' => $id
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal menghapus wilayah: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Error deleting wilayah',
                'deleteWilayah.MasterWilayahController',
                ['wilayah_id' => $id],
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Get wilayah untuk request/dropdown
     */
    public function wilayahRequest(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Ambil semua wilayah yang aktif
            $wilayah = MasterWilayah::where('status', true)
                ->select('id', 'nama as code', 'value')
                ->orderBy('nama', 'asc')
                ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'MasterWilayah', 'wilayahRequest');

            $response = [
                'success' => true,
                'data' => $wilayah
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Get wilayah request',
                'wilayahRequest.MasterWilayahController',
                $request->all(),
                $response
            );

            return response()->json($wilayah);

        } catch (\Exception $e) {
            Log::error('Error getting wilayah request', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengambil data wilayah: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'MasterWilayah',
                'Error getting wilayah request',
                'wilayahRequest.MasterWilayahController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }
}
