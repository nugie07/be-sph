<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterSupplierTransporterTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('master_supplier_transporter', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5); // kode unik 5 digit
            $table->string('name');
            $table->string('format');
            $table->string('pic_name');
            $table->string('pic_contact');
            $table->string('address');
            $table->tinyInteger('type')->comment('1: supplier, 2: transporter');
            $table->tinyInteger('status')->default(1)->comment('0: suspend, 1: active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('master_supplier_transporter');
    }
}