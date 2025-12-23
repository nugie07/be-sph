<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeLastUpdatebyNullableInGoodReceiptTable extends Migration
{
    public function up()
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->integer('last_updateby')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->integer('last_updateby')->nullable(false)->change();
        });
    }
}