<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SphController;
use App\Models\DataTrxSph;

class GenerateSphPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 120, 300];

    protected int $sphId;

    protected bool $updateFileSph;

    protected string $tempSphAction;

    protected ?int $sphPdfJobId;

    public function __construct(
        int $sphId,
        bool $updateFileSph = false,
        string $tempSphAction = 'insert',
        ?int $sphPdfJobId = null
    ) {
        $this->sphId = $sphId;
        $this->updateFileSph = $updateFileSph;
        $this->tempSphAction = $tempSphAction;
        $this->sphPdfJobId = $sphPdfJobId;
    }

    public function handle(): void
    {
        $jobId = $this->sphPdfJobId;

        if ($jobId) {
            DB::table('sph_pdf_jobs')->where('id', $jobId)->update([
                'status' => 'processing',
                'attempt' => $this->attempts(),
                'updated_at' => now(),
            ]);
        }

        $sph = DataTrxSph::find($this->sphId);
        if (!$sph) {
            throw new \RuntimeException('SPH tidak ditemukan: ' . $this->sphId);
        }

        $defaultTemplatesEnv = env('DEFAULT_TEMPLATE', '');
        $defaultTemplateIds = [];
        if (!empty($defaultTemplatesEnv)) {
            foreach (explode(',', $defaultTemplatesEnv) as $val) {
                $val = trim($val);
                if ($val !== '') {
                    $defaultTemplateIds[] = (int) $val;
                }
            }
        }

        $tplId = $sph->template_id;
        $controller = app(SphController::class);

        if (!empty($defaultTemplateIds) && in_array((int) $tplId, $defaultTemplateIds, true)) {
            $pdfResponse = $controller->generatePdf($this->sphId);
        } else {
            $pdfResponse = $controller->generateKmpPdfFile($this->sphId);
        }

        if (!is_a($pdfResponse, \Illuminate\Http\JsonResponse::class)) {
            throw new \RuntimeException('Generate PDF mengembalikan response tidak valid.');
        }

        $pdfData = $pdfResponse->getData(true);
        $pdfPath = $pdfData['pdf_path'] ?? $pdfData['path'] ?? null;
        if (empty($pdfPath) && !empty($pdfData['pdf_url'])) {
            $pdfPath = self::urlToPath($pdfData['pdf_url']);
        }
        if (empty($pdfPath)) {
            throw new \RuntimeException('PDF path kosong setelah generate.');
        }
        $pdfPath = self::ensurePathOnly($pdfPath);

        if ($this->updateFileSph) {
            DataTrxSph::where('id', $this->sphId)->update(['file_sph' => $pdfPath]);
        }

        $now = now();
        $shouldUpdateTempSph = in_array($this->tempSphAction, ['insert', 'update'], true) || $this->updateFileSph;
        if ($shouldUpdateTempSph) {
            $exists = DB::table('temp_sph')->where('sph_id', $this->sphId)->exists();
            if ($exists) {
                DB::table('temp_sph')->where('sph_id', $this->sphId)->update([
                    'temp_link' => $pdfPath,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('temp_sph')->insert([
                    'sph_id' => $this->sphId,
                    'temp_link' => $pdfPath,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            Log::info('GenerateSphPdfJob: temp_sph updated for preview', ['sph_id' => $this->sphId, 'temp_link' => $pdfPath]);
        }

        if ($jobId) {
            DB::table('sph_pdf_jobs')->where('id', $jobId)->update([
                'status' => 'success',
                'pdf_url' => $pdfPath,
                'error' => null,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $jobId = $this->sphPdfJobId;
        if ($jobId) {
            DB::table('sph_pdf_jobs')->where('id', $jobId)->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::error('GenerateSphPdfJob failed', [
            'sph_id' => $this->sphId,
            'attempts' => $this->attempts(),
            'error' => $e->getMessage(),
        ]);
    }

    /** Pastikan hanya path (object key) yang disimpan, bukan URL penuh. Kolom DB terbatas 255 char. */
    private static function ensurePathOnly(string $value): string
    {
        if (str_contains($value, '?') || str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return self::urlToPath($value);
        }
        return $value;
    }

    /** Ambil path object dari URL (presigned atau biasa). */
    private static function urlToPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === false) {
            return $url;
        }
        return ltrim($path, '/');
    }
}
