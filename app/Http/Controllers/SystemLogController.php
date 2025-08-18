<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;
use App\Models\UserSysLog;
use App\Helpers\AuthValidator;
use App\Helpers\UserSysLogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemLogController extends Controller
{
    /**
     * Store a new system log entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'modul'    => 'required|string',
            'activity' => 'required|string',
            'services' => 'required|string',
            'payload'  => 'nullable',
            'response' => 'nullable',
        ]);

        $log = SystemLog::create($validated);

        return response()->json([
            'message' => 'Log berhasil disimpan!',
            'data'    => $log
        ], 201);
    }

    /**
     * List user system logs with search and date range filter
     */
    public function userSysLog(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search', '');
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');

            // Base query
            $query = UserSysLog::query();

            // Search filter by user_name
            if (!empty($search)) {
                $query->where('user_name', 'LIKE', '%' . $search . '%');
            }

            // Date range filter
            if (!empty($startDate)) {
                $query->where('timestamp', '>=', $startDate . ' 00:00:00');
            }

            if (!empty($endDate)) {
                $query->where('timestamp', '<=', $endDate . ' 23:59:59');
            }

            // Hitung total records
            $totalCount = $query->count();

            // Ambil data dengan pagination
            $logs = $query->orderBy('timestamp', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'SystemLog', 'userSysLog');

            $response = [
                'success' => true,
                'data' => $logs,
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
                'SystemLog',
                'List user system logs',
                'userSysLog.SystemLogController',
                $request->all(),
                $response
            );

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error getting user system logs', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorResponse = [
                'success' => false,
                'message' => 'Gagal mengambil data user system logs: ' . $e->getMessage()
            ];

            // Capture log error menggunakan helper SystemLog
            log_system(
                'SystemLog',
                'Error getting user system logs',
                'userSysLog.SystemLogController',
                $request->all(),
                $errorResponse
            );

            return response()->json($errorResponse, 500);
        }
    }
}
