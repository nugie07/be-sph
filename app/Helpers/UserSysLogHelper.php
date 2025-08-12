<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserSysLogHelper
{
    /**
     * Log aktivitas user ke table user_sys_log
     * 
     * @param int $userId ID user yang melakukan aktivitas
     * @param string $userName Nama user yang melakukan aktivitas
     * @param string $controllerName Nama controller (tanpa 'Controller' suffix)
     * @param string $functionName Nama function yang dipanggil
     * @param string $activity Deskripsi aktivitas (opsional, akan di-generate otomatis jika kosong)
     * @return bool
     */
    public static function log($userId, $userName, $controllerName, $functionName, $activity = null)
    {
        try {
            // Generate activity jika tidak disediakan
            if (!$activity) {
                $activity = self::generateActivity($functionName);
            }

            // Format services: functionName.ControllerName
            $services = $functionName . '.' . $controllerName . 'Controller';

            // Insert ke table user_sys_log
            DB::table('user_sys_log')->insert([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'user_name' => $userName,
                'services' => $services,
                'activity' => $activity,
                'timestamp' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Log error jika terjadi masalah
            Log::error('UserSysLog Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate activity description berdasarkan function name
     * 
     * @param string $functionName
     * @return string
     */
    private static function generateActivity($functionName)
    {
        $activityMap = [
            // Auth activities
            'login' => 'Login ke sistem',
            'logout' => 'Logout dari sistem',
            'updateProfile' => 'Update profil user',
            'generateOTP' => 'Generate OTP',
            'verifyOTP' => 'Verifikasi OTP',
            'resetPassword' => 'Reset password',
            
            // CRUD activities
            'store' => 'Simpan data baru',
            'create' => 'Buat data baru',
            'update' => 'Update data',
            'edit' => 'Edit data',
            'destroy' => 'Hapus data',
            'delete' => 'Hapus data',
            'softDelete' => 'Soft delete data',
            
            // Approval activities
            'verify' => 'Verifikasi/Approve data',
            'approve' => 'Approve data',
            'reject' => 'Reject data',
            'revisi' => 'Revisi data',
            'cancel' => 'Cancel data',
            
            // File activities
            'upload' => 'Upload file',
            'download' => 'Download file',
            'generatePDF' => 'Generate PDF',
            
            // List/View activities
            'index' => 'Lihat daftar data',
            'list' => 'Lihat daftar data',
            'show' => 'Lihat detail data',
            'view' => 'Lihat data',
            
            // Payment activities
            'uploadPaymentReceipt' => 'Upload bukti pembayaran',
            'uploadReceipt' => 'Upload bukti pembayaran',
            'payment' => 'Proses pembayaran',
            
            // Export activities
            'export' => 'Export data',
            'generateExcel' => 'Generate Excel',
            
            // Other activities
            'search' => 'Cari data',
            'filter' => 'Filter data',
            'sort' => 'Sort data',
            'bulkAction' => 'Bulk action',
            'import' => 'Import data',
            'sync' => 'Sync data',
        ];

        return $activityMap[$functionName] ?? 'Melakukan aktivitas ' . $functionName;
    }

    /**
     * Log dengan data dari AuthValidator result
     * 
     * @param array $authResult Result dari AuthValidator::validateTokenAndClient
     * @param string $controllerName Nama controller (tanpa 'Controller' suffix)
     * @param string $functionName Nama function yang dipanggil
     * @param string $activity Deskripsi aktivitas (opsional)
     * @return bool
     */
    public static function logFromAuth($authResult, $controllerName, $functionName, $activity = null)
    {
        if (!is_array($authResult) || !$authResult['status']) {
            return false;
        }

        $userId = $authResult['id'];
        $user = $authResult['token']->tokenable;
        $userName = $user->first_name . ' ' . $user->last_name;

        return self::log($userId, $userName, $controllerName, $functionName, $activity);
    }

    /**
     * Log dengan data user dari token
     * 
     * @param object $token PersonalAccessToken dari Sanctum
     * @param string $controllerName Nama controller (tanpa 'Controller' suffix)
     * @param string $functionName Nama function yang dipanggil
     * @param string $activity Deskripsi aktivitas (opsional)
     * @return bool
     */
    public static function logFromToken($token, $controllerName, $functionName, $activity = null)
    {
        if (!$token || !$token->tokenable) {
            return false;
        }

        $user = $token->tokenable;
        $userId = $user->id;
        $userName = $user->first_name . ' ' . $user->last_name;

        return self::log($userId, $userName, $controllerName, $functionName, $activity);
    }
}
