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
            // Menambahkan kolom 'type' bertipe integer setelah kolom 'status'
            // 1: Invoice, 2: Proforma
            $table->integer('type')->nullable()->after('status')->comment('1: Invoice, 2: Proforma');
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
            // Menghapus kolom 'type'
            $table->dropColumn('type');
        });
    }
};

