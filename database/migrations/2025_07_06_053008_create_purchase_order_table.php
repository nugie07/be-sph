<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('drs_no')->nullable();
            $table->string('drs_unique')->nullable();
            $table->string('customer_po')->nullable();
            $table->string('vendor_name')->nullable()->comment('Nama supplier atau transporter yang menerima PO');
            $table->string('vendor_po')->nullable()->comment('Nomer po ke supplier atau transporter mengikuti format');
            $table->date('tgl_po')->nullable();
            $table->string('nama')->nullable();
            $table->string('alamat')->nullable();
            $table->string('contact')->nullable();
            $table->string('fob')->nullable();
            $table->string('term')->nullable();
            $table->decimal('transport', 16, 2)->nullable();
            $table->string('loading_point')->nullable();
            $table->string('shipped_via')->nullable();
            $table->string('delivery_to')->nullable();
            $table->decimal('qty', 16, 2)->nullable();
            $table->decimal('harga', 16, 2)->nullable();
            $table->decimal('ppn', 16, 2)->nullable()->comment('hanya ada untuk PO Supplier');
            $table->decimal('pbbkb', 16, 2)->nullable()->comment('hanya ada untuk PO Supplier');
            $table->decimal('bph', 16, 2)->nullable()->comment('hanya ada untuk PO Supplier');
            $table->decimal('portal', 16, 2)->nullable()->comment('hanya ada untuk PO Transporter');
            $table->decimal('sub_total', 16, 2)->nullable();
            $table->decimal('total', 16, 2)->nullable();
            $table->string('terbilang')->nullable();
            $table->text('description')->nullable();
            $table->text('additional_notes')->nullable();
            $table->integer('category')->nullable()->comment('1: Supplier, 2: Transporter');
            $table->integer('status')->nullable()->comment('0: Draft Pengajuan , 1: Menunggu Approval, 2: Revisi, 3: Reject, 4: Approved');
            $table->string('created_by')->nullable();
            $table->string('last_updateby')->nullable();
            $table->timestamps(); // This will create created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order');
    }
}