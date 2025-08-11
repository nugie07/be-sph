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
        Schema::table('finance_invoice', function (Blueprint $table) {
            // Tambahkan kolom 'file' bertipe text setelah kolom 'status'
            $table->text('file')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_invoice', function (Blueprint $table) {
            // Hapus kolom 'file'
            $table->dropColumn('file');
        });
    }
};