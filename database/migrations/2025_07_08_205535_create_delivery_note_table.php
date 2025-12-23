<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryNoteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_note', function (Blueprint $table) {
            $table->id();
            $table->string('dn_no')->nullable();
            $table->string('drs_no')->nullable();
            $table->string('drs_unique')->nullable();
            $table->string('customer_po')->nullable();
            $table->string('customer_name')->nullable();
            $table->date('po_date')->nullable();
            $table->date('arrival_date')->nullable();
            $table->string('consignee')->nullable();
            $table->string('delivery_to')->nullable();
            $table->text('address')->nullable();
            $table->decimal('qty', 18, 2)->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->string('segel_atas')->nullable();
            $table->string('segel_bawah')->nullable();
            $table->string('nopol')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('transportir')->nullable();
            $table->integer('so')->nullable();
            $table->string('terra')->nullable();
            $table->string('berat_jenis')->nullable();
            $table->string('temperature')->nullable();
            $table->string('tgl_bongkar')->nullable();
            $table->string('jam_mulai')->nullable();
            $table->string('jam_akhir')->nullable();
            $table->string('meter_awal')->nullable();
            $table->string('meter_akhir')->nullable();
            $table->string('tinggi_sounding')->nullable();
            $table->string('jenis_suhu')->nullable();
            $table->string('volume_diterima')->nullable();
            $table->string('created_by')->nullable();
            $table->integer('status')->nullable()->comment('0: Open, 1: Bast Done');
            $table->text('file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_note');
    }
}