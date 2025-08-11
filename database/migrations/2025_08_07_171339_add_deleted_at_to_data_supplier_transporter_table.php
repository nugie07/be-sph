<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtToDataSupplierTransporterTable extends Migration
{
    public function up()
    {
        Schema::table('data_supplier_transporter', function (Blueprint $table) {
            $table->softDeletes()->after('category');
        });
    }

    public function down()
    {
        Schema::table('data_supplier_transporter', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}