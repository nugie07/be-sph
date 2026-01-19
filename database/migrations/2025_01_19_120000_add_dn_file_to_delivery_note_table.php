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
        Schema::table('delivery_note', function (Blueprint $table) {
            // Menambahkan kolom 'dn_file' bertipe text setelah kolom 'volume_diterima'
            $table->text('dn_file')->nullable()->after('volume_diterima');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_note', function (Blueprint $table) {
            // Menghapus kolom 'dn_file'
            $table->dropColumn('dn_file');
        });
    }
};
