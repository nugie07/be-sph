<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration (buat tabel baru).
     */
    public function up(): void
    {
        Schema::create('user_sys_log', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID sebagai primary key
            $table->unsignedInteger('user_id')->index(); // ID user
            $table->string('user_name', 100); // nama user
            $table->string('services', 150); // layanan yang diakses
            $table->string('activity', 255); // aktivitas user
            $table->timestamp('timestamp')->useCurrent(); // waktu aktivitas
        });
    }

    /**
     * Rollback migration (hapus tabel).
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sys_log');
    }
};