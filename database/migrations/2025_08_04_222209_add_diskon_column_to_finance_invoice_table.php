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
            // Menambahkan kolom 'diskon' bertipe decimal(15,2) setelah kolom 'sub_total'
            $table->decimal('diskon', 15, 2)->default(0)->after('sub_total');
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
            // Menghapus kolom 'diskon'
            $table->dropColumn('diskon');
        });
    }
};