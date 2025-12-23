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
        Schema::table('invoice_details', function (Blueprint $table) {
            // Tambahkan kolom 'kode_barang' (varchar) setelah kolom 'invoice_id'
            $table->string('kode_barang', 100)->after('invoice_id')->nullable();

            // Tambahkan kolom 'diskon' (decimal) setelah kolom 'harga'
            $table->decimal('diskon', 15, 2)->default(0)->after('harga');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Gunakan conditional agar tidak error jika kolom belum ada
        if (Schema::hasColumn('invoice_details', 'diskon')) {
            Schema::table('invoice_details', function (Blueprint $table) {
                $table->dropColumn('diskon');
            });
        }
        if (Schema::hasColumn('invoice_details', 'kode_barang')) {
            Schema::table('invoice_details', function (Blueprint $table) {
                $table->dropColumn('kode_barang');
            });
        }
    }
};