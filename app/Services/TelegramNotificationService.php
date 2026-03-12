<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    public static function isEnabled(): bool
    {
        return filter_var(config('telegram.enabled', false), FILTER_VALIDATE_BOOLEAN)
            && !empty(config('telegram.bot_token'))
            && !empty(config('telegram.channel_id'));
    }

    /**
     * Kirim pesan teks ke channel Telegram (parse_mode HTML untuk bold).
     */
    public static function send(string $text): bool
    {
        if (!self::isEnabled()) {
            Log::debug('TelegramNotificationService: disabled or missing config, skip send.');
            return false;
        }

        $token = config('telegram.bot_token');
        $channelId = config('telegram.channel_id');
        $baseUrl = rtrim(config('telegram.api_url', 'https://api.telegram.org'), '/');
        $url = "{$baseUrl}/bot{$token}/sendMessage";

        try {
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $channelId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if (!$response->successful()) {
                Log::warning('TelegramNotificationService: send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramNotificationService: exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Notifikasi: SPH baru submit untuk approval.
     * Contoh: "UPDATE ada SPH baru dengan Kode SPH XXXXXX, dibuat oleh XXXXX, Mohon Segera lakukan Review dan Approval, Terima Kasih"
     */
    public static function notifyNewSph(string $kodeSph, string $createdBy): bool
    {
        $kodeSph = trim($kodeSph) ?: '-';
        $createdBy = trim($createdBy) ?: '-';
        $text = "<b>UPDATE</b>\n\nAda SPH baru dengan Kode SPH {$kodeSph}, dibuat oleh {$createdBy}. Mohon Segera lakukan Review dan Approval. Terima Kasih.";
        return self::send($text);
    }

    /**
     * Notifikasi: PO baru (Supplier atau Transportir) submit untuk approval.
     */
    public static function notifyNewPo(string $poLabel, string $createdBy, string $poType): bool
    {
        $poLabel = trim($poLabel) ?: '-';
        $createdBy = trim($createdBy) ?: '-';
        $typeLabel = $poType === 'Transportir' ? 'PO Transportir' : 'PO Supplier';
        $text = "<b>UPDATE</b>\n\nAda {$typeLabel} baru ({$poLabel}), dibuat oleh {$createdBy}. Mohon Segera lakukan Review dan Approval. Terima Kasih.";
        return self::send($text);
    }

    /**
     * Reminder: masih ada SPH / PO Supplier / PO Transportir yang belum di-approve.
     * Hanya kirim jika minimal satu count > 0 (pemanggil yang cek).
     */
    public static function sendReminder(int $sphCount, int $poSupplierCount, int $poTransportirCount): bool
    {
        if ($sphCount < 0) {
            $sphCount = 0;
        }
        if ($poSupplierCount < 0) {
            $poSupplierCount = 0;
        }
        if ($poTransportirCount < 0) {
            $poTransportirCount = 0;
        }

        if ($sphCount === 0 && $poSupplierCount === 0 && $poTransportirCount === 0) {
            return false;
        }

        $lines = [
            "<b>REMINDER</b>",
            "Masih ada:",
            "SPH : {$sphCount} Jumlah SPH belum di Approved",
            "PO Supplier : {$poSupplierCount} Jumlah PO Supplier belum di approved",
            "PO Transportir : {$poTransportirCount} Jumlah PO Transportir belum di approved",
        ];
        $text = implode("\n", $lines);
        return self::send($text);
    }
}
