<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder approval ke Telegram: cek SPH & PO belum approved, kirim hanya jika ada yang pending
Schedule::command('telegram:send-approval-reminder')->dailyAt('09:00');
