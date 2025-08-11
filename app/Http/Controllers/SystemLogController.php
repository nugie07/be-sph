<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;

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
}