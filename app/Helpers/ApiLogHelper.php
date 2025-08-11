<?php

use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;

if (!function_exists('log_system')) {
    
    function log_system($modul, $activity, $service, $payload, $response)
    {
        try {
            SystemLog::create([
                'modul'    => $modul,
                'activity' => $activity,
                'services' => $service,
                'payload'  => is_array($payload) ? json_encode($payload) : $payload,
                'response' => is_array($response) ? json_encode($response) : $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal simpan log system_logs: ' . $e->getMessage());
        }
    }
}