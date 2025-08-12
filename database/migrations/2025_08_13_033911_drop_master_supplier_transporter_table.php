<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration (hapus tabel).
     */
    public function up(): void
    {
        Schema::dropIfExists('master_supplier_transporter');
    }

    /**
     * Rollback migration (buat tabel lagi).
     */
    public function down(): void
    {
        Schema::create('master_supplier_transporter', function (Blueprint $table) {
            $table->id();
            // tambahkan kembali struktur kolom sesuai versi sebelumnya
            $table->timestamps();
        });
    }
};