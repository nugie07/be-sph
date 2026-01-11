<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\AuthValidator;
use App\Helpers\UserSysLogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    /**
     * List users dengan search dan pagination
     */
    public function index(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search', '');

            // Base query untuk count (tanpa GROUP BY)
            $countQuery = DB::table('users as u')
                ->where('u.status', 1)
                ->whereNull('u.deleted_at');

            // Search filter untuk count
            if (!empty($search)) {
                $countQuery->where(DB::raw("CONCAT(u.first_name, ' ', u.last_name)"), 'LIKE', '%' . $search . '%');
            }

            // Hitung total records
            $totalCount = $countQuery->count();

            // Base query dengan GROUP_CONCAT untuk roles
            $query = DB::table('users as u')
                ->leftJoin('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
                ->select([
                    'u.id',
                    'u.first_name',
                    'u.last_name',
                    DB::raw("GROUP_CONCAT(r.name SEPARATOR ', ') as rolename"),
                    'u.email',
                    'u.status',
                    'u.created_at',
                    'u.updated_at',
                    'u.deleted_at',
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as full_name")
                ])
                ->where('u.status', 1)
                ->whereNull('u.deleted_at')
                ->groupBy('u.id');

            // Search filter - gabungan first_name dan last_name
            if (!empty($search)) {
                $query->where(DB::raw("CONCAT(u.first_name, ' ', u.last_name)"), 'LIKE', '%' . $search . '%');
            }

            // Ambil data dengan pagination
            $users = $query->orderBy('u.first_name', 'asc')
                        ->skip(($page - 1) * $perPage)
                        ->take($perPage)
                        ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'index');

            return response()->json([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next_page' => $page < ceil($totalCount / $perPage),
                    'has_prev_page' => $page > 1
                ],
                'filters' => [
                    'search' => $search
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting users list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show user detail berdasarkan ID
     */
    public function show(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $user = DB::table('users as u')
                ->leftJoin('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
                ->select([
                    'u.id',
                    'u.first_name',
                    'u.last_name',
                    'r.name as rolename',
                    'r.id as role_id',
                    'u.email',
                    'u.status',
                    'u.created_at',
                    'u.updated_at',
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) as full_name")
                ])
                ->where('u.id', $id)
                ->whereNull('u.deleted_at')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'show');

            return response()->json([
                'success' => true,
                'data' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting user detail', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create user baru
     */
    public function store(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $currentUser = User::find($result['id']);
        $fullName = "{$currentUser->first_name} {$currentUser->last_name}";

        try {
            // Validasi input
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'status' => 'required|in:0,1'
            ]);

            DB::beginTransaction();

            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => $request->status
            ]);

            // Assign role
            $role = Role::findById($request->role_id);
            $user->assignRole($role);

            DB::commit();

            // Kirim email welcome ke user baru
            try {
                $emailData = [
                    'fullname' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'username' => $user->email, // Username sama dengan email
                    'password' => $request->password, // Password asli sebelum di-hash
                    'role' => $role->name // Nama role yang di-assign
                ];

                Mail::to($user->email)->send(new \App\Mail\NewUserMail($emailData));

                Log::info('Welcome email sent successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
                // Tidak throw exception agar tidak mengganggu proses create user
            }

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'store');

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dibuat',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role_name' => $role->name,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
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

            Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['password'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user berdasarkan ID
     */
    public function update(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $currentUser = User::find($result['id']);
        $fullName = "{$currentUser->first_name} {$currentUser->last_name}";

        try {
            // Validasi input
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'status' => 'required|in:0,1'
            ]);

            DB::beginTransaction();

            $user = User::findOrFail($id);

            // Data yang akan diupdate
            $updateData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'status' => $request->status
            ];

            // Update password jika ada perubahan
            if (!empty($request->password)) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Update user data
            $user->update($updateData);

            // Update role jika berbeda
            $currentRole = $user->roles->first();
            $newRole = Role::findById($request->role_id);

            if (!$currentRole || $currentRole->id != $request->role_id) {
                // Remove existing roles
                $user->syncRoles([]);
                // Assign new role
                $user->assignRole($newRole);
            }

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'update');

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diupdate',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role_name' => $newRole->name,
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                    'updated_by' => $fullName,
                    'password_changed' => !empty($request->password)
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

            Log::error('Error updating user', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $id,
                'request_data' => $request->except(['password'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete user berdasarkan ID
     */
    public function destroy(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $currentUser = User::find($result['id']);
        $fullName = "{$currentUser->first_name} {$currentUser->last_name}";

        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);

            // Validasi tidak bisa delete diri sendiri
            if ($user->id == $result['id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus akun sendiri'
                ], 400);
            }

            // Soft delete dengan mengupdate deleted_at
            $user->deleted_at = now();
            $user->save();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'destroy');

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'deleted_by' => $fullName,
                    'deleted_at' => $user->deleted_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting user', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list roles untuk dropdown
     */
    public function getRoles(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $roles = Role::select('id', 'name')->orderBy('name', 'asc')->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'getRoles');

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting roles list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list permissions untuk dropdown
     */
    public function getPermissions(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $permissions = Permission::select('id', 'name', 'guard_name', 'created_at', 'updated_at')
                            ->orderBy('name', 'asc')
                            ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'getPermissions');

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting permissions list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions by role ID dengan status assigned
     */
    public function getPermissionsByRole(Request $request, $roleId)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Validasi role exists
            $role = Role::find($roleId);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak ditemukan'
                ], 404);
            }

            // Query langsung dengan parameter binding yang jelas
            $sql = "
                SELECT
                  p.*,
                  rhp.role_id,
                  r.name AS role_name,
                  CASE WHEN rhp.role_id IS NULL THEN 0 ELSE 1 END AS is_assigned
                FROM permissions p
                LEFT JOIN role_has_permissions rhp
                  ON rhp.permission_id = p.id
                 AND rhp.role_id = ?
                LEFT JOIN roles r
                  ON r.id = rhp.role_id
                ORDER BY p.id ASC
            ";

            $permissions = collect(DB::select($sql, [$roleId]));

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'getPermissionsByRole');

            return response()->json([
                'success' => true,
                'data' => $permissions,
                'role_info' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting permissions by role', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'role_id' => $roleId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create role baru
     */
    public function createRole(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $currentUser = User::find($result['id']);
        $fullName = "{$currentUser->first_name} {$currentUser->last_name}";

        try {
            // Validasi input
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'guard_name' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            // Create role
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'createRole');

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil dibuat',
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'created_at' => $role->created_at->format('Y-m-d H:i:s'),
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

            Log::error('Error creating role', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions by user ID dengan grouping berdasarkan struktur menu
     */
    public function getUserPermissions(Request $request, $userId = null)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Jika tidak ada userId, gunakan user yang sedang login
            $targetUserId = $userId ?? $result['id'];

            // Get user dengan role
            $user = User::with('roles')->find($targetUserId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Get semua permissions yang dimiliki user (melalui role)
            $userPermissions = DB::table('permissions as p')
                ->join('role_has_permissions as rhp', 'rhp.permission_id', '=', 'p.id')
                ->join('model_has_roles as mhr', 'mhr.role_id', '=', 'rhp.role_id')
                ->where('mhr.model_id', $targetUserId)
                ->where('mhr.model_type', User::class)
                ->pluck('p.name')
                ->toArray();

            // Get semua permissions yang ada di sistem
            $allPermissions = DB::table('permissions')
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();

            // Group permissions berdasarkan struktur
            $groupedPermissions = $this->groupPermissionsByStructure($allPermissions, $userPermissions);

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'getUserPermissions');

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $targetUserId,
                    'user_name' => $user->first_name . ' ' . $user->last_name,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $groupedPermissions,
                    'total_permissions' => count($allPermissions),
                    'user_permissions_count' => count($userPermissions)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting user permissions', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $targetUserId ?? 'current_user'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil permissions user: ' . $e->getMessage()
            ], 500);
        }
    }

        /**
     * Group permissions berdasarkan struktur menu
     */
    private function groupPermissionsByStructure($allPermissions, $userPermissions)
    {
        $grouped = [];

        // Identifikasi main menus (yang hanya punya 2 segment)
        $mainMenus = [];
        foreach ($allPermissions as $permission) {
            $segments = explode('.', $permission);
            if (count($segments) == 2) {
                $mainMenus[] = $permission;
            }
        }

        // Process setiap main menu
        foreach ($mainMenus as $mainMenu) {
            $mainMenuSegments = explode('.', $mainMenu);
            $mainPrefix = $mainMenuSegments[0];

            // Cek apakah main menu ini punya sub menus
            $subMenus = [];
            foreach ($allPermissions as $permission) {
                $segments = explode('.', $permission);
                if (count($segments) == 3 && $segments[0] == $mainPrefix) {
                    $subMenus[] = $permission;
                }
            }

                                    // Jika main menu punya sub menus
            if (!empty($subMenus)) {
                // Cek access main menu
                $mainMenuAccess = in_array($mainMenu, $userPermissions) ? 1 : null;

                $grouped[$mainMenu] = [
                    '_access' => $mainMenuAccess,  // Access untuk main menu
                    '_sub_menus' => [],
                    '_actions' => []  // Actions langsung di main menu
                ];

                // Process setiap sub menu
                foreach ($subMenus as $subMenu) {
                    $subMenuSegments = explode('.', $subMenu);
                    $subPrefix = $subMenuSegments[0] . '.' . $subMenuSegments[1];

                    // Cek apakah ini sub menu atau action
                    if (strpos($subMenu, '.act.') !== false) {
                        // Ini adalah action, bukan sub menu
                        $actionAccess = in_array($subMenu, $userPermissions) ? 1 : null;
                        $grouped[$mainMenu]['_actions'][] = [
                            $subMenu => $actionAccess
                        ];
                    } else {
                        // Ini adalah sub menu
                        $subMenuAccess = in_array($subMenu, $userPermissions) ? 1 : null;

                        $grouped[$mainMenu]['_sub_menus'][$subMenu] = [
                            '_access' => $subMenuAccess,  // Access untuk sub menu
                            '_actions' => []
                        ];

                        // Cari actions untuk sub menu ini
                        $actions = [];
                        foreach ($allPermissions as $permission) {
                            $segments = explode('.', $permission);
                            if (count($segments) == 4 &&
                                $segments[0] . '.' . $segments[1] == $subPrefix) {
                                $actions[] = $permission;
                            }
                        }

                        // Process setiap action
                        foreach ($actions as $action) {
                            $hasAccess = in_array($action, $userPermissions) ? 1 : null;
                            $grouped[$mainMenu]['_sub_menus'][$subMenu]['_actions'][] = [
                                $action => $hasAccess
                            ];
                        }
                    }
                }
            } else {
                // Main menu tidak punya sub menus, langsung set access value
                $hasAccess = in_array($mainMenu, $userPermissions) ? 1 : null;
                $grouped[$mainMenu] = $hasAccess;
            }
        }

        return $grouped;
    }

    /**
     * Sync permissions untuk role (assign checked, remove unchecked)
     */
    public function syncPermissionsToRole(Request $request, $roleId)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $currentUser = User::find($result['id']);
        $fullName = "{$currentUser->first_name} {$currentUser->last_name}";

        try {
            // Validasi input
            $request->validate([
                'permission_ids' => 'array',
                'permission_ids.*' => 'exists:permissions,id'
            ]);

            // Validasi role exists
            $role = Role::find($roleId);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            $checkedPermissionIds = $request->permission_ids ?? [];

            // Get permissions yang saat ini sudah assigned
            $currentPermissions = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();

            // Hapus SEMUA permission lama untuk role ini
            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->delete();

            // Insert hanya permission yang di-check
            $insertData = [];
            foreach ($checkedPermissionIds as $permissionId) {
                $insertData[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ];
            }

            if (!empty($insertData)) {
                DB::table('role_has_permissions')->insert($insertData);
            }

            DB::commit();

            // Analisis perubahan untuk response
            $addedPermissions = array_diff($checkedPermissionIds, $currentPermissions);
            $removedPermissions = array_diff($currentPermissions, $checkedPermissionIds);

            // Get permission names
            $addedPermissionNames = Permission::whereIn('id', $addedPermissions)->pluck('name', 'id');
            $removedPermissionNames = Permission::whereIn('id', $removedPermissions)->pluck('name', 'id');
            $allAssignedNames = Permission::whereIn('id', $checkedPermissionIds)->pluck('name', 'id');

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'syncPermissionsToRole', 'Sync permissions untuk role: ' . $role->name);

            return response()->json([
                'success' => true,
                'message' => 'Permissions berhasil disinkronisasi',
                'data' => [
                    'role_id' => $roleId,
                    'role_name' => $role->name,
                    'total_assigned' => count($checkedPermissionIds),
                    'total_added' => count($addedPermissions),
                    'total_removed' => count($removedPermissions),
                    'assigned_permissions' => $allAssignedNames,
                    'added_permissions' => $addedPermissionNames,
                    'removed_permissions' => $removedPermissionNames,
                    'synced_by' => $fullName,
                    'synced_at' => now()->format('Y-m-d H:i:s')
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

            Log::error('Error syncing permissions to role', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'role_id' => $roleId,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal sync permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password user dan kirim email
     */
    public function resetPassword(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        $currentUser = User::find($result['id']);
        $fullName = "{$currentUser->first_name} {$currentUser->last_name}";

        try {
            DB::beginTransaction();

            // Cari user yang akan di-reset password
            $user = User::findOrFail($id);

            // Generate password baru yang kuat
            $newPassword = $this->generateRandomPassword(12);

            // Update password user
            $user->password = Hash::make($newPassword);
            $user->email_verified_at = null; // Reset email verification
            $user->save();

            // Kirim email reset password
            try {
                $emailData = [
                    'fullname' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'username' => $user->email,
                    'password' => $newPassword,
                    'reset_by' => $fullName
                ];

                Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($emailData));

                // Kirim copy ke admin support jika ada
                $supportEmail = env('MAIL_SUPPORT_EMAIL');
                if ($supportEmail && $supportEmail !== $user->email) {
                    Mail::to($supportEmail)->send(new \App\Mail\ResetPasswordAdminMail($emailData));
                }

                Log::info('Reset password email sent successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'reset_by' => $result['id']
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send reset password email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
                // Tidak throw exception agar tidak mengganggu proses reset password
            }

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'UserManagement', 'resetPassword', 'Reset password user: ' . $user->email);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset dan email telah dikirim',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'reset_by' => $fullName,
                    'reset_at' => now()->format('Y-m-d H:i:s'),
                    'email_sent' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error resetting password', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $id,
                'reset_by' => $result['id']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal reset password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate random password yang kuat
     */
    private function generateRandomPassword($length = 12)
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $password = '';

        // Pastikan minimal 1 karakter dari setiap jenis
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $symbols[rand(0, strlen($symbols) - 1)];

        // Isi sisa dengan karakter random
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }

        // Shuffle password agar tidak predictable
        return str_shuffle($password);
    }
}
