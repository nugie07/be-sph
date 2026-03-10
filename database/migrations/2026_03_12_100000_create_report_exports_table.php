<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('report_type', 20); // ar, ap, logistik
            $table->string('ap_sub_type', 20)->nullable(); // all, supplier, transportir (hanya untuk report_type=ap)
            $table->date('date_from')->nullable(); // wajib untuk AR (dan logistik jika ada)
            $table->date('date_to')->nullable();
            $table->string('status', 20)->default('pending'); // pending, processing, ready, failed
            $table->text('file_path')->nullable(); // path di BytePlus storage
            $table->text('error')->nullable(); // pesan error jika failed
            $table->string('filename', 255)->nullable(); // nama file untuk download
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
