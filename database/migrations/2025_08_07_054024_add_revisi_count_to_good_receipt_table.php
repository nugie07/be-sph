<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRevisiCountToGoodReceiptTable extends Migration
{
    public function up()
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->integer('revisi_count')->default(0)->after('status');
        });
    }

    public function down()
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->dropColumn('revisi_count');
        });
    }
}