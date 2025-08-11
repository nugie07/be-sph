<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthValidator;
use App\Models\User;
use Carbon\Carbon;

class SupplierTransporterController extends Controller
{
    /**
     * List semua data supplier/transporter (tidak termasuk yang soft deleted)
     */
    public function index(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Get query parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search', '');
            $category = $request->get('category', '');
            $status = $request->get('status', '');
            $filterStatus = $request->get('filter_status', ''); // 1=Supplier, 2=Transporter

            // Build query
            $query = DB::table('data_supplier_transporter')
                ->whereNull('deleted_at');

            // Apply search filter (search by nama only) - tidak terpengaruh filter lain
            if (!empty($search)) {
                $query->where('nama', 'LIKE', '%' . $search . '%');
            }

            // Apply category filter
            if (!empty($category)) {
                $query->where('category', $category);
            }

            // Apply status filter (active/inactive)
            if ($status !== '') {
                $query->where('status', $status);
            }

            // Apply filter_status filter (1=Supplier, 2=Transporter)
            if (!empty($filterStatus)) {
                $query->where('category', $filterStatus);
            }

            // Get total count for pagination
            $totalCount = $query->count();

            // Get paginated results
            $data = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Format data
            $formattedData = $data->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tipe' => $item->tipe,
                    'format' => $item->format,
                    'alias' => $item->alias,
                    'nama' => $item->nama,
                    'pic' => $item->pic,
                    'contact_no' => $item->contact_no,
                    'email' => $item->email,
                    'address' => $item->address,
                    'status' => $item->status,
                    'category' => $item->category,
                    'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : null,
                    'updated_at' => $item->updated_at ? Carbon::parse($item->updated_at)->format('Y-m-d H:i') : null
                ];
            });

            // Calculate summary
            $totalActive = DB::table('data_supplier_transporter')->whereNull('deleted_at')->where('status', 1)->count();
            $totalInactive = DB::table('data_supplier_transporter')->whereNull('deleted_at')->where('status', 0)->count();
            $totalSupplier = DB::table('data_supplier_transporter')->whereNull('deleted_at')->where('category', 1)->count();
            $totalTransporter = DB::table('data_supplier_transporter')->whereNull('deleted_at')->where('category', 2)->count();

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => $page < ceil($totalCount / $perPage),
                    'has_prev_page' => $page > 1
                ],
                'summary' => [
                    'total_active' => $totalActive,
                    'total_inactive' => $totalInactive,
                    'total_supplier' => $totalSupplier,
                    'total_transporter' => $totalTransporter
                ],
                'filters' => [
                    'search' => $search,
                    'category' => $category,
                    'status' => $status,
                    'filter_status' => $filterStatus
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier/transporter list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data supplier/transporter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail supplier/transporter berdasarkan ID
     */
    public function show(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $data = DB::table('data_supplier_transporter')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data supplier/transporter tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $data->id,
                    'tipe' => $data->tipe,
                    'format' => $data->format,
                    'alias' => $data->alias,
                    'nama' => $data->nama,
                    'pic' => $data->pic,
                    'contact_no' => $data->contact_no,
                    'email' => $data->email,
                    'address' => $data->address,
                    'status' => $data->status,
                    'category' => $data->category,
                    'created_at' => $data->created_at ? Carbon::parse($data->created_at)->format('Y-m-d H:i') : null,
                    'updated_at' => $data->updated_at ? Carbon::parse($data->updated_at)->format('Y-m-d H:i') : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting supplier/transporter detail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail supplier/transporter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create supplier/transporter baru
     */
    public function store(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'tipe' => 'required|string|max:100',
                'format' => 'required|string|max:100',
                'alias' => 'required|string|max:100',
                'nama' => 'required|string|max:255',
                'pic' => 'required|string|max:100',
                'contact_no' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'status' => 'required|in:0,1',
                'category' => 'required|in:1,2' // 1=Supplier, 2=Transporter
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $id = DB::table('data_supplier_transporter')->insertGetId([
                'tipe' => $request->tipe,
                'format' => $request->format,
                'alias' => $request->alias,
                'nama' => $request->nama,
                'pic' => $request->pic,
                'contact_no' => $request->contact_no,
                'email' => $request->email,
                'address' => $request->address,
                'status' => $request->status,
                'category' => $request->category,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info('Supplier/Transporter created successfully', [
                'id' => $id,
                'nama' => $request->nama,
                'category' => $request->category,
                'created_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier/Transporter berhasil dibuat',
                'data' => [
                    'id' => $id,
                    'tipe' => $request->tipe,
                    'format' => $request->format,
                    'alias' => $request->alias,
                    'nama' => $request->nama,
                    'pic' => $request->pic,
                    'contact_no' => $request->contact_no,
                    'email' => $request->email,
                    'address' => $request->address,
                    'status' => $request->status,
                    'category' => $request->category
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating supplier/transporter', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat supplier/transporter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update supplier/transporter
     */
    public function update(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'tipe' => 'required|string|max:100',
                'format' => 'required|string|max:100',
                'alias' => 'required|string|max:100',
                'nama' => 'required|string|max:255',
                'pic' => 'required|string|max:100',
                'contact_no' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'status' => 'required|in:0,1',
                'category' => 'required|in:1,2'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if data exists
            $existingData = DB::table('data_supplier_transporter')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$existingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data supplier/transporter tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            DB::table('data_supplier_transporter')
                ->where('id', $id)
                ->update([
                    'tipe' => $request->tipe,
                    'format' => $request->format,
                    'alias' => $request->alias,
                    'nama' => $request->nama,
                    'pic' => $request->pic,
                    'contact_no' => $request->contact_no,
                    'email' => $request->email,
                    'address' => $request->address,
                    'status' => $request->status,
                    'category' => $request->category,
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Supplier/Transporter updated successfully', [
                'id' => $id,
                'nama' => $request->nama,
                'updated_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier/Transporter berhasil diperbarui',
                'data' => [
                    'id' => $id,
                    'tipe' => $request->tipe,
                    'format' => $request->format,
                    'alias' => $request->alias,
                    'nama' => $request->nama,
                    'pic' => $request->pic,
                    'contact_no' => $request->contact_no,
                    'email' => $request->email,
                    'address' => $request->address,
                    'status' => $request->status,
                    'category' => $request->category
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating supplier/transporter', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui supplier/transporter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete supplier/transporter
     */
    public function destroy(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $user = User::find($result['id']);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 401);
        }

        try {
            // Check if data exists
            $existingData = DB::table('data_supplier_transporter')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$existingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data supplier/transporter tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            // Soft delete - update deleted_at
            DB::table('data_supplier_transporter')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Supplier/Transporter soft deleted successfully', [
                'id' => $id,
                'nama' => $existingData->nama,
                'deleted_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier/Transporter berhasil dihapus',
                'data' => [
                    'id' => $id,
                    'nama' => $existingData->nama,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error soft deleting supplier/transporter', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus supplier/transporter: ' . $e->getMessage()
            ], 500);
        }
    }
}
