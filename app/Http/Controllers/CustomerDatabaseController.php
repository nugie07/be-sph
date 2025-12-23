<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthValidator;
use App\Models\User;
use Carbon\Carbon;
use App\Helpers\UserSysLogHelper;

class CustomerDatabaseController extends Controller
{
    /**
     * List semua data customer (tidak termasuk yang soft deleted)
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
            $type = $request->get('type', '');
            $status = $request->get('status', '');

            // Build query
            $query = DB::table('master_customer')
                ->whereNull('deleted_at');

            // Apply search filter (search by nama customer only)
            if (!empty($search)) {
                $query->where('name', 'LIKE', '%' . $search . '%');
            }

            // Apply type filter
            if (!empty($type)) {
                $query->where('type', $type);
            }

            // Apply status filter (active/inactive)
            if ($status !== '') {
                $query->where('status', $status);
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
                    'cust_code' => $item->cust_code,
                    'alias' => $item->alias,
                    'type' => $item->type,
                    'name' => $item->name,
                    'address' => $item->address,
                    'pic_name' => $item->pic_name,
                    'pic_contact' => $item->pic_contact,
                    'email' => $item->email,
                    'bill_to' => $item->bill_to,
                    'ship_to' => $item->ship_to,
                    'status' => $item->status,
                    'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i') : null,
                    'updated_at' => $item->updated_at ? Carbon::parse($item->updated_at)->format('Y-m-d H:i') : null
                ];
            });

            // Calculate summary
            $totalActive = DB::table('master_customer')->whereNull('deleted_at')->where('status', 1)->count();
            $totalInactive = DB::table('master_customer')->whereNull('deleted_at')->where('status', 0)->count();
            $totalByType = DB::table('master_customer')->whereNull('deleted_at')->select('type', DB::raw('count(*) as total'))->groupBy('type')->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'CustomerDatabase', 'index');

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
                    'total_by_type' => $totalByType
                ],
                'filters' => [
                    'search' => $search,
                    'type' => $type,
                    'status' => $status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail customer berdasarkan ID
     */
    public function show(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $data = DB::table('master_customer')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $data->id,
                    'cust_code' => $data->cust_code,
                    'alias' => $data->alias,
                    'type' => $data->type,
                    'name' => $data->name,
                    'address' => $data->address,
                    'pic_name' => $data->pic_name,
                    'pic_contact' => $data->pic_contact,
                    'email' => $data->email,
                    'bill_to' => $data->bill_to,
                    'ship_to' => $data->ship_to,
                    'status' => $data->status,
                    'created_at' => $data->created_at ? Carbon::parse($data->created_at)->format('Y-m-d H:i') : null,
                    'updated_at' => $data->updated_at ? Carbon::parse($data->updated_at)->format('Y-m-d H:i') : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer detail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create customer baru
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
                'cust_code' => 'required|string|max:50|unique:master_customer,cust_code',
                'alias' => 'required|string|max:100',
                'type' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'pic_name' => 'nullable|string|max:100',
                'pic_contact' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'bill_to' => 'nullable|string|max:500',
                'ship_to' => 'nullable|string|max:500',
                'status' => 'required|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $id = DB::table('master_customer')->insertGetId([
                'cust_code' => $request->cust_code,
                'alias' => $request->alias,
                'type' => $request->type,
                'name' => $request->name,
                'address' => $request->address,
                'pic_name' => $request->pic_name,
                'pic_contact' => $request->pic_contact,
                'email' => $request->email,
                'bill_to' => $request->bill_to,
                'ship_to' => $request->ship_to,
                'status' => $request->status,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'CustomerDatabase', 'store');

            Log::info('Customer created successfully', [
                'id' => $id,
                'name' => $request->name,
                'cust_code' => $request->cust_code,
                'created_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil dibuat',
                'data' => [
                    'id' => $id,
                    'cust_code' => $request->cust_code,
                    'alias' => $request->alias,
                    'type' => $request->type,
                    'name' => $request->name,
                    'address' => $request->address,
                    'pic_name' => $request->pic_name,
                    'pic_contact' => $request->pic_contact,
                    'email' => $request->email,
                    'bill_to' => $request->bill_to,
                    'ship_to' => $request->ship_to,
                    'status' => $request->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating customer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update customer
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
                'cust_code' => 'required|string|max:50|unique:master_customer,cust_code,' . $id,
                'alias' => 'required|string|max:100',
                'type' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'pic_name' => 'nullable|string|max:100',
                'pic_contact' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'bill_to' => 'nullable|string|max:500',
                'ship_to' => 'nullable|string|max:500',
                'status' => 'required|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if data exists
            $existingData = DB::table('master_customer')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$existingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            DB::table('master_customer')
                ->where('id', $id)
                ->update([
                    'cust_code' => $request->cust_code,
                    'alias' => $request->alias,
                    'type' => $request->type,
                    'name' => $request->name,
                    'address' => $request->address,
                    'pic_name' => $request->pic_name,
                    'pic_contact' => $request->pic_contact,
                    'email' => $request->email,
                    'bill_to' => $request->bill_to,
                    'ship_to' => $request->ship_to,
                    'status' => $request->status,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'CustomerDatabase', 'update');

            Log::info('Customer updated successfully', [
                'id' => $id,
                'name' => $request->name,
                'cust_code' => $request->cust_code,
                'updated_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil diperbarui',
                'data' => [
                    'id' => $id,
                    'cust_code' => $request->cust_code,
                    'alias' => $request->alias,
                    'type' => $request->type,
                    'name' => $request->name,
                    'address' => $request->address,
                    'pic_name' => $request->pic_name,
                    'pic_contact' => $request->pic_contact,
                    'email' => $request->email,
                    'bill_to' => $request->bill_to,
                    'ship_to' => $request->ship_to,
                    'status' => $request->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating customer', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete customer
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
            $existingData = DB::table('master_customer')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$existingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            // Soft delete - update deleted_at
            DB::table('master_customer')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'CustomerDatabase', 'destroy');

            Log::info('Customer soft deleted successfully', [
                'id' => $id,
                'name' => $existingData->name,
                'cust_code' => $existingData->cust_code,
                'deleted_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil dihapus',
                'data' => [
                    'id' => $id,
                    'name' => $existingData->name,
                    'cust_code' => $existingData->cust_code,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error soft deleting customer', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list customer untuk dropdown/select
     */
    public function getCustomerList(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $customers = DB::table('master_customer')
                ->select('id', 'alias', 'name')
                ->whereNull('deleted_at')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data customer list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list OAT customer berdasarkan customer ID
     */
    public function getOatList(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|integer|exists:master_customer,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customerId = $request->customer_id;

            $oatList = DB::table('oat_customer as oc')
                ->leftJoin('master_customer as mc', 'mc.id', '=', 'oc.cust_id')
                ->select([
                    'oc.id',
                    'oc.cust_id',
                    'oc.location',
                    'oc.qty',
                    'oc.oat',
                    'oc.created_at',
                    'oc.updated_at',
                    'oc.deleted_at',
                    'mc.name as customer_name',
                    'mc.alias as customer_alias'
                ])
                ->where('mc.id', $customerId)
                ->whereNull('mc.deleted_at')
                ->whereNull('oc.deleted_at')
                ->orderBy('oc.created_at', 'desc')
                ->get();

            // Get customer info
            $customer = DB::table('master_customer')
                ->select('id', 'name', 'alias')
                ->where('id', $customerId)
                ->whereNull('deleted_at')
                ->first();

            return response()->json([
                'success' => true,
                'customer' => $customer,
                'data' => $oatList,
                'total' => $oatList->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting OAT list', [
                'customer_id' => $request->customer_id ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data OAT list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete OAT customer
     */
    public function deleteOat(Request $request, $id)
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
            // Check if OAT data exists
            $existingData = DB::table('oat_customer')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$existingData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data OAT tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            // Soft delete - update deleted_at
            DB::table('oat_customer')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('OAT customer soft deleted successfully', [
                'id' => $id,
                'cust_id' => $existingData->cust_id,
                'deleted_by' => $user->first_name . ' ' . $user->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OAT customer berhasil dihapus',
                'data' => [
                    'id' => $id,
                    'cust_id' => $existingData->cust_id,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error soft deleting OAT customer', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus OAT customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create OAT customer baru
     */
    public function createOat(Request $request)
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
                'cust_id' => 'required|integer|exists:master_customer,id',
                'location' => 'required|string|max:255',
                'qty' => 'required|string|max:255',
                'oat' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            // Cek apakah customer exists dan tidak dihapus
            $customer = DB::table('master_customer')
                ->where('id', $request->cust_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan'
                ], 404);
            }

            // Buat OAT customer baru
            $oatId = DB::table('oat_customer')->insertGetId([
                'cust_id' => $request->cust_id,
                'location' => $request->location,
                'qty' => $request->qty,
                'oat' => $request->oat,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Ambil data yang baru dibuat
            $newOat = DB::table('oat_customer as oc')
                ->leftJoin('master_customer as mc', 'mc.id', '=', 'oc.cust_id')
                ->select([
                    'oc.id',
                    'oc.cust_id',
                    'oc.location',
                    'oc.qty',
                    'oc.oat',
                    'oc.created_at',
                    'oc.updated_at',
                    'mc.name as customer_name',
                    'mc.alias as customer_alias'
                ])
                ->where('oc.id', $oatId)
                ->first();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'CustomerDatabase', 'createOat');

            $response = [
                'success' => true,
                'message' => 'OAT customer berhasil dibuat',
                'data' => $newOat
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'CustomerDatabase',
                'Create OAT customer',
                'createOat.CustomerDatabaseController',
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

            Log::error('Error creating OAT customer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal membuat OAT customer: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'CustomerDatabase',
                'Error creating OAT customer',
                'createOat.CustomerDatabaseController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }

    /**
     * Update OAT customer berdasarkan ID
     */
    public function updateOat(Request $request, $id)
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
                'cust_id' => 'required|integer|exists:master_customer,id',
                'location' => 'required|string|max:255',
                'qty' => 'required|string|max:255',
                'oat' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            // Cek apakah OAT data exists
            $existingOat = DB::table('oat_customer')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$existingOat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data OAT tidak ditemukan'
                ], 404);
            }

            // Cek apakah customer exists dan tidak dihapus
            $customer = DB::table('master_customer')
                ->where('id', $request->cust_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan'
                ], 404);
            }

            // Update OAT customer
            DB::table('oat_customer')
                ->where('id', $id)
                ->update([
                    'cust_id' => $request->cust_id,
                    'location' => $request->location,
                    'qty' => $request->qty,
                    'oat' => $request->oat,
                    'updated_at' => now()
                ]);

            // Ambil data yang sudah diupdate
            $updatedOat = DB::table('oat_customer as oc')
                ->leftJoin('master_customer as mc', 'mc.id', '=', 'oc.cust_id')
                ->select([
                    'oc.id',
                    'oc.cust_id',
                    'oc.location',
                    'oc.qty',
                    'oc.oat',
                    'oc.created_at',
                    'oc.updated_at',
                    'mc.name as customer_name',
                    'mc.alias as customer_alias'
                ])
                ->where('oc.id', $id)
                ->first();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'CustomerDatabase', 'updateOat');

            $response = [
                'success' => true,
                'message' => 'OAT customer berhasil diupdate',
                'data' => $updatedOat
            ];

            // Capture log menggunakan helper SystemLog
            log_system(
                'CustomerDatabase',
                'Update OAT customer',
                'updateOat.CustomerDatabaseController',
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

            Log::error('Error updating OAT customer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
                'oat_id' => $id
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengupdate OAT customer: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'CustomerDatabase',
                'Error updating OAT customer',
                'updateOat.CustomerDatabaseController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }
}
