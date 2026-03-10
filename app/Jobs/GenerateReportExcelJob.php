<?php

namespace App\Jobs;

use App\Exports\ReportDataExport;
use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReportExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $backoff = [60, 120];

    protected int $reportExportId;

    public function __construct(int $reportExportId)
    {
        $this->reportExportId = $reportExportId;
    }

    public function handle(): void
    {
        $report = ReportExport::find($this->reportExportId);
        if (!$report) {
            Log::warning('GenerateReportExcelJob: report export not found', ['id' => $this->reportExportId]);
            return;
        }

        ReportExport::where('id', $this->reportExportId)->update([
            'status' => ReportExport::STATUS_PROCESSING,
            'error' => null,
            'updated_at' => now(),
        ]);

        try {
            $data = $this->runQuery($report);
            $headings = $data['headings'];
            $rows = $data['rows'];

            $filename = $this->buildFilename($report);
            $tempFilename = 'report_' . $this->reportExportId . '_' . time() . '.xlsx';

            $export = new ReportDataExport($headings, $rows);
            Excel::store($export, $tempFilename, 'local');

            $localFullPath = Storage::disk('local')->path($tempFilename);
            $content = file_get_contents($localFullPath);

            $storagePath = 'reports/' . $filename;
            Storage::disk('byteplus')->put($storagePath, $content);

            Storage::disk('local')->delete($tempFilename);

            ReportExport::where('id', $this->reportExportId)->update([
                'status' => ReportExport::STATUS_READY,
                'file_path' => $storagePath,
                'filename' => $filename,
                'error' => null,
                'updated_at' => now(),
            ]);

            Log::info('GenerateReportExcelJob: report ready', ['id' => $this->reportExportId, 'path' => $storagePath]);
        } catch (\Throwable $e) {
            ReportExport::where('id', $this->reportExportId)->update([
                'status' => ReportExport::STATUS_FAILED,
                'error' => $e->getMessage(),
                'updated_at' => now(),
            ]);
            Log::error('GenerateReportExcelJob failed', [
                'id' => $this->reportExportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        ReportExport::where('id', $this->reportExportId)->update([
            'status' => ReportExport::STATUS_FAILED,
            'error' => $e->getMessage(),
            'updated_at' => now(),
        ]);
    }

    protected function runQuery(ReportExport $report): array
    {
        $type = $report->report_type;
        $dateFrom = $report->date_from?->format('Y-m-d');
        $dateTo = $report->date_to?->format('Y-m-d');

        if ($type === ReportExport::TYPE_AR) {
            return $this->queryAr($dateFrom, $dateTo);
        }
        if ($type === ReportExport::TYPE_AP) {
            return $this->queryAp($dateFrom, $dateTo, $report->ap_sub_type);
        }
        if ($type === ReportExport::TYPE_LOGISTIK) {
            return $this->queryLogistik($dateFrom, $dateTo);
        }

        throw new \InvalidArgumentException('Invalid report_type: ' . $type);
    }

    protected function queryAr(?string $dateFrom, ?string $dateTo): array
    {
        $dateFrom = $dateFrom ?? '1970-01-01';
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        $sql = "
            SELECT
                fi.invoice_no, fi.dn_no, fi.invoice_date, fi.terms, fi.po_no, fi.bill_to, fi.ship_to,
                fi.ship_to_address, fi.fob, fi.sent_date, fi.sent_via, id.nama_item, id.qty, id.harga,
                id.total AS detail_total, fi.sub_total, fi.diskon, fi.ppn, fi.pbbkb, fi.pph, fi.oat, fi.transport, fi.total,
                fi.terbilang,
                CASE
                    WHEN fi.status = 1 THEN 'Menunggu Approval'
                    WHEN fi.status = 4 THEN 'Approved'
                    ELSE 'Tidak ada keterangan'
                END AS status,
                CASE
                    WHEN fi.type = 1 THEN 'Invoice'
                    WHEN fi.type = 2 THEN 'Proforma Invoice'
                    ELSE '-'
                END AS type,
                CASE
                    WHEN fi.payment_status = 0 THEN 'Unpaid'
                    WHEN fi.payment_status = 1 THEN 'Paid'
                    ELSE '-'
                END AS payment_status,
                fi.payment_date,
                fi.receipt_number
            FROM finance_invoice fi
            LEFT JOIN invoice_details id ON id.invoice_id = fi.id
            WHERE fi.created_at BETWEEN ? AND ?
        ";
        $rows = DB::select($sql, [$dateFrom, $dateTo]);
        return $this->resultToHeadingsAndRows($rows);
    }

    protected function queryAp(?string $dateFrom, ?string $dateTo, ?string $apSubType): array
    {
        $dateFrom = $dateFrom ?? '1970-01-01';
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        $categoryWhere = '';
        if ($apSubType === ReportExport::AP_SUB_SUPPLIER) {
            $categoryWhere = ' AND po.category = 1';
        } elseif ($apSubType === ReportExport::AP_SUB_TRANSPORTIR) {
            $categoryWhere = ' AND po.category = 2';
        }

        $sql = "
            SELECT
                po.dn_no, po.customer_po, po.vendor_po, po.vendor_name, po.tgl_po, po.nama, po.alamat, po.contact,
                po.fob, po.term, po.transport, po.loading_point, po.shipped_via, po.delivery_to,
                po.qty, po.harga, po.ppn, po.pbbkb, po.pph, po.bph, po.portal, po.sub_total, po.total, po.terbilang,
                CASE
                    WHEN po.category = 1 THEN 'Supplier'
                    WHEN po.category = 2 THEN 'Transportir'
                    ELSE 'Tidak ada keterangan'
                END AS category,
                CASE
                    WHEN po.status = 1 THEN 'Menunggu Approval'
                    WHEN po.status = 4 THEN 'Approved'
                    ELSE 'Tidak ada keterangan'
                END AS status,
                CASE
                    WHEN po.payment_status = 0 THEN 'Unpaid'
                    WHEN po.payment_status = 1 THEN 'Paid'
                    ELSE 'Tidak ada keterangan'
                END AS payment_status,
                po.receipt_number, po.payment_date, po.created_at
            FROM purchase_order po
            WHERE po.created_at BETWEEN ? AND ? " . $categoryWhere;

        $rows = DB::select($sql, [$dateFrom, $dateTo]);
        return $this->resultToHeadingsAndRows($rows);
    }

    protected function queryLogistik(?string $dateFrom, ?string $dateTo): array
    {
        // Placeholder: tidak ada query Logistik dari user. Return empty dengan header kosong atau satu kolom.
        return [
            'headings' => ['Info'],
            'rows' => [['Report Logistik belum diisi. Query dapat ditambahkan kemudian.']],
        ];
    }

    protected function resultToHeadingsAndRows(array $rows): array
    {
        if (empty($rows)) {
            return ['headings' => [], 'rows' => []];
        }
        $first = (array) $rows[0];
        $headings = array_keys($first);
        $rowsArray = [];
        foreach ($rows as $row) {
            $rowsArray[] = array_values((array) $row);
        }
        return ['headings' => $headings, 'rows' => $rowsArray];
    }

    protected function buildFilename(ReportExport $report): string
    {
        $type = $report->report_type;
        $sub = $report->ap_sub_type ? '_' . $report->ap_sub_type : '';
        $from = $report->date_from?->format('Y-m-d') ?? 'na';
        $to = $report->date_to?->format('Y-m-d') ?? 'na';
        return sprintf('report_%s%s_%s_%s_%s.xlsx', $type, $sub, $from, $to, $report->id);
    }
}
