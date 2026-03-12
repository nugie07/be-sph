<?php

namespace App\Console\Commands;

use App\Models\DataTrxSph;
use App\Models\PurchaseOrder;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class SendApprovalReminderTelegram extends Command
{
    protected $signature = 'telegram:send-approval-reminder';

    protected $description = 'Kirim reminder ke Telegram bila ada SPH / PO Supplier / PO Transportir yang belum di-approve. Hanya kirim jika minimal satu jumlah > 0.';

    public function handle(): int
    {
        $sphCount = DataTrxSph::where('status', 1)->count();
        $poSupplierCount = PurchaseOrder::where('category', 1)->where('status', 1)->count();
        $poTransportirCount = PurchaseOrder::where('category', 2)->where('status', 1)->count();

        if ($sphCount === 0 && $poSupplierCount === 0 && $poTransportirCount === 0) {
            $this->info('Tidak ada item pending approval. Reminder tidak dikirim.');
            return self::SUCCESS;
        }

        if (!TelegramNotificationService::isEnabled()) {
            $this->warn('Telegram notifikasi nonaktif atau konfigurasi belum lengkap. Reminder tidak dikirim.');
            return self::SUCCESS;
        }

        $sent = TelegramNotificationService::sendReminder($sphCount, $poSupplierCount, $poTransportirCount);
        if ($sent) {
            $this->info('Reminder Telegram terkirim.');
        } else {
            $this->warn('Reminder Telegram gagal terkirim (cek log).');
        }

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
