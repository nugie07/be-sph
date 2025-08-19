<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table oat_customer: pindahkan kolom location setelah cust_id
        Schema::table('oat_customer', function (Blueprint $table) {
            // drop dulu kolom location (jika ada)
            $table->dropColumn('location');
        });

        Schema::table('oat_customer', function (Blueprint $table) {
            // tambahkan kembali dengan posisi setelah cust_id
            $table->string('location')->after('cust_id');
        });

        // 2. Table master_customer: buat kolom address, pic_name, pic_contact menjadi nullable
        Schema::table('master_customer', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('pic_name')->nullable()->change();
            $table->string('pic_contact')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // rollback perubahan
        Schema::table('oat_customer', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->string('location'); // default tambah lagi tanpa after
        });

        Schema::table('master_customer', function (Blueprint $table) {
            $table->string('address')->nullable(false)->change();
            $table->string('pic_name')->nullable(false)->change();
            $table->string('pic_contact')->nullable(false)->change();
        });
    }
};