<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileSphToDataTrxSphTable extends Migration
{
    public function up()
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->string('file_sph')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('data_trx_sph', function (Blueprint $table) {
            $table->dropColumn('file_sph');
        });
    }
}