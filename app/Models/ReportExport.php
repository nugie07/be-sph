<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    protected $table = 'report_exports';

    protected $fillable = [
        'user_id',
        'report_type',
        'ap_sub_type',
        'date_from',
        'date_to',
        'status',
        'file_path',
        'error',
        'filename',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public const TYPE_AR = 'ar';
    public const TYPE_AP = 'ap';
    public const TYPE_LOGISTIK = 'logistik';

    public const AP_SUB_ALL = 'all';
    public const AP_SUB_SUPPLIER = 'supplier';
    public const AP_SUB_TRANSPORTIR = 'transportir';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
