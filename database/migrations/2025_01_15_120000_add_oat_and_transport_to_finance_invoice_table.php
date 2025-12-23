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
            // Menambahkan kolom 'oat' bertipe double setelah kolom 'pph'
            $table->double('oat')->nullable()->after('pph');
            
            // Menambahkan kolom 'transport' bertipe double setelah kolom 'oat'
            $table->double('transport')->nullable()->after('oat');
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
            // Menghapus kolom 'oat' dan 'transport'
            $table->dropColumn(['oat', 'transport']);
        });
    }
};
