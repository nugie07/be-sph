<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportExcelJob;
use App\Models\ReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReportingController extends Controller
{
    /**
     * Request generate report (antrian/queue).
     * Payload: report_type (ar|ap|logistik), date_from & date_to (wajib untuk AR), ap_sub_type (untuk AP: all|supplier|transportir).
     */
    public function requestReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => ['required', 'string', Rule::in([ReportExport::TYPE_AR, ReportExport::TYPE_AP, ReportExport::TYPE_LOGISTIK])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'ap_sub_type' => ['nullable', 'string', Rule::in([ReportExport::AP_SUB_ALL, ReportExport::AP_SUB_SUPPLIER, ReportExport::AP_SUB_TRANSPORTIR])],
        ]);

        $type = $validated['report_type'];

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if ($type === ReportExport::TYPE_AR) {
            $request->validate([
                'date_from' => ['required', 'date'],
                'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            ]);
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
        }

        $report = ReportExport::create([
            'user_id' => $request->user()?->id,
            'report_type' => $type,
            'ap_sub_type' => $type === ReportExport::TYPE_AP ? ($validated['ap_sub_type'] ?? ReportExport::AP_SUB_ALL) : null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => ReportExport::STATUS_PENDING,
        ]);

        GenerateReportExcelJob::dispatch($report->id);

        return response()->json([
            'message' => 'Report request berhasil ditambahkan ke antrian.',
            'data' => $this->formatExportItem($report),
        ], 201);
    }

    /**
     * List semua request export (untuk halaman Download Export).
     * Bisa pakai tombol refresh untuk cek status; jika ready bisa download.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = ReportExport::query()->orderByDesc('created_at');

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        $items = $query->paginate($perPage);
        $items->getCollection()->transform(function ($report) {
            return $this->formatExportItem($report);
        });

        return response()->json([
            'message' => 'OK',
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Redirect ke URL download (BytePlus presigned) jika status ready.
     */
    public function download(Request $request, int $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $report = ReportExport::find($id);
        if (!$report) {
            return response()->json(['message' => 'Report export tidak ditemukan.'], 404);
        }

        if ($report->user_id !== null && $request->user() && (int) $request->user()->id !== (int) $report->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($report->status !== ReportExport::STATUS_READY) {
            return response()->json([
                'message' => 'Report belum siap. Status: ' . $report->status,
                'status' => $report->status,
            ], 400);
        }

        if (!$report->file_path || !Storage::disk('byteplus')->exists($report->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan di storage.'], 404);
        }

        $url = byteplus_url($report->file_path, 15);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'OK',
                'download_url' => $url,
                'filename' => $report->filename,
            ]);
        }

        return redirect()->away($url);
    }

    protected function formatExportItem(ReportExport $report): array
    {
        $item = [
            'id' => $report->id,
            'report_type' => $report->report_type,
            'ap_sub_type' => $report->ap_sub_type,
            'date_from' => $report->date_from?->format('Y-m-d'),
            'date_to' => $report->date_to?->format('Y-m-d'),
            'status' => $report->status,
            'filename' => $report->filename,
            'created_at' => $report->created_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];

        if ($report->status === ReportExport::STATUS_READY && $report->file_path) {
            $item['download_url'] = byteplus_url($report->file_path, 15);
        }

        if ($report->status === ReportExport::STATUS_FAILED && $report->error) {
            $item['error'] = $report->error;
        }

        return $item;
    }
}
