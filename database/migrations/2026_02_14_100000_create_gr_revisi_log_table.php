<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Log revisi good receipt: copy data good_receipt sebelum di-update.
     */
    public function up(): void
    {
        Schema::create('gr_revisi_log', function (Blueprint $table) {
            $table->id();
            $table->integer('daily_seq')->nullable();
            $table->string('kode_sph')->nullable();
            $table->string('nama_customer')->nullable();
            $table->string('po_no')->nullable();
            $table->string('no_seq')->nullable();
            $table->string('po_file')->nullable();
            $table->decimal('sub_total', 18, 2)->nullable();
            $table->decimal('ppn', 18, 2)->nullable();
            $table->decimal('pbbkb', 18, 2)->nullable();
            $table->decimal('pph', 18, 2)->nullable();
            $table->decimal('transport', 15, 2)->nullable();
            $table->boolean('bypass')->default(false);
            $table->decimal('total', 18, 2)->nullable();
            $table->string('terbilang')->nullable();
            $table->string('created_by')->nullable()->comment('User yang melakukan revisi');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gr_revisi_log');
    }
};
