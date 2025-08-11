<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_oat_customer', function (Blueprint $table) {
            // Kolom ID utama, auto-increment
            $table->id();

            // Kolom untuk menyimpan ID customer.
            // Menggunakan unsignedBigInteger adalah praktik terbaik untuk foreign key.
            $table->unsignedBigInteger('customer_id');

            // Kolom untuk menyimpan nama lokasi
            $table->string('lokasi')->nullable()->comment('Nama lokasi customer');

            // Kolom status dengan nilai default 1 (active)
            // 0: inactive, 1: active
            $table->boolean('status')->nullable()->default(1)->comment('0: inactive, 1: active');

            // Kolom created_at dan updated_at
            $table->timestamps();

            // Opsional: Jika Anda memiliki tabel 'customers', Anda bisa menambahkan foreign key constraint.
            // Hapus komentar di bawah ini jika Anda ingin menggunakannya.
            // $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_oat_customer');
    }
};
