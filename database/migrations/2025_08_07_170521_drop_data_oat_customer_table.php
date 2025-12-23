<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDataOatCustomerTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('data_oat_customer');
    }

    public function down()
    {
        Schema::create('data_oat_customer', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Tambahkan kembali kolom sesuai struktur sebelumnya jika diketahui
            $table->timestamps();
        });
    }
}