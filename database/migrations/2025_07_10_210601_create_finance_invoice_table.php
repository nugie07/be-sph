<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinanceInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('finance_invoice', function (Blueprint $table) {
            $table->id();
            $table->string('drs_no')->nullable();
            $table->string('drs_unique')->nullable();
            $table->unsignedBigInteger('bast_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('terms')->nullable();
            $table->string('po_no')->nullable();
            $table->string('bill_to')->nullable();
            $table->string('ship_to')->nullable();
            $table->string('fob')->nullable();
            $table->date('sent_date')->nullable();
            $table->string('sent_via')->nullable();
            $table->decimal('sub_total', 18, 2)->nullable();
            $table->decimal('ppn', 18, 2)->nullable();
            $table->decimal('pbbkb', 18, 2)->nullable();
            $table->decimal('pph', 18, 2)->nullable();
            $table->decimal('total', 18, 2)->nullable();
            $table->string('terbilang')->nullable();
            $table->integer('status')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('finance_invoice');
    }
}