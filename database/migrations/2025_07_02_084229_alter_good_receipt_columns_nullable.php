<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterGoodReceiptColumnsNullable extends Migration
{
    public function up()
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->decimal('sub_total', 20, 2)->nullable()->change();
            $table->decimal('ppn', 20, 2)->nullable()->change();
            $table->decimal('pbbkb', 20, 2)->nullable()->change();
            $table->decimal('pph', 20, 2)->nullable()->change();
            $table->decimal('total', 20, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('good_receipt', function (Blueprint $table) {
            $table->decimal('sub_total', 20, 2)->nullable(false)->change();
            $table->decimal('ppn', 20, 2)->nullable(false)->change();
            $table->decimal('pbbkb', 20, 2)->nullable(false)->change();
            $table->decimal('pph', 20, 2)->nullable(false)->change();
            $table->decimal('total', 20, 2)->nullable(false)->change();
        });
    }
}