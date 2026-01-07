<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Helpers\AuthValidator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\Helpers\WorkflowHelper;
use App\Helpers\UserSysLogHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AuthController extends Controller
{
public function login(Request $request)
    {
            // ✅ Validasi X-Client-Secret
        $clientSecret = $request->header('X-Client-Secret');
        $expectedSecret = config('app.client_secret');

        // Log untuk debugging
        Log::info('Login attempt', [
            'email' => $request->email,
            'has_client_secret' => !empty($clientSecret),
            'client_secret_match' => $clientSecret === $expectedSecret,
            'expected_secret_set' => !empty($expectedSecret),
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        if (!$clientSecret || $clientSecret !== $expectedSecret) {
            Log::warning('Login failed: Invalid client secret', [
                'email' => $request->email,
                'provided_secret' => $clientSecret ? 'provided' : 'missing',
                'expected_secret' => $expectedSecret ? 'set' : 'not_set'
            ]);
            return response()->json([
                'message' => 'Unauthorized. Invalid client secret.'
            ], 403);
        }

        // ✅ Validasi email & password
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek apakah user exists
        $user = User::where('email', $request->email)->whereNull('deleted_at')->first();
        
        if (!$user) {
            Log::warning('Login failed: User not found', [
                'email' => $request->email
            ]);
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning('Login failed: Invalid password', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = Auth::user();

        $roleName = \DB::table('users as a')
            ->leftJoin('model_has_roles as b', 'b.model_id', '=', 'a.id')
            ->leftJoin('roles as c', 'c.id', '=', 'b.role_id')
            ->where('a.id', $user->id)
            ->value('c.name');

        // Get user permissions dengan grouping
        $userPermissions = $this->getUserPermissionsForLogin($user->id);

        // ✅ Hapus semua token lama
        $user->tokens()->delete();

        // ✅ Buat token baru
        $tokenResult = $user->createToken('api-token');

        // Set expires_at 12 jam ke depan
        $tokenResult->accessToken->expires_at = Carbon::now()->addHours(12);
        $tokenResult->accessToken->last_used_at = Carbon::now();
        $tokenResult->accessToken->save();

        // Ambil plain token tanpa id|
        $plainToken = explode('|', $tokenResult->plainTextToken, 2)[1];

        // Log aktivitas login
        UserSysLogHelper::log($user->id, $user->first_name . ' ' . $user->last_name, 'Auth', 'login');

        return response()->json([
            'message' => 'Login successful',
            'token'   => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->accessToken->expires_at
                ? $tokenResult->accessToken->expires_at->timezone('Asia/Jakarta')->toDateTimeString()
                : null,

            'last_login' => $tokenResult->accessToken->last_used_at
                ? $tokenResult->accessToken->last_used_at->timezone('Asia/Jakarta')->toDateTimeString()
                : null,

            'user' => array_merge($user->toArray(), [
                'role' => $roleName
            ]),
            'permissions' => $userPermissions
        ]);
    }

public function logout(Request $request)
    {
        $clientSecret = $request->header('X-Client-Secret');
        $expectedSecret = config('app.client_secret');

        if (!$clientSecret || $clientSecret !== $expectedSecret) {
            return response()->json([
                'message' => 'Unauthorized. Invalid client secret.'
            ], 403);
        }
        // 🔑 Hapus hanya token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'message' => 'Logout successful.'
        ]);
    }

    /**
     * Get delivery tracking data dengan filter date range dan PO number
     */
    public function getDeliveryTracking(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            // Get query parameters
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $poNumber = $request->get('po_number');

            // Build query
            $query = DB::table('good_receipt as gr')
                ->leftJoin('delivery_request as drs', 'drs.po_number', '=', 'gr.po_no')
                ->leftJoin('purchase_order as po', 'po.drs_unique', '=', 'drs.drs_unique')
                ->leftJoin('delivery_note as dn', 'dn.drs_unique', '=', 'drs.drs_unique')
                ->select([
                    'gr.status',
                    'gr.nama_customer',
                    'gr.po_no',
                    'gr.created_at',
                    'drs.volume',
                    'drs.request_date',
                    'drs.wilayah',
                    'drs.dn_no',
                    'dn.tgl_bongkar',
                    'dn.arrival_date',
                    'dn.bast_date',
                    'dn.file',
                    'drs.transporter_name',
                    'dn.qty',
                    'dn.dn_no',
                    'dn.driver_name',
                    'dn.nopol',
                    DB::raw("
                        CASE
                            WHEN dn.arrival_date IS NULL THEN 'On Progress'
                            WHEN dn.arrival_date = drs.request_date THEN 'Ontime'
                            WHEN dn.arrival_date < drs.request_date THEN 'Lebih Awal'
                            WHEN dn.arrival_date > drs.request_date THEN
                                CONCAT('Telat (', DATEDIFF(dn.arrival_date, drs.request_date), ' hari)')
                            ELSE NULL
                        END AS delivery_ket
                    ")
                ])
                ->where('po.category', 2);

            // Apply filters
            if ($startDate && $endDate) {
                $query->whereBetween('gr.created_at', [$startDate, $endDate]);
            }

            if ($poNumber) {
                $query->where('gr.po_no', 'LIKE', '%' . $poNumber . '%');
            }

            // Get results
            $data = $query->orderBy('gr.created_at', 'desc')->get();

            // Format dates
            $formattedData = $data->map(function ($item) {
                return [
                    'status' => $item->status,
                    'nama_customer' => $item->nama_customer,
                    'po_no' => $item->po_no,
                    'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i') : null,
                    'volume' => $item->volume,
                    'request_date' => $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('Y-m-d') : null,
                    'wilayah' => $item->wilayah,
                    'drs_dn_no' => $item->dn_no, // dari delivery_request
                    'tgl_bongkar' => $item->tgl_bongkar ? \Carbon\Carbon::parse($item->tgl_bongkar)->format('Y-m-d') : null,
                    'arrival_date' => $item->arrival_date ? \Carbon\Carbon::parse($item->arrival_date)->format('Y-m-d') : null,
                    'bast_date' => $item->bast_date ? \Carbon\Carbon::parse($item->bast_date)->format('Y-m-d') : null,
                    'file' => $item->file,
                    'transporter_name' => $item->transporter_name,
                    'qty' => $item->qty,
                    'dn_no' => $item->dn_no, // dari delivery_note
                    'driver_name' => $item->driver_name,
                    'nopol' => $item->nopol,
                    'delivery_ket' => $item->delivery_ket
                ];
            });

            // Calculate summary
            $totalRecords = $data->count();
            $onProgressCount = $data->where('delivery_ket', 'On Progress')->count();
            $ontimeCount = $data->where('delivery_ket', 'Ontime')->count();
            $earlyCount = $data->where('delivery_ket', 'Lebih Awal')->count();
            $lateCount = $data->filter(function ($item) {
                return strpos($item->delivery_ket, 'Telat') === 0;
            })->count();

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'summary' => [
                    'total_records' => $totalRecords,
                    'on_progress' => $onProgressCount,
                    'ontime' => $ontimeCount,
                    'early' => $earlyCount,
                    'late' => $lateCount
                ],
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'po_number' => $poNumber
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting delivery tracking data', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data delivery tracking: ' . $e->getMessage()
            ], 500);
        }
    }

public function checkSession(Request $request)
    {
        // ✅ Check Bearer Token and X-Client-Secret
        $result = AuthValidator::validateTokenAndClient($request);
        if ($result !== true) {
            return $result;
        }

        // ✅ Manual ambil Bearer
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return response()->json(['message' => 'Unauthorized: No token'], 401);
        }

        // ✅ Cek token di DB (jika tidak pakai Sanctum)
        $token = PersonalAccessToken::findToken($bearer);
        if (!$token) {
            return response()->json(['message' => 'Session invalid. Token not found.'], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return response()->json(['message' => 'Session expired. Please login again.'], 401);
        }

        // ✅ Ambil user manual
        $userId = $token->tokenable;
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        return response()->json([
            'message' => 'Session valid.',
        ], 200);
    }

public function profileDetails(Request $request)
    {
       $result = AuthValidator::validateTokenAndClient($request);

        if (!is_array($result) || !$result['status']) {
            return $result; // error response JSON dari helper
        }

        $accessToken = $result['token'];
        $id          = $result['id'];

        // ✅ Ambil user
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // ✅ Ambil 1 role
        $role = $user->getRoleNames()->first(); // Spatie Permission

        return response()->json([
            'profile_details' => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'address'    => $user->address,
                'country'    => $user->country,
                'status'     => $user->status,
                'role'       => $role
            ]
        ]);
    }

public function updateProfile(Request $request)
    {
            $result = AuthValidator::validateTokenAndClient($request);

            if (!is_array($result) || !$result['status']) {
                return $result;
            }
            $id          = $result['id'];

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'nullable|string|max:255',
                'last_name'  => 'nullable|string|max:255',
                'address'    => 'nullable|string|max:255',
                'country'    => 'nullable|string|max:100',
                'password'   => 'nullable|string|min:8', // Tidak pakai confirmed kalau tidak mau
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();

            $user->first_name = $validated['first_name'] ?? $user->first_name;
            $user->last_name  = $validated['last_name'] ?? $user->last_name;
            $user->address    = $validated['address'] ?? $user->address;
            $user->country    = $validated['country'] ?? $user->country;
            $user->email_verified_at = Carbon::now();

            if (!empty($validated['password'])) {
                $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
            }

            $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile_details' => [
                'id'         => $id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'address'    => $user->address,
                'country'    => $user->country,
                'email'      => $user->email,
                'role'       => $user->getRoleNames()->first() ?? null,
            ]
        ]);
    }
public function remarks(Request $request, $id)
{
    // Get tipe_trx from query parameter, default to 'sph' if not provided
    $tipeTrx = $request->query('tipe_trx', 'sph');
    $remarks = WorkflowHelper::getRemarks($id, $tipeTrx);
    return response()->json($remarks);
}

/**
 * Generate dan kirim OTP untuk reset password
 */
public function generateOTP(Request $request)
{
    try {
        // Validasi input
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;

        // Cek apakah email ada di database
        $user = User::where('email', $email)->whereNull('deleted_at')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak valid atau tidak terdaftar'
            ], 404);
        }

        DB::beginTransaction();

        // Generate OTP 6 digit
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Set expire time (15 menit dari sekarang)
        $expireAt = Carbon::now()->addMinutes(15);

        // Hapus OTP lama jika ada
        DB::table('otp_verifikasi')
            ->where('user_id', $user->id)
            ->where('contact', $email)
            ->delete();

        // Insert OTP baru
        $otpId = Str::uuid();
        DB::table('otp_verifikasi')->insert([
            'id' => $otpId,
            'user_id' => $user->id,
            'contact' => $email,
            'otp' => $otp,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'expire_at' => $expireAt,
            'verified_at' => null
        ]);

        // Kirim email OTP
        $emailData = [
            'fullname' => $user->first_name . ' ' . $user->last_name,
            'otp' => $otp,
            'expire_at' => $expireAt->format('H:i')
        ];

        Mail::send('emails.reset_email', ['data' => $emailData], function($message) use ($email, $user) {
            $message->to($email, $user->first_name . ' ' . $user->last_name)
                    ->subject('Reset Password - OTP Verification');
        });

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'OTP telah dikirim ke email Anda',
            'data' => [
                'email' => $email,
                'expire_at' => $expireAt->format('Y-m-d H:i:s'),
                'otp_id' => $otpId
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

        Log::error('Error generating OTP', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'email' => $request->email ?? null
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim OTP: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Verifikasi OTP
 */
public function verifyOTP(Request $request)
{
    try {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        $email = $request->email;
        $otp = $request->otp;

        // Cek apakah email ada di database
        $user = User::where('email', $email)->whereNull('deleted_at')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak valid atau tidak terdaftar'
            ], 404);
        }

        // Cek OTP di database
        $otpRecord = DB::table('otp_verifikasi')
            ->where('user_id', $user->id)
            ->where('contact', $email)
            ->where('otp', $otp)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'OTP tidak valid'
            ], 400);
        }

        // Cek apakah OTP sudah expired
        if (Carbon::now()->gt(Carbon::parse($otpRecord->expire_at))) {
            return response()->json([
                'success' => false,
                'message' => 'OTP sudah expired. Silakan request OTP baru'
            ], 400);
        }

        // Cek apakah OTP sudah digunakan
        if ($otpRecord->verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'OTP sudah digunakan. Silakan request OTP baru'
            ], 400);
        }

        // Update verified_at
        DB::table('otp_verifikasi')
            ->where('id', $otpRecord->id)
            ->update([
                'verified_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil diverifikasi',
            'data' => [
                'email' => $email,
                'user_id' => $user->id,
                'verified_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        Log::error('Error verifying OTP', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'email' => $request->email ?? null
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal verifikasi OTP: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Reset password berdasarkan email dengan generate password random
 */
public function resetPassword(Request $request)
{
    try {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        $email = $request->email;
        $otp = $request->otp;

        // Cek apakah email ada di database
        $user = User::where('email', $email)->whereNull('deleted_at')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak valid atau tidak terdaftar'
            ], 404);
        }

        // Cek OTP di database
        $otpRecord = DB::table('otp_verifikasi')
            ->where('user_id', $user->id)
            ->where('contact', $email)
            ->where('otp', $otp)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'OTP tidak valid'
            ], 400);
        }

        // Cek apakah OTP sudah expired
        if (Carbon::now()->gt(Carbon::parse($otpRecord->expire_at))) {
            return response()->json([
                'success' => false,
                'message' => 'OTP sudah expired. Silakan request OTP baru'
            ], 400);
        }

        // Cek apakah OTP sudah digunakan
        if ($otpRecord->verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'OTP sudah digunakan. Silakan request OTP baru'
            ], 400);
        }

        DB::beginTransaction();

        // Generate password random (12 karakter: huruf besar, kecil, angka, simbol)
        $newPassword = $this->generateRandomPassword();

        // Update password user dan reset email_verified_at
        $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        $user->email_verified_at = null;
        $user->save();

        // Update verified_at OTP
        DB::table('otp_verifikasi')
            ->where('id', $otpRecord->id)
            ->update([
                'verified_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

        // Kirim email password baru
        $emailData = [
            'fullname' => $user->first_name . ' ' . $user->last_name,
            'new_password' => $newPassword
        ];

        Mail::send('emails.new_password', ['data' => $emailData], function($message) use ($email, $user) {
            $message->to($email, $user->first_name . ' ' . $user->last_name)
                    ->subject('Password Baru - Login Credentials');
        });

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Password baru telah dikirim ke email Anda',
            'data' => [
                'email' => $email,
                'user_id' => $user->id,
                'reset_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'password_sent' => true
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

        Log::error('Error resetting password', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'email' => $request->email ?? null
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal reset password: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Get user permissions untuk login response
     */
    private function getUserPermissionsForLogin($userId)
    {
        try {
            // Get semua permissions yang dimiliki user (melalui role)
            $userPermissions = DB::table('permissions as p')
                ->join('role_has_permissions as rhp', 'rhp.permission_id', '=', 'p.id')
                ->join('model_has_roles as mhr', 'mhr.role_id', '=', 'rhp.role_id')
                ->where('mhr.model_id', $userId)
                ->where('mhr.model_type', User::class)
                ->pluck('p.name')
                ->toArray();

            // Get semua permissions yang ada di sistem
            $allPermissions = DB::table('permissions')
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();

            // Group permissions berdasarkan struktur
            return $this->groupPermissionsByStructure($allPermissions, $userPermissions);

        } catch (\Exception $e) {
            Log::error('Error getting user permissions for login', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return [];
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
 * Generate password random yang kuat
 */
private function generateRandomPassword($length = 12)
{
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    $allChars = $uppercase . $lowercase . $numbers . $symbols;
    $password = '';

    // Pastikan minimal 1 karakter dari setiap jenis
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    $password .= $symbols[rand(0, strlen($symbols) - 1)];

    // Isi sisa dengan karakter random
    for ($i = 4; $i < $length; $i++) {
        $password .= $allChars[rand(0, strlen($allChars) - 1)];
    }

    // Acak urutan karakter
    return str_shuffle($password);
}

}
