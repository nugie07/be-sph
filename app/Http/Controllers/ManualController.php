<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthValidator;
use App\Helpers\UserSysLogHelper;
use App\Models\Manual;
use App\Models\ManualDetail;
use Illuminate\Support\Facades\Log;

class ManualController extends Controller
{
    /**
     * Helper method to handle sequence logic
     * 1. Ensure sequence is sequential (1,2,3,4...), auto-correct if not (max + 1)
     * 2. If sequence already exists, shift all >= sequences by +1
     */
    private function handleSequence($requestedSequence, $excludeId = null)
    {
        // Get max sequence (excluding current manual if updating)
        $maxSequence = Manual::when($excludeId, function($query) use ($excludeId) {
            $query->where('id', '!=', $excludeId);
        })->max('sequence') ?? 0;

        // Ensure sequence is sequential (must be max + 1 if requested > max + 1)
        $finalSequence = $requestedSequence;
        if ($requestedSequence > $maxSequence + 1) {
            // If requested sequence is not sequential (e.g., 20 when max is 3), set to max + 1 (4)
            $finalSequence = $maxSequence + 1;
        } elseif ($requestedSequence < 1) {
            $finalSequence = $maxSequence + 1;
        }

        // Check if sequence already exists
        $existingManual = Manual::when($excludeId, function($query) use ($excludeId) {
            $query->where('id', '!=', $excludeId);
        })->where('sequence', $finalSequence)->first();

        if ($existingManual) {
            // If sequence exists, shift all sequences >= finalSequence by +1
            // The existing manual will get sequence +1, and we'll use the finalSequence for new/updated manual
            Manual::when($excludeId, function($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })->where('sequence', '>=', $finalSequence)
              ->increment('sequence');
        }

        return $finalSequence;
    }

    /**
     * Helper method to handle sequence logic for manual details
     * 1. Ensure sequence is sequential (1,2,3,4...), auto-correct if not (max + 1)
     *    Example: If max is 3 and user posts 20, it becomes 4
     * 2. If sequence already exists, shift all >= sequences by +1
     *    The existing detail will get sequence +1, and we'll use the finalSequence for new/updated detail
     */
    private function handleDetailSequence($requestedSequence, $menuId, $excludeId = null)
    {
        // Get max sequence for this menu_id (excluding current detail if updating)
        $maxSequence = ManualDetail::where('menu_id', $menuId)
            ->when($excludeId, function($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->max('sequence') ?? 0;

        // Ensure sequence is sequential (must be max + 1 if requested > max + 1)
        $finalSequence = $requestedSequence;
        if ($requestedSequence > $maxSequence + 1) {
            // If requested sequence is not sequential (e.g., 20 when max is 3), set to max + 1 (4)
            $finalSequence = $maxSequence + 1;
        } elseif ($requestedSequence < 1) {
            $finalSequence = $maxSequence + 1;
        }

        // Check if sequence already exists for this menu_id
        $existingDetail = ManualDetail::where('menu_id', $menuId)
            ->when($excludeId, function($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->where('sequence', $finalSequence)
            ->first();

        if ($existingDetail) {
            // If sequence exists, shift all sequences >= finalSequence by +1 for this menu_id
            // The existing detail will get sequence +1, and we'll use the finalSequence for new/updated detail
            ManualDetail::where('menu_id', $menuId)
                ->when($excludeId, function($query) use ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                })
                ->where('sequence', '>=', $finalSequence)
                ->increment('sequence');
        }

        return $finalSequence;
    }

    /**
     * List all manuals with their details
     * GET /api/manual
     */
    public function index(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $manuals = Manual::with('details')
                ->orderBy('sequence', 'asc')
                ->get();

            $data = $manuals->map(function ($manual) {
                return [
                    'id' => $manual->id,
                    'title' => $manual->title,
                    'sequence' => $manual->sequence,
                    'status' => $manual->status,
                    'status_label' => $manual->status == 1 ? 'Active' : 'Inactive',
                    'created_at' => $manual->created_at ? $manual->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $manual->updated_at ? $manual->updated_at->format('Y-m-d H:i:s') : null,
                    'details' => $manual->details->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'menu_id' => $detail->menu_id,
                            'sequence' => $detail->sequence,
                            'content' => $detail->content,
                            'created_at' => $detail->created_at ? $detail->created_at->format('Y-m-d H:i:s') : null,
                            'updated_at' => $detail->updated_at ? $detail->updated_at->format('Y-m-d H:i:s') : null,
                        ];
                    })->values()
                ];
            });

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Manual', 'index');

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $manuals->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting manual list', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single manual with details
     * GET /api/manual/{id}
     */
    public function show(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $manual = Manual::with('details')->find($id);

            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual tidak ditemukan'
                ], 404);
            }

            $data = [
                'id' => $manual->id,
                'title' => $manual->title,
                'sequence' => $manual->sequence,
                'status' => $manual->status,
                'status_label' => $manual->status == 1 ? 'Active' : 'Inactive',
                'created_at' => $manual->created_at ? $manual->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $manual->updated_at ? $manual->updated_at->format('Y-m-d H:i:s') : null,
                'details' => $manual->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'menu_id' => $detail->menu_id,
                        'sequence' => $detail->sequence,
                        'content' => $detail->content,
                        'created_at' => $detail->created_at ? $detail->created_at->format('Y-m-d H:i:s') : null,
                        'updated_at' => $detail->updated_at ? $detail->updated_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values()
            ];

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Manual', 'show');

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting manual detail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new manual
     * POST /api/manual
     */
    public function store(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:500',
                'sequence' => 'nullable|integer|min:0',
                'status' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Handle sequence logic
            $requestedSequence = $request->sequence ?? 0;
            if ($requestedSequence == 0) {
                // If no sequence provided, set to max + 1
                $maxSequence = Manual::max('sequence') ?? 0;
                $finalSequence = $maxSequence + 1;
            } else {
                $finalSequence = $this->handleSequence($requestedSequence);
            }

            $manual = Manual::create([
                'title' => $request->title,
                'sequence' => $finalSequence,
                'status' => $request->status,
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Manual', 'store');

            return response()->json([
                'success' => true,
                'message' => 'Manual berhasil dibuat',
                'data' => [
                    'id' => $manual->id,
                    'title' => $manual->title,
                    'sequence' => $manual->sequence,
                    'status' => $manual->status,
                    'status_label' => $manual->status == 1 ? 'Active' : 'Inactive',
                    'created_at' => $manual->created_at ? $manual->created_at->format('Y-m-d H:i:s') : null,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating manual', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update manual
     * PUT /api/manual/{id}
     */
    public function update(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $manual = Manual::find($id);

            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:500',
                'sequence' => 'nullable|integer|min:0',
                'status' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Handle sequence logic if sequence is being updated
            $finalSequence = $manual->sequence;
            if ($request->has('sequence') && $request->sequence != $manual->sequence) {
                $requestedSequence = $request->sequence;
                $finalSequence = $this->handleSequence($requestedSequence, $id);
            }

            $manual->update([
                'title' => $request->title,
                'sequence' => $finalSequence,
                'status' => $request->status,
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Manual', 'update');

            return response()->json([
                'success' => true,
                'message' => 'Manual berhasil diperbarui',
                'data' => [
                    'id' => $manual->id,
                    'title' => $manual->title,
                    'sequence' => $manual->sequence,
                    'status' => $manual->status,
                    'status_label' => $manual->status == 1 ? 'Active' : 'Inactive',
                    'updated_at' => $manual->updated_at ? $manual->updated_at->format('Y-m-d H:i:s') : null,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating manual', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete manual (only if no details exist)
     * DELETE /api/manual/{id}
     */
    public function destroy(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $manual = Manual::find($id);

            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual tidak ditemukan'
                ], 404);
            }

            // Check if manual has details
            $detailsCount = ManualDetail::where('menu_id', $id)->count();

            if ($detailsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus manual karena masih memiliki ' . $detailsCount . ' detail manual. Silakan hapus detail manual terlebih dahulu.'
                ], 422);
            }

            DB::beginTransaction();

            $manual->delete();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'Manual', 'destroy');

            return response()->json([
                'success' => true,
                'message' => 'Manual berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting manual', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single manual detail
     * GET /api/manual-details/{id}
     */
    public function showDetail(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $detail = ManualDetail::with('manual')->find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual detail tidak ditemukan'
                ], 404);
            }

            $data = [
                'id' => $detail->id,
                'menu_id' => $detail->menu_id,
                'sequence' => $detail->sequence,
                'content' => $detail->content,
                'created_at' => $detail->created_at ? $detail->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $detail->updated_at ? $detail->updated_at->format('Y-m-d H:i:s') : null,
                'manual' => $detail->manual ? [
                    'id' => $detail->manual->id,
                    'title' => $detail->manual->title,
                ] : null
            ];

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'ManualDetail', 'showDetail');

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting manual detail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data manual detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new manual detail
     * POST /api/manual-details
     */
    public function storeDetail(Request $request)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $validator = Validator::make($request->all(), [
                'menu_id' => 'required|integer|exists:manual,id',
                'sequence' => 'nullable|integer|min:0',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Handle sequence logic
            $requestedSequence = $request->sequence ?? 0;
            if ($requestedSequence == 0) {
                // If no sequence provided, set to max + 1 for this menu_id
                $maxSequence = ManualDetail::where('menu_id', $request->menu_id)->max('sequence') ?? 0;
                $finalSequence = $maxSequence + 1;
            } else {
                $finalSequence = $this->handleDetailSequence($requestedSequence, $request->menu_id);
            }

            $detail = ManualDetail::create([
                'menu_id' => $request->menu_id,
                'sequence' => $finalSequence,
                'content' => $request->content,
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'ManualDetail', 'storeDetail');

            return response()->json([
                'success' => true,
                'message' => 'Manual detail berhasil dibuat',
                'data' => [
                    'id' => $detail->id,
                    'menu_id' => $detail->menu_id,
                    'sequence' => $detail->sequence,
                    'content' => $detail->content,
                    'created_at' => $detail->created_at ? $detail->created_at->format('Y-m-d H:i:s') : null,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating manual detail', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat manual detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update manual detail
     * PUT /api/manual-details/{id}
     */
    public function updateDetail(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $detail = ManualDetail::find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual detail tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'menu_id' => 'required|integer|exists:manual,id',
                'sequence' => 'nullable|integer|min:0',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Handle sequence logic if sequence is being updated
            $finalSequence = $detail->sequence;
            if ($request->has('sequence') && $request->sequence != $detail->sequence) {
                $requestedSequence = $request->sequence;
                $finalSequence = $this->handleDetailSequence($requestedSequence, $request->menu_id, $id);
            }

            $detail->update([
                'menu_id' => $request->menu_id,
                'sequence' => $finalSequence,
                'content' => $request->content,
            ]);

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'ManualDetail', 'updateDetail');

            return response()->json([
                'success' => true,
                'message' => 'Manual detail berhasil diperbarui',
                'data' => [
                    'id' => $detail->id,
                    'menu_id' => $detail->menu_id,
                    'sequence' => $detail->sequence,
                    'content' => $detail->content,
                    'updated_at' => $detail->updated_at ? $detail->updated_at->format('Y-m-d H:i:s') : null,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating manual detail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui manual detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete manual detail
     * DELETE /api/manual-details/{id}
     */
    public function destroyDetail(Request $request, $id)
    {
        $result = AuthValidator::validateTokenAndClient($request);
        if (!is_array($result) || !$result['status']) {
            return $result;
        }

        try {
            $detail = ManualDetail::find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual detail tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            $detail->delete();

            DB::commit();

            // Log aktivitas user
            UserSysLogHelper::logFromAuth($result, 'ManualDetail', 'destroyDetail');

            return response()->json([
                'success' => true,
                'message' => 'Manual detail berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting manual detail', [
                'id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus manual detail: ' . $e->getMessage()
            ], 500);
        }
    }
}
